<?php
/**
 * Dashboard Petugas Kasir / Sales MotoShow
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Dashboard Petugas - MotoShow';
$activeMenu = 'dashboard';

// Statistik Petugas & Showroom
$today = date('Y-m-d');
$userId = $_SESSION['user_id'];

// Total transaksi yang dihandle petugas ini
$stmtTrx = $pdo->prepare("SELECT COUNT(*) as jml, COALESCE(SUM(total_bayar), 0) as omset FROM transactions WHERE user_id = ? AND status != 'Batal'");
$stmtTrx->execute([$userId]);
$myTrx = $stmtTrx->fetch();

// Total transaksi hari ini (showroom)
$stmtToday = $pdo->prepare("SELECT COUNT(*) as jml, COALESCE(SUM(total_bayar), 0) as omset FROM transactions WHERE DATE(tanggal) = ? AND status != 'Batal'");
$stmtToday->execute([$today]);
$todayTrx = $stmtToday->fetch();

// Total motor tersedia
$motorRow = $pdo->query("SELECT SUM(stok) as stok, COUNT(*) as model FROM motors WHERE status = 'Tersedia'")->fetch();
$totalMotor = (int)($motorRow['stok'] ?? 0);
$totalModel = (int)($motorRow['model'] ?? 0);

// Total pelanggan terdaftar
$totalPelanggan = (int)$pdo->query("SELECT COUNT(*) as jml FROM customers")->fetch()['jml'] ?? 0;

// Booking pending
$totalBooking = (int)$pdo->query("SELECT COUNT(*) as jml FROM bookings WHERE status = 'Menunggu Konfirmasi'")->fetch()['jml'] ?? 0;

// Transaksi terkini showroom
$stmtRecent = $pdo->query("
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.no_hp, u.nama_lengkap as nama_kasir 
    FROM transactions t 
    JOIN customers c ON t.customer_id = c.id 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.id DESC 
    LIMIT 6
");
$recentTrx = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <!-- Welcome Banner -->
    <div class="ms-card p-4 mb-4" style="background: radial-gradient(circle at right, rgba(239, 68, 68, 0.2), transparent 60%), #181d29;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-3 py-1 mb-2">Kasir & Sales Portal</span>
          <h4 class="fw-black text-white mb-1">Selamat Datang, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h4>
          <p class="text-muted small mb-0">Siap melayani pemesanan, transaksi kasir penjualan, dan verifikasi pelunasan konsumen.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('petugas/transaksi_tambah.php') ?>" class="btn btn-primary-ms">
            <i data-lucide="shopping-cart" style="width: 18px; height: 18px;"></i> Transaksi Baru
          </a>
          <a href="<?= base_url('admin/pelanggan/tambah.php') ?>" class="btn btn-outline-ms">
            <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Pelanggan Baru
          </a>
        </div>
      </div>
    </div>

    <!-- Quick Stats Cards (5 Indikator Operasional) -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
      <!-- Transaksi Saya -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Transaksi Saya</div>
              <div class="stat-value text-white"><?= number_format($myTrx['jml']) ?> <span class="fs-6 text-muted fw-normal">Trx</span></div>
              <span class="text-white-50 small">Order diproses Anda</span>
            </div>
            <div class="stat-icon blue">
              <i data-lucide="shopping-bag"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Omset Penjualan Saya -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Omset Saya</div>
              <div class="stat-value text-danger fs-5"><?= formatRupiah($myTrx['omset']) ?></div>
              <span class="text-white-50 small">Total penjualan sah</span>
            </div>
            <div class="stat-icon red">
              <i data-lucide="coins"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Transaksi Hari Ini -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Showroom Hari Ini</div>
              <div class="stat-value text-success"><?= number_format($todayTrx['jml']) ?> <span class="fs-6 text-muted fw-normal">Trx</span></div>
              <span class="text-white-50 small"><?= formatRupiah($todayTrx['omset']) ?></span>
            </div>
            <div class="stat-icon green">
              <i data-lucide="calendar"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Stok Motor Showroom -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Stok Motor</div>
              <div class="stat-value"><?= number_format($totalMotor) ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
              <span class="text-white-50 small"><?= $totalModel ?> model aktif</span>
            </div>
            <div class="stat-icon purple" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
              <i data-lucide="bike"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Pelanggan -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Total Konsumen</div>
              <div class="stat-value text-warning"><?= number_format($totalPelanggan) ?> <span class="fs-6 text-muted fw-normal">Orang</span></div>
              <span class="text-white-50 small"><?= $totalBooking ?> antrean booking</span>
            </div>
            <div class="stat-icon amber">
              <i data-lucide="users"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions & Transaksi Terkini -->
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0">Akses Cepat Operasional</h6>
          </div>
          <div class="ms-card-body d-flex flex-column gap-2">
            <a href="<?= base_url('petugas/transaksi_tambah.php') ?>" class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3 text-white text-decoration-none">
              <div class="stat-icon red" style="width: 40px; height: 40px;"><i data-lucide="shopping-bag" style="width: 20px; height: 20px;"></i></div>
              <div>
                <div class="fw-bold small">Kasir Penjualan Motor</div>
                <div class="text-muted" style="font-size: 0.78rem;">Buat nota order tunai/kredit</div>
              </div>
            </a>
            <a href="<?= base_url('admin/pembayaran/input.php') ?>" class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3 text-white text-decoration-none">
              <div class="stat-icon green" style="width: 40px; height: 40px;"><i data-lucide="credit-card" style="width: 20px; height: 20px;"></i></div>
              <div>
                <div class="fw-bold small">Input Pembayaran Cicilan</div>
                <div class="text-muted" style="font-size: 0.78rem;">Catat angsuran & kuitansi</div>
              </div>
            </a>
            <a href="<?= base_url('petugas/motor.php') ?>" class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3 text-white text-decoration-none">
              <div class="stat-icon blue" style="width: 40px; height: 40px;"><i data-lucide="bike" style="width: 20px; height: 20px;"></i></div>
              <div>
                <div class="fw-bold small">Cek Ketersediaan Stok</div>
                <div class="text-muted" style="font-size: 0.78rem;">Katalog & spesifikasi unit</div>
              </div>
            </a>
            <a href="<?= base_url('admin/pelanggan/tambah.php') ?>" class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3 text-white text-decoration-none">
              <div class="stat-icon amber" style="width: 40px; height: 40px;"><i data-lucide="users" style="width: 20px; height: 20px;"></i></div>
              <div>
                <div class="fw-bold small">Pendaftaran Konsumen</div>
                <div class="text-muted" style="font-size: 0.78rem;">Input data KTP & domisili</div>
              </div>
            </a>
            <a href="<?= base_url('petugas/riwayat.php') ?>" class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3 text-white text-decoration-none">
              <div class="stat-icon purple" style="width: 40px; height: 40px; background: rgba(168, 85, 247, 0.15); color: #c084fc;"><i data-lucide="history" style="width: 20px; height: 20px;"></i></div>
              <div>
                <div class="fw-bold small">Riwayat Penjualan</div>
                <div class="text-muted" style="font-size: 0.78rem;">Daftar transaksi kasir Anda</div>
              </div>
            </a>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="ms-card h-100">
          <div class="ms-card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-white mb-0">Transaksi Showroom Terkini</h6>
            <a href="<?= base_url('petugas/riwayat.php') ?>" class="text-danger small fw-semibold">Lihat Semua Riwayat</a>
          </div>
          <div class="table-responsive">
            <table class="table table-custom-dark mb-0">
              <thead>
                <tr>
                  <th>No. Transaksi</th>
                  <th>Pelanggan</th>
                  <th>Tanggal</th>
                  <th>Total Tagihan</th>
                  <th>Metode</th>
                  <th>Status</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentTrx)): ?>
                  <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi penjualan di showroom.</td></tr>
                <?php else: ?>
                  <?php foreach ($recentTrx as $rx): ?>
                    <tr>
                      <td class="font-monospace fw-bold text-white small">
                        <a href="<?= base_url('admin/transaksi/detail.php?id=' . $rx['id']) ?>" class="text-white text-decoration-underline">
                          <?= htmlspecialchars($rx['no_transaksi']) ?>
                        </a>
                        <?php if ($rx['user_id'] == $userId): ?>
                          <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 ms-1" style="font-size: 0.65rem;">Anda</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-white small">
                        <div><?= htmlspecialchars($rx['nama_pelanggan']) ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($rx['no_hp']) ?></div>
                      </td>
                      <td class="text-muted small"><?= date('d/m/Y', strtotime($rx['tanggal'])) ?></td>
                      <td class="fw-bold text-danger"><?= formatRupiah($rx['total_bayar']) ?></td>
                      <td><span class="badge bg-dark border border-secondary text-white-50"><?= htmlspecialchars($rx['metode_pembayaran']) ?></span></td>
                      <td>
                        <?php if ($rx['status'] === 'Lunas'): ?>
                          <span class="badge badge-status-lunas">Lunas</span>
                        <?php elseif ($rx['status'] === 'Cicilan Berjalan'): ?>
                          <span class="badge badge-status-cicilan">Cicilan</span>
                        <?php else: ?>
                          <span class="badge badge-status-pending"><?= htmlspecialchars($rx['status']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div class="btn-group btn-group-sm">
                          <a href="<?= base_url('admin/transaksi/detail.php?id=' . $rx['id']) ?>" class="btn btn-outline-ms p-1 px-2" title="Detail Transaksi">
                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                          </a>
                          <a href="<?= base_url('admin/transaksi/cetak.php?id=' . $rx['id']) ?>" target="_blank" class="btn btn-outline-ms p-1 px-2 text-info" title="Cetak Kuitansi / Faktur">
                            <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                          </a>
                        </div>
                      </td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
