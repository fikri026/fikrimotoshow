<?php
/**
 * Detail Transaksi Penjualan & Invoice View
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Detail Transaksi Penjualan - MotoShow';
$activeMenu = 'transaksi';

$id = (int)($_GET['id'] ?? 0);

// Ambil transaksi utama
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
    setFlash('danger', 'Transaksi tidak ditemukan!');
    $backUrl = ($_SESSION['user_role'] === 'petugas') ? 'petugas/riwayat.php' : 'admin/transaksi/index.php';
    header('Location: ' . base_url($backUrl));
    exit;
}

// Ambil item yang dibeli
$stmtDetails = $pdo->prepare("
    SELECT td.*, m.nama_motor, m.kode_motor, m.warna, m.foto, b.nama_merek 
    FROM transaction_details td
    JOIN motors m ON td.motor_id = m.id
    JOIN brands b ON m.brand_id = b.id
    WHERE td.transaction_id = ?
");
$stmtDetails->execute([$id]);
$items = $stmtDetails->fetchAll();

// Ambil data pembayaran
$stmtPayments = $pdo->prepare("SELECT * FROM payments WHERE transaction_id = ? ORDER BY id ASC");
$stmtPayments->execute([$id]);
$payments = $stmtPayments->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
      <div>
        <?php $backUrl = ($_SESSION['user_role'] === 'petugas') ? base_url('petugas/riwayat.php') : base_url('admin/transaksi/index.php'); ?>
        <a href="<?= $backUrl ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Riwayat Transaksi
        </a>
        <div class="d-flex align-items-center gap-2">
          <h4 class="fw-black text-white mb-0"><?= htmlspecialchars($trx['no_transaksi']) ?></h4>
          <?php if ($trx['status'] === 'Lunas'): ?>
            <span class="badge badge-status-lunas">Lunas</span>
          <?php elseif ($trx['status'] === 'Cicilan Berjalan'): ?>
            <span class="badge badge-status-cicilan">Cicilan Berjalan</span>
          <?php elseif ($trx['status'] === 'Pending'): ?>
            <span class="badge badge-status-pending">Pending</span>
          <?php else: ?>
            <span class="badge badge-status-batal">Dibatalkan</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('admin/transaksi/cetak.php?id=' . $trx['id']) ?>" target="_blank" class="btn btn-outline-ms">
          <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Cetak Faktur / Invoice
        </a>
        <?php if ($trx['status'] === 'Cicilan Berjalan'): ?>
          <a href="<?= base_url('admin/pembayaran/input.php?trx_id=' . $trx['id']) ?>" class="btn btn-primary-ms">
            <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Bayar Cicilan
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="row g-4">
      <!-- Info Pelanggan & Petugas -->
      <div class="col-md-6">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="user" class="text-danger" style="width: 18px; height: 18px;"></i> Identitas Pembeli
            </h6>
          </div>
          <div class="ms-card-body">
            <div class="spec-item"><span class="label">Nama Lengkap</span><span class="val"><?= htmlspecialchars($trx['nama_pelanggan']) ?></span></div>
            <div class="spec-item"><span class="label">NIK KTP</span><span class="val font-monospace"><?= htmlspecialchars($trx['nik']) ?></span></div>
            <div class="spec-item"><span class="label">No. Telepon / WA</span><span class="val"><?= htmlspecialchars($trx['no_hp']) ?></span></div>
            <div class="spec-item"><span class="label">Email</span><span class="val"><?= htmlspecialchars($trx['email'] ?: '-') ?></span></div>
            <div class="spec-item"><span class="label">Alamat</span><span class="val text-end" style="max-width: 250px;"><?= htmlspecialchars($trx['alamat']) ?></span></div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="file-text" class="text-danger" style="width: 18px; height: 18px;"></i> Ringkasan Order
            </h6>
          </div>
          <div class="ms-card-body">
            <div class="spec-item"><span class="label">Tanggal Order</span><span class="val"><?= formatTanggal($trx['tanggal']) ?></span></div>
            <div class="spec-item"><span class="label">Petugas / Kasir</span><span class="val"><?= htmlspecialchars($trx['nama_kasir'] ?: 'Admin') ?></span></div>
            <div class="spec-item"><span class="label">Metode Pembayaran</span><span class="val badge bg-dark border border-secondary text-white"><?= htmlspecialchars($trx['metode_pembayaran']) ?></span></div>
            <div class="spec-item"><span class="label">Status Transaksi</span><span class="val"><?= htmlspecialchars($trx['status']) ?></span></div>
            <div class="spec-item"><span class="label">Catatan Tambahan</span><span class="val text-muted fst-italic"><?= htmlspecialchars($trx['catatan'] ?: 'Tidak ada') ?></span></div>
          </div>
        </div>
      </div>

      <!-- Detail Unit Motor Yang Dibeli -->
      <div class="col-12">
        <div class="ms-card">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0">Rincian Unit Motor</h6>
          </div>
          <div class="table-responsive">
            <table class="table table-custom-dark mb-0">
              <thead>
                <tr>
                  <th style="width: 70px;">Unit</th>
                  <th>Nama & Tipe Motor</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-end">Harga Satuan</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td>
                      <img src="<?= getMotorImage($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama_motor']) ?>" class="rounded" style="width: 55px; height: 40px; object-fit: cover;">
                    </td>
                    <td>
                      <div class="fw-bold text-white"><?= htmlspecialchars($item['nama_motor']) ?></div>
                      <div class="text-muted small">
                        <?= htmlspecialchars($item['nama_merek']) ?> &bull; Warna: <?= htmlspecialchars($item['warna']) ?> &bull; <?= htmlspecialchars($item['kode_motor']) ?>
                      </div>
                    </td>
                    <td class="text-center fw-bold text-white"><?= $item['jumlah'] ?> Unit</td>
                    <td class="text-end text-muted"><?= formatRupiah($item['harga_satuan']) ?></td>
                    <td class="text-end fw-bold text-white"><?= formatRupiah($item['subtotal']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="4" class="text-end text-muted">Subtotal Harga Unit :</td>
                  <td class="text-end text-white fw-bold"><?= formatRupiah($trx['total_harga']) ?></td>
                </tr>
                <?php if ($trx['diskon'] > 0): ?>
                  <tr>
                    <td colspan="4" class="text-end text-success">Potongan Diskon Khusus :</td>
                    <td class="text-end text-success fw-bold">- <?= formatRupiah($trx['diskon']) ?></td>
                  </tr>
                <?php endif; ?>
                <tr class="border-top border-secondary border-opacity-25">
                  <td colspan="4" class="text-end fs-5 fw-bold text-white">Total Tagihan Bersih :</td>
                  <td class="text-end fs-5 fw-black text-danger"><?= formatRupiah($trx['total_bayar']) ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Riwayat Pembayaran Terkait -->
      <div class="col-12">
        <div class="ms-card">
          <div class="ms-card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-white mb-0">Riwayat Pembayaran & Angsuran</h6>
            <span class="badge bg-dark border border-secondary text-white-50"><?= count($payments) ?> Entri Pembayaran</span>
          </div>
          <div class="table-responsive">
            <table class="table table-custom-dark mb-0">
              <thead>
                <tr>
                  <th>No. Pembayaran</th>
                  <th>Tanggal Bayar</th>
                  <th>Jumlah Bayar</th>
                  <th>Metode</th>
                  <th>Uang Muka (DP)</th>
                  <th>Tenor & Cicilan</th>
                  <th>Sisa Bayar</th>
                  <th>Status</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($payments)): ?>
                  <tr>
                    <td colspan="9" class="text-center py-4 text-muted">Belum ada catatan pembayaran.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($payments as $p): ?>
                    <tr>
                      <td class="font-monospace fw-bold text-white"><?= htmlspecialchars($p['no_pembayaran']) ?></td>
                      <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></td>
                      <td class="fw-bold text-success"><?= formatRupiah($p['jumlah_bayar']) ?></td>
                      <td><?= htmlspecialchars($p['metode_pembayaran']) ?></td>
                      <td><?= $p['dp'] > 0 ? formatRupiah($p['dp']) : '-' ?></td>
                      <td>
                        <?php if ($p['tenor'] > 0): ?>
                          <?= $p['tenor'] ?> Bln &bull; <?= formatRupiah($p['cicilan_per_bulan']) ?>/bln
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td class="text-danger fw-semibold"><?= formatRupiah($p['sisa_bayar']) ?></td>
                      <td>
                        <span class="badge <?= $p['status'] === 'Lunas' ? 'badge-status-lunas' : 'badge-status-pending' ?>">
                          <?= htmlspecialchars($p['status']) ?>
                        </span>
                      </td>
                      <td class="text-muted small"><?= htmlspecialchars($p['keterangan'] ?: '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
