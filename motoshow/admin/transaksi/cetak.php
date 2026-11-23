<?php
/**
 * Cetak Faktur / Invoice Penjualan MotoShow
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas', 'pelanggan']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.nik, c.no_hp, c.email, c.alamat,
           u.nama_lengkap as nama_kasir
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Faktur transaksi tidak ditemukan.");
}

// Jika pelanggan yang login, pastikan hanya bisa cetak miliknya sendiri
if ($_SESSION['user_role'] === 'pelanggan') {
    $stmtC = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmtC->execute([$_SESSION['user_id']]);
    $myCust = $stmtC->fetch();
    if (!$myCust || $myCust['id'] != $trx['customer_id']) {
        die("Akses tidak sah.");
    }
}

// Ambil item
$stmtDetails = $pdo->prepare("
    SELECT td.*, m.nama_motor, m.kode_motor, m.warna, b.nama_merek 
    FROM transaction_details td
    JOIN motors m ON td.motor_id = m.id
    JOIN brands b ON m.brand_id = b.id
    WHERE td.transaction_id = ?
");
$stmtDetails->execute([$id]);
$items = $stmtDetails->fetchAll();

// Ambil riwayat pembayaran
$payments = $pdo->prepare("SELECT * FROM payments WHERE transaction_id = ? ORDER BY id ASC");
$payments->execute([$id]);
$payList = $payments->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Faktur Penjualan - <?= htmlspecialchars($trx['no_transaksi']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #1e293b;
      padding: 30px 15px;
    }
    .invoice-wrapper {
      max-width: 820px;
      margin: 0 auto;
      background: #ffffff;
      padding: 40px;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .invoice-header {
      border-bottom: 2px solid #ef4444;
      padding-bottom: 20px;
      margin-bottom: 25px;
    }
    .brand-title {
      font-size: 24px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
    }
    .brand-title span {
      color: #ef4444;
    }
    .table thead th {
      background-color: #f1f5f9;
      color: #334155;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    @media print {
      body { background: #fff; padding: 0; }
      .invoice-wrapper { border: none; box-shadow: none; padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="invoice-wrapper">
  <!-- Tombol Aksi Cetak -->
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-danger btn-sm px-4">
      <i class="bi bi-printer"></i> Cetak Dokumen Faktur
    </button>
  </div>

  <!-- Header Invoice -->
  <div class="invoice-header d-flex justify-content-between align-items-start">
    <div>
      <div class="brand-title">MOTO<span>SHOW</span></div>
      <div class="text-muted small">Showroom Sepeda Motor Premium & Dealer Resmi</div>
      <div class="small text-secondary mt-1">
        Jl. Showroom Raya Boulevard No. 88, Jakarta Selatan<br>
        Telp: (021) 7890-1234 | WhatsApp: 0812-3456-7890<br>
        Email: info@motoshow.com | Website: www.motoshow.com
      </div>
    </div>
    <div class="text-end">
      <h3 class="fw-black text-danger mb-1">FAKTUR PENJUALAN</h3>
      <div class="fw-bold font-monospace fs-6"><?= htmlspecialchars($trx['no_transaksi']) ?></div>
      <div class="text-muted small">Tanggal: <?= formatTanggal($trx['tanggal']) ?></div>
      <div class="badge <?= $trx['status'] === 'Lunas' ? 'bg-success' : 'bg-warning text-dark' ?> mt-2 px-3 py-1">
        STATUS: <?= strtoupper($trx['status']) ?>
      </div>
    </div>
  </div>

  <!-- Informasi Pembeli & Transaksi -->
  <div class="row mb-4">
    <div class="col-6">
      <div class="text-muted small text-uppercase fw-bold mb-1">Diterbitkan Kepada:</div>
      <div class="fw-bold fs-6"><?= htmlspecialchars($trx['nama_pelanggan']) ?></div>
      <div class="small text-secondary">NIK: <?= htmlspecialchars($trx['nik']) ?></div>
      <div class="small text-secondary">No. HP: <?= htmlspecialchars($trx['no_hp']) ?></div>
      <div class="small text-secondary">Alamat: <?= htmlspecialchars($trx['alamat']) ?></div>
    </div>
    <div class="col-6 text-end">
      <div class="text-muted small text-uppercase fw-bold mb-1">Informasi Transaksi:</div>
      <div class="small"><strong>Metode:</strong> <?= htmlspecialchars($trx['metode_pembayaran']) ?></div>
      <div class="small"><strong>Kasir/Petugas:</strong> <?= htmlspecialchars($trx['nama_kasir'] ?: 'Admin') ?></div>
      <?php if (!empty($trx['catatan'])): ?>
        <div class="small text-muted mt-1"><strong>Catatan:</strong> <?= htmlspecialchars($trx['catatan']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabel Barang -->
  <table class="table table-bordered mb-4">
    <thead>
      <tr>
        <th style="width: 50px;">No</th>
        <th>Deskripsi Unit Motor</th>
        <th class="text-center" style="width: 80px;">Qty</th>
        <th class="text-end" style="width: 170px;">Harga Satuan</th>
        <th class="text-end" style="width: 170px;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $idx => $it): ?>
        <tr>
          <td class="text-center"><?= $idx + 1 ?></td>
          <td>
            <div class="fw-bold"><?= htmlspecialchars($it['nama_motor']) ?></div>
            <div class="text-muted small">Kode: <?= htmlspecialchars($it['kode_motor']) ?> &bull; Warna: <?= htmlspecialchars($it['warna']) ?> &bull; Merek: <?= htmlspecialchars($it['nama_merek']) ?></div>
          </td>
          <td class="text-center fw-bold"><?= $it['jumlah'] ?></td>
          <td class="text-end"><?= formatRupiah($it['harga_satuan']) ?></td>
          <td class="text-end fw-bold"><?= formatRupiah($it['subtotal']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="text-end fw-semibold">Subtotal :</td>
        <td class="text-end fw-bold"><?= formatRupiah($trx['total_harga']) ?></td>
      </tr>
      <?php if ($trx['diskon'] > 0): ?>
        <tr>
          <td colspan="4" class="text-end text-success fw-semibold">Potongan Diskon :</td>
          <td class="text-end text-success fw-bold">- <?= formatRupiah($trx['diskon']) ?></td>
        </tr>
      <?php endif; ?>
      <tr class="table-light">
        <td colspan="4" class="text-end fs-6 fw-bold">TOTAL PEMBAYARAN :</td>
        <td class="text-end fs-6 fw-black text-danger"><?= formatRupiah($trx['total_bayar']) ?></td>
      </tr>
    </tfoot>
  </table>

  <!-- Keterangan Kredit jika ada -->
  <?php if ($trx['metode_pembayaran'] === 'Kredit' && !empty($payList)): 
    $pCredit = $payList[0];
  ?>
    <div class="p-3 bg-light rounded border mb-4 small">
      <div class="fw-bold mb-1">Rincian Skema Pembiayaan Kredit Showroom:</div>
      <div class="row">
        <div class="col-md-3">Uang Muka (DP): <strong><?= formatRupiah($pCredit['dp']) ?></strong></div>
        <div class="col-md-3">Jangka Tenor: <strong><?= $pCredit['tenor'] ?> Bulan</strong></div>
        <div class="col-md-3">Cicilan / Bulan: <strong><?= formatRupiah($pCredit['cicilan_per_bulan']) ?></strong></div>
        <div class="col-md-3 text-danger">Sisa Pokok: <strong><?= formatRupiah($pCredit['sisa_bayar']) ?></strong></div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Tanda Tangan & Cap Showroom -->
  <div class="row mt-5 pt-3 text-center">
    <div class="col-6">
      <div class="small text-muted mb-5">Tanda Tangan Pelanggan,</div>
      <div class="fw-bold mt-4">( <?= htmlspecialchars($trx['nama_pelanggan']) ?> )</div>
    </div>
    <div class="col-6">
      <div class="small text-muted mb-5">Petugas / Kasir Showroom,</div>
      <div class="fw-bold mt-4">( <?= htmlspecialchars($trx['nama_kasir'] ?: 'Finance MotoShow') ?> )</div>
      <div class="text-muted small">Cap & Stempel Resmi MotoShow</div>
    </div>
  </div>

  <div class="mt-5 pt-3 border-top text-center text-muted small">
    * Faktur resmi ini merupakan bukti sah kepemilikan dan serah terima unit kendaraan bermotor. Simpan lembar ini untuk pengurusan STNK & BPKB.
  </div>
</div>

</body>
</html>
