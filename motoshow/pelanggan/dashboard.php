<?php
/**
 * Dashboard Pelanggan - MotoShow
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['pelanggan']);

$pdo = getDBConnection();
$pageTitle = 'Dashboard Pelanggan - MotoShow';
$activeMenu = 'dashboard';

// Ambil data pelanggan yang terhubung dengan akun user ini
$stmtCust = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmtCust->execute([$_SESSION['user_id']]);
$customer = $stmtCust->fetch();
$customerId = $customer ? $customer['id'] : 0;

// Statistik Pelanggan
// 1. Total Booking
$stmtBkg = $pdo->prepare("SELECT COUNT(*) as jml FROM bookings WHERE customer_id = ?");
$stmtBkg->execute([$customerId]);
$totalBooking = $stmtBkg->fetch()['jml'] ?? 0;

// 2. Total Pembelian
$stmtTrx = $pdo->prepare("SELECT COUNT(*) as jml, COALESCE(SUM(total_bayar), 0) as total_nominal FROM transactions WHERE customer_id = ? AND status != 'Batal'");
$stmtTrx->execute([$customerId]);
$trxData = $stmtTrx->fetch();
$totalTrx = $trxData['jml'] ?? 0;
$totalNominal = $trxData['total_nominal'] ?? 0;

// 3. Booking Terakhir
$stmtRecentBkg = $pdo->prepare("
    SELECT b.*, m.nama_motor, m.harga, m.foto, br.nama_merek 
    FROM bookings b 
    JOIN motors m ON b.motor_id = m.id 
    JOIN brands br ON m.brand_id = br.id 
    WHERE b.customer_id = ? 
    ORDER BY b.id DESC 
    LIMIT 4
");
$stmtRecentBkg->execute([$customerId]);
$recentBookings = $stmtRecentBkg->fetchAll();

// 4. Rekomendasi Motor Populer
$featuredMotors = $pdo->query("
    SELECT m.*, b.nama_merek 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE m.status = 'Tersedia' 
    ORDER BY m.id ASC 
    LIMIT 3
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <!-- Banner Sambutan Pelanggan -->
    <div class="ms-card p-4 mb-4 position-relative overflow-hidden" style="background: radial-gradient(circle at 90% 10%, rgba(239, 68, 68, 0.25) 0%, transparent 60%), #161b27;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-3 py-1 mb-2">Member Portal MotoShow</span>
          <h4 class="fw-black text-white mb-1">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h4>
          <p class="text-muted small mb-0">Temukan motor impian Anda dengan jaminan unit resmi, promo bunga rendah, dan proses cepat.</p>
        </div>
        <div>
          <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-primary-ms">
            <i data-lucide="bike" style="width: 18px; height: 18px;"></i> Jelajahi Katalog Motor
          </a>
        </div>
      </div>
    </div>

    <!-- KPI Ringkasan Pelanggan -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Pemesanan (Booking)</div>
              <div class="stat-value text-white"><?= number_format($totalBooking) ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
              <span class="text-white-50 small">Antrean booking Anda</span>
            </div>
            <div class="stat-icon red"><i data-lucide="calendar-check"></i></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Unit Motor Dimiliki</div>
              <div class="stat-value text-success"><?= number_format($totalTrx) ?> <span class="fs-6 text-muted fw-normal">Transaksi</span></div>
              <span class="text-white-50 small">Pembelian selesai</span>
            </div>
            <div class="stat-icon green"><i data-lucide="award"></i></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Akumulasi Belanja</div>
              <div class="stat-value text-danger fs-5"><?= formatRupiah($totalNominal) ?></div>
              <span class="text-white-50 small">Total transaksi terverifikasi</span>
            </div>
            <div class="stat-icon amber"><i data-lucide="wallet"></i></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Terbaru & Rekomendasi Motor -->
    <div class="row g-4">
      <!-- Daftar Booking Terkini -->
      <div class="col-lg-7">
        <div class="ms-card h-100">
          <div class="ms-card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-white mb-0">Status Pemesanan (Booking) Anda</h6>
            <a href="<?= base_url('pelanggan/pemesanan.php') ?>" class="text-danger small fw-semibold">Lihat Semua</a>
          </div>
          <div class="table-responsive">
            <table class="table table-custom-dark mb-0">
              <thead>
                <tr>
                  <th>No. Booking</th>
                  <th>Motor</th>
                  <th>Tanggal</th>
                  <th>Metode</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentBookings)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i data-lucide="inbox" style="width: 36px; height: 36px; opacity: 0.3;" class="mb-2"></i>
                      <div>Anda belum memiliki antrean booking motor.</div>
                      <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-outline-ms btn-sm mt-2">Pilih Motor Sekarang</a>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentBookings as $rb): ?>
                    <tr>
                      <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($rb['no_booking']) ?></td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="<?= getMotorImage($rb['foto']) ?>" alt="<?= htmlspecialchars($rb['nama_motor']) ?>" class="rounded" style="width: 40px; height: 28px; object-fit: cover;">
                          <div>
                            <div class="fw-semibold text-white small"><?= htmlspecialchars($rb['nama_motor']) ?></div>
                            <div class="text-danger small" style="font-size: 0.75rem;"><?= formatRupiah($rb['harga']) ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="text-muted small"><?= date('d/m/Y', strtotime($rb['tanggal_booking'])) ?></td>
                      <td><span class="badge bg-dark border border-secondary text-white-50"><?= htmlspecialchars($rb['rencana_pembayaran']) ?></span></td>
                      <td>
                        <?php if ($rb['status'] === 'Dikonfirmasi'): ?>
                          <span class="badge badge-status-tersedia">Dikonfirmasi</span>
                        <?php elseif ($rb['status'] === 'Menunggu Konfirmasi'): ?>
                          <span class="badge badge-status-cicilan">Menunggu</span>
                        <?php elseif ($rb['status'] === 'Selesai'): ?>
                          <span class="badge badge-status-lunas">Selesai</span>
                        <?php else: ?>
                          <span class="badge badge-status-batal">Dibatalkan</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Rekomendasi Motor Unggulan -->
      <div class="col-lg-5">
        <div class="ms-card h-100">
          <div class="ms-card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-white mb-0">Unit Pilihan Showroom</h6>
            <a href="<?= base_url('pelanggan/katalog.php') ?>" class="text-danger small fw-semibold">Buka Katalog</a>
          </div>
          <div class="ms-card-body p-0">
            <div class="list-group list-group-flush bg-transparent">
              <?php foreach ($featuredMotors as $fm): ?>
                <div class="list-group-item bg-transparent border-secondary border-opacity-10 p-3 d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <img src="<?= getMotorImage($fm['foto']) ?>" alt="<?= htmlspecialchars($fm['nama_motor']) ?>" class="rounded" style="width: 60px; height: 42px; object-fit: cover;">
                    <div>
                      <div class="fw-bold text-white small"><?= htmlspecialchars($fm['nama_motor']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($fm['nama_merek']) ?> &bull; <?= $fm['kapasitas_mesin'] ?> CC</div>
                      <div class="text-danger fw-bold small"><?= formatRupiah($fm['harga']) ?></div>
                    </div>
                  </div>
                  <a href="<?= base_url('pelanggan/detail_motor.php?id=' . $fm['id']) ?>" class="btn btn-outline-ms btn-sm p-1 px-2">
                    <i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
