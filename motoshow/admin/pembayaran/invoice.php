<?php
/**
 * Cetak Kuitansi Resmi Pembayaran MotoShow
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas', 'pelanggan']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, t.no_transaksi, t.tanggal as tanggal_trx,
           c.nama_lengkap as nama_pelanggan, c.nik, c.no_hp, c.alamat
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    JOIN customers c ON t.customer_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pay = $stmt->fetch();

if (!$pay) {
    die("Kuitansi pembayaran tidak ditemukan.");
}

// Terbilang function helper sederhana untuk Rupiah
function terbilang($angka) {
    $angka = abs($angka);
    $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $terbilang = "";
    if ($angka < 12) {
        $terbilang = " " . $baca[$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang($angka - 10) . " Belas";
    } elseif ($angka < 100) {
        $terbilang = terbilang($angka / 10) . " Puluh" . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = " Seratus" . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $terbilang = terbilang($angka / 100) . " Ratus" . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = " Seribu" . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $terbilang = terbilang($angka / 1000) . " Ribu" . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = terbilang($angka / 1000000) . " Juta" . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $terbilang = terbilang($angka / 1000000000) . " Milyar" . terbilang(fmod($angka, 1000000000));
    }
    return $terbilang;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kuitansi Pembayaran - <?= htmlspecialchars($pay['no_pembayaran']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 30px 15px;
      color: #0f172a;
    }
    .receipt-box {
      max-width: 780px;
      margin: 0 auto;
      background: #ffffff;
      padding: 35px;
      border: 2px dashed #cbd5e1;
      border-radius: 10px;
      position: relative;
    }
    .receipt-title {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #b91c1c;
    }
    .receipt-row {
      display: flex;
      padding: 10px 0;
      border-bottom: 1px dotted #e2e8f0;
      font-size: 15px;
    }
    .receipt-row .label {
      width: 220px;
      font-weight: 600;
      color: #475569;
    }
    .receipt-row .val {
      flex: 1;
      font-weight: 500;
    }
    @media print {
      body { background: #fff; padding: 0; }
      .receipt-box { border: 2px solid #000; box-shadow: none; padding: 25px; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="receipt-box">
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-danger btn-sm px-4">Cetak Kuitansi</button>
  </div>

  <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
    <div>
      <h4 class="fw-black mb-0 text-dark">MOTO<span class="text-danger">SHOW</span></h4>
      <div class="small text-muted">Authorized Motorcycle Dealer & Finance</div>
    </div>
    <div class="text-end">
      <div class="receipt-title">KUITANSI PEMBAYARAN</div>
      <div class="font-monospace fw-bold small text-secondary">NO: <?= htmlspecialchars($pay['no_pembayaran']) ?></div>
    </div>
  </div>

  <div class="receipt-row">
    <div class="label">Telah Diterima Dari</div>
    <div class="val fw-bold text-uppercase"><?= htmlspecialchars($pay['nama_pelanggan']) ?> (NIK: <?= htmlspecialchars($pay['nik']) ?>)</div>
  </div>

  <div class="receipt-row">
    <div class="label">Sejumlah Uang</div>
    <div class="val fst-italic fw-bold text-danger bg-light p-2 rounded">
      "<?= trim(terbilang($pay['jumlah_bayar'])) ?> Rupiah"
    </div>
  </div>

  <div class="receipt-row">
    <div class="label">Untuk Pembayaran</div>
    <div class="val">
      <?= htmlspecialchars($pay['keterangan'] ?: 'Pelunasan Transaksi') ?> &bull; No. Transaksi: <strong><?= htmlspecialchars($pay['no_transaksi']) ?></strong>
    </div>
  </div>

  <div class="receipt-row">
    <div class="label">Metode & Status</div>
    <div class="val">
      <span class="badge bg-secondary"><?= htmlspecialchars($pay['metode_pembayaran']) ?></span>
      <span class="badge bg-success ms-1"><?= htmlspecialchars($pay['status']) ?></span>
    </div>
  </div>

  <?php if ($pay['sisa_bayar'] > 0): ?>
    <div class="receipt-row">
      <div class="label">Sisa Saldo Pinjaman</div>
      <div class="val text-danger fw-bold"><?= formatRupiah($pay['sisa_bayar']) ?></div>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-end mt-4 pt-3">
    <div class="p-3 bg-light rounded border border-2 border-danger text-center" style="min-width: 240px;">
      <div class="small text-muted mb-1 text-uppercase fw-bold">Nominal Pembayaran:</div>
      <div class="fs-4 fw-black text-danger"><?= formatRupiah($pay['jumlah_bayar']) ?></div>
    </div>

    <div class="text-center" style="min-width: 220px;">
      <div class="small text-muted mb-4">Jakarta, <?= formatTanggal($pay['tanggal_bayar']) ?><br>Kasir Penerima,</div>
      <div class="fw-bold mt-4 text-decoration-underline">( Finance MotoShow )</div>
      <div class="text-muted small">Stempel Resmi Showroom</div>
    </div>
  </div>
</div>

</body>
</html>
