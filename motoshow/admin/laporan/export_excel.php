<?php
/**
 * Ekspor Laporan Penjualan ke File Excel (.xls)
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();

$periode = cleanInput($_GET['periode'] ?? 'bulan_ini');
$tglMulai = cleanInput($_GET['tgl_mulai'] ?? '');
$tglSelesai = cleanInput($_GET['tgl_selesai'] ?? '');

$today = date('Y-m-d');
if ($periode === 'hari_ini') {
    $tglMulai = $today;
    $tglSelesai = $today;
    $labelPeriode = 'Hari Ini (' . formatTanggal($today) . ')';
} elseif ($periode === 'bulan_ini') {
    $tglMulai = date('Y-m-01');
    $tglSelesai = date('Y-m-t');
    $labelPeriode = 'Bulan Ini (' . date('F Y') . ')';
} elseif ($periode === 'tahun_ini') {
    $tglMulai = date('Y') . '-01-01';
    $tglSelesai = date('Y') . '-12-31';
    $labelPeriode = 'Tahun Ini (' . date('Y') . ')';
} else {
    if (empty($tglMulai)) $tglMulai = date('Y-m-01');
    if (empty($tglSelesai)) $tglSelesai = $today;
    $labelPeriode = 'Periode ' . formatTanggal($tglMulai) . ' s/d ' . formatTanggal($tglSelesai);
}

// Ambil Transaksi
$stmtTrx = $pdo->prepare("
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.nik, c.no_hp, u.nama_lengkap as nama_kasir,
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR '; ') as motor_list,
           COALESCE(SUM(td.jumlah), 0) as total_qty
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN motors m ON td.motor_id = m.id
    WHERE t.status != 'Batal' AND t.tanggal BETWEEN ? AND ?
    GROUP BY t.id
    ORDER BY t.tanggal DESC, t.id DESC
");
$stmtTrx->execute([$tglMulai, $tglSelesai]);
$transactions = $stmtTrx->fetchAll();

// Header untuk Download Excel
$fileName = "Laporan_Penjualan_MotoShow_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Calibri, Arial, sans-serif; }
    th { background-color: #dc2626; color: #ffffff; font-weight: bold; padding: 10px; border: 1px solid #000; }
    td { padding: 8px; border: 1px solid #ddd; }
    .title { font-size: 16pt; font-weight: bold; }
    .subtitle { font-size: 11pt; color: #555; }
    .num { text-align: right; }
  </style>
</head>
<body>
  <table>
    <tr>
      <td colspan="8" class="title">LAPORAN PENJUALAN SHOWROOM MOTOSHOW</td>
    </tr>
    <tr>
      <td colspan="8" class="subtitle">Periode: <?= htmlspecialchars($labelPeriode) ?> | Tanggal Unduh: <?= date('d/m/Y H:i') ?></td>
    </tr>
    <tr><td colspan="8"></td></tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>No. Transaksi</th>
        <th>Tanggal</th>
        <th>Nama Pelanggan</th>
        <th>NIK Pelanggan</th>
        <th>Unit Motor</th>
        <th>Metode Pembayaran</th>
        <th>Diskon (Rp)</th>
        <th>Total Bayar (Rp)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $grandTotal = 0;
      $grandDiskon = 0;
      foreach ($transactions as $idx => $t): 
        $grandTotal += (float)$t['total_bayar'];
        $grandDiskon += (float)$t['diskon'];
      ?>
        <tr>
          <td align="center"><?= $idx + 1 ?></td>
          <td><?= htmlspecialchars($t['no_transaksi']) ?></td>
          <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
          <td><?= htmlspecialchars($t['nama_pelanggan']) ?></td>
          <td style="mso-number-format:'\@';">'<?= htmlspecialchars($t['nik']) ?></td>
          <td><?= htmlspecialchars($t['motor_list']) ?></td>
          <td><?= htmlspecialchars($t['metode_pembayaran']) ?></td>
          <td class="num"><?= number_format($t['diskon'], 0, ',', '.') ?></td>
          <td class="num"><?= number_format($t['total_bayar'], 0, ',', '.') ?></td>
          <td align="center"><?= htmlspecialchars($t['status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="7" align="right">GRAND TOTAL :</th>
        <th class="num"><?= number_format($grandDiskon, 0, ',', '.') ?></th>
        <th class="num"><?= number_format($grandTotal, 0, ',', '.') ?></th>
        <th></th>
      </tr>
    </tfoot>
  </table>
</body>
</html>
