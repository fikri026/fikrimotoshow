<?php
/**
 * Cetak Laporan Penjualan Showroom MotoShow
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

// Summary
$stmtSummary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT t.id) as total_trx,
        COALESCE(SUM(td.jumlah), 0) as total_unit,
        COALESCE(SUM(t.total_harga), 0) as total_kotor,
        COALESCE(SUM(t.diskon), 0) as total_diskon,
        COALESCE(SUM(t.total_bayar), 0) as total_bersih
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    WHERE t.status != 'Batal' AND t.tanggal BETWEEN ? AND ?
");
$stmtSummary->execute([$tglMulai, $tglSelesai]);
$summary = $stmtSummary->fetch();

// Transaksi
$stmtTrx = $pdo->prepare("
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.nik, u.nama_lengkap as nama_kasir,
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR ', ') as motor_list,
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Penjualan - MotoShow (<?= htmlspecialchars($labelPeriode) ?>)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 30px;
      color: #0f172a;
    }
    .report-wrapper {
      max-width: 950px;
      margin: 0 auto;
      background: #ffffff;
      padding: 40px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
    }
    .brand-title {
      font-size: 24px;
      font-weight: 900;
      color: #0f172a;
    }
    .brand-title span { color: #dc2626; }
    .table thead th {
      background: #f1f5f9;
      color: #1e293b;
      font-size: 13px;
      text-transform: uppercase;
    }
    @media print {
      body { background: #fff; padding: 0; }
      .report-wrapper { border: none; box-shadow: none; padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="report-wrapper">
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-danger btn-sm px-4">Cetak Laporan</button>
  </div>

  <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
    <div>
      <div class="brand-title">MOTO<span>SHOW</span></div>
      <div class="text-muted small">Showroom Sepeda Motor & Layanan Pembiayaan Terpadu</div>
      <div class="text-secondary small">Jl. Showroom Raya No. 88, Jakarta Selatan | Telp: (021) 7890-1234</div>
    </div>
    <div class="text-end">
      <h4 class="fw-bold text-danger mb-1">LAPORAN PENJUALAN</h4>
      <div class="fw-semibold text-secondary"><?= htmlspecialchars($labelPeriode) ?></div>
      <div class="text-muted small">Dicetak: <?= date('d/m/Y H:i') ?> WIB</div>
    </div>
  </div>

  <!-- KPI Box -->
  <div class="row g-3 mb-4">
    <div class="col-3">
      <div class="p-3 bg-light border rounded text-center">
        <div class="small text-muted text-uppercase fw-bold">Total Transaksi</div>
        <div class="fs-4 fw-bold"><?= number_format($summary['total_trx']) ?></div>
      </div>
    </div>
    <div class="col-3">
      <div class="p-3 bg-light border rounded text-center">
        <div class="small text-muted text-uppercase fw-bold">Unit Terjual</div>
        <div class="fs-4 fw-bold text-success"><?= number_format($summary['total_unit']) ?></div>
      </div>
    </div>
    <div class="col-3">
      <div class="p-3 bg-light border rounded text-center">
        <div class="small text-muted text-uppercase fw-bold">Total Diskon</div>
        <div class="fs-5 fw-bold text-warning"><?= formatRupiah($summary['total_diskon']) ?></div>
      </div>
    </div>
    <div class="col-3">
      <div class="p-3 bg-light border rounded text-center">
        <div class="small text-muted text-uppercase fw-bold">Total Pendapatan</div>
        <div class="fs-5 fw-bold text-danger"><?= formatRupiah($summary['total_bersih']) ?></div>
      </div>
    </div>
  </div>

  <table class="table table-bordered mb-4">
    <thead>
      <tr>
        <th style="width: 40px;">No</th>
        <th>No. Transaksi</th>
        <th>Tanggal</th>
        <th>Nama Pelanggan</th>
        <th>Unit Motor</th>
        <th>Metode</th>
        <th class="text-end">Total Bayar</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($transactions)): ?>
        <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada transaksi pada periode ini.</td></tr>
      <?php else: ?>
        <?php foreach ($transactions as $idx => $t): ?>
          <tr>
            <td class="text-center"><?= $idx + 1 ?></td>
            <td class="fw-bold font-monospace"><?= htmlspecialchars($t['no_transaksi']) ?></td>
            <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
            <td><?= htmlspecialchars($t['nama_pelanggan']) ?></td>
            <td class="small"><?= htmlspecialchars($t['motor_list']) ?></td>
            <td><?= htmlspecialchars($t['metode_pembayaran']) ?></td>
            <td class="text-end fw-bold"><?= formatRupiah($t['total_bayar']) ?></td>
            <td><?= htmlspecialchars($t['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr class="table-light">
        <td colspan="6" class="text-end fw-bold">GRAND TOTAL PENDAPATAN :</td>
        <td class="text-end fw-black text-danger"><?= formatRupiah($summary['total_bersih']) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  <!-- Tanda Tangan -->
  <div class="row mt-5 pt-3 text-center">
    <div class="col-6">
      <div class="small text-muted mb-5">Dibuat Oleh,</div>
      <div class="fw-bold mt-4">( <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin Showroom') ?> )</div>
      <div class="text-muted small">Admin Showroom MotoShow</div>
    </div>
    <div class="col-6">
      <div class="small text-muted mb-5">Mengetahui / Menyetujui,</div>
      <div class="fw-bold mt-4">( General Manager )</div>
      <div class="text-muted small">Pimpinan Showroom MotoShow</div>
    </div>
  </div>
</div>

</body>
</html>
