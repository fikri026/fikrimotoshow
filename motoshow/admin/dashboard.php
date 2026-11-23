<?php
/**
 * Dashboard Admin MotoShow
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Dashboard Admin - MotoShow';
$activeMenu = 'dashboard';

// 1. Data Statistik Utama
// Total Motor Tersedia
$stmt = $pdo->query("SELECT SUM(stok) as total_stok, COUNT(*) as total_model FROM motors WHERE status = 'Tersedia'");
$motorStats = $stmt->fetch();
$totalMotorTersedia = (int)($motorStats['total_stok'] ?? 0);
$totalModelTersedia = (int)($motorStats['total_model'] ?? 0);

// Total Motor Terjual
$stmt = $pdo->query("
    SELECT COALESCE(SUM(td.jumlah), 0) as total_terjual 
    FROM transaction_details td
    JOIN transactions t ON td.transaction_id = t.id
    WHERE t.status != 'Batal'
");
$totalMotorTerjual = (int)$stmt->fetch()['total_terjual'];

// Total Pelanggan
$stmt = $pdo->query("SELECT COUNT(*) as total_pelanggan FROM customers");
$totalPelanggan = (int)$stmt->fetch()['total_pelanggan'];

// Total Transaksi
$stmt = $pdo->query("SELECT COUNT(*) as total_trx FROM transactions WHERE status != 'Batal'");
$totalTransaksi = (int)$stmt->fetch()['total_trx'];

// Total Pendapatan
$stmt = $pdo->query("SELECT COALESCE(SUM(total_bayar), 0) as total_pendapatan FROM transactions WHERE status != 'Batal'");
$totalPendapatan = (float)$stmt->fetch()['total_pendapatan'];

// Total Booking Menunggu Konfirmasi
$stmt = $pdo->query("SELECT COUNT(*) as total_booking FROM bookings WHERE status = 'Menunggu Konfirmasi'");
$totalBookingPending = (int)$stmt->fetch()['total_booking'];

// 2. Data Transaksi Terbaru
$stmt = $pdo->query("
    SELECT t.*, c.nama_lengkap as nama_pelanggan, u.nama_lengkap as nama_kasir
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.id DESC
    LIMIT 6
");
$recentTransactions = $stmt->fetchAll();

// 3. Motor Paling Banyak Terjual
$stmt = $pdo->query("
    SELECT m.nama_motor, b.nama_merek, m.foto, m.harga,
           COALESCE(SUM(td.jumlah), 0) as total_terjual,
           COALESCE(SUM(td.subtotal), 0) as total_omset
    FROM motors m
    JOIN brands b ON m.brand_id = b.id
    JOIN transaction_details td ON m.id = td.motor_id
    JOIN transactions t ON td.transaction_id = t.id
    WHERE t.status != 'Batal'
    GROUP BY m.id, m.nama_motor, b.nama_merek, m.foto, m.harga
    ORDER BY total_terjual DESC
    LIMIT 5
");
$topSellingMotors = $stmt->fetchAll();

// 4. Data untuk Chart.js (Penjualan 6 Bulan Terakhir)
$salesChartLabels = [];
$salesChartData = [];
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    $salesChartLabels[] = $monthName;

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_bayar), 0) as omset
        FROM transactions
        WHERE status != 'Batal' AND DATE_FORMAT(tanggal, '%Y-%m') = ?
    ");
    $stmt->execute([$monthDate]);
    $salesChartData[] = (float)$stmt->fetch()['omset'];
}

// 5. Data Chart Metode Pembayaran
$stmt = $pdo->query("
    SELECT metode_pembayaran, COUNT(*) as jml 
    FROM transactions 
    WHERE status != 'Batal' 
    GROUP BY metode_pembayaran
");
$methodLabels = [];
$methodCounts = [];
while ($row = $stmt->fetch()) {
    $methodLabels[] = $row['metode_pembayaran'];
    $methodCounts[] = (int)$row['jml'];
}

// Motor dengan Stok Menipis (<= 3)
$stmt = $pdo->query("
    SELECT m.*, b.nama_merek 
    FROM motors m
    JOIN brands b ON m.brand_id = b.id
    WHERE m.stok <= 4
    ORDER BY m.stok ASC
    LIMIT 4
");
$lowStockMotors = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Dashboard Statistik Showroom</h4>
        <p class="text-muted small mb-0">Ringkasan performa penjualan, ketersediaan unit, dan analitik bisnis MotoShow</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('admin/transaksi/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Transaksi Baru
        </a>
        <a href="<?= base_url('admin/motor/tambah.php') ?>" class="btn btn-outline-ms">
          <i data-lucide="plus" style="width: 18px; height: 18px;"></i> Tambah Motor
        </a>
      </div>
    </div>

    <!-- Alert Banner Booking Baru -->
    <?php if ($totalBookingPending > 0): ?>
      <div class="alert bg-warning bg-opacity-10 border border-warning border-opacity-25 d-flex align-items-center justify-content-between p-3 rounded mb-4 text-white">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon amber" style="width: 42px; height: 42px;">
            <i data-lucide="bell-ring" style="width: 22px; height: 22px;"></i>
          </div>
          <div>
            <div class="fw-bold text-warning">Ada <?= $totalBookingPending ?> Pemesanan Unit Baru!</div>
            <div class="small text-white-50">Pelanggan telah mengajukan pemesanan motor secara online dan menunggu konfirmasi admin.</div>
          </div>
        </div>
        <a href="<?= base_url('admin/booking/index.php') ?>" class="btn btn-warning btn-sm text-dark fw-bold px-3">
          Verifikasi Sekarang <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
        </a>
      </div>
    <?php endif; ?>

    <!-- Statistik Cards (5 KPI Utama Sesuai Spesifikasi MotoShow) -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
      <!-- 1. Total Pendapatan -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Total Pendapatan</div>
              <div class="stat-value text-danger" style="font-size: 1.35rem;"><?= formatRupiah($totalPendapatan) ?></div>
              <span class="text-white-50 small">Akumulasi penjualan sah</span>
            </div>
            <div class="stat-icon red">
              <i data-lucide="coins"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Total Transaksi -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Total Transaksi</div>
              <div class="stat-value text-primary" style="font-size: 1.35rem;"><?= number_format($totalTransaksi) ?> <span class="fs-6 text-muted fw-normal">Trx</span></div>
              <span class="text-white-50 small">Nota order selesai</span>
            </div>
            <div class="stat-icon blue">
              <i data-lucide="receipt"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Total Motor Terjual -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Motor Terjual</div>
              <div class="stat-value text-success" style="font-size: 1.35rem;"><?= number_format($totalMotorTerjual) ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
              <span class="text-white-50 small">Telah diserahterimakan</span>
            </div>
            <div class="stat-icon green">
              <i data-lucide="check-circle-2"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Total Motor Tersedia -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Motor Tersedia</div>
              <div class="stat-value" style="font-size: 1.35rem;"><?= number_format($totalMotorTersedia) ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
              <span class="text-white-50 small"><?= $totalModelTersedia ?> model motor aktif</span>
            </div>
            <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
              <i data-lucide="bike"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. Total Pelanggan -->
      <div class="col">
        <div class="stat-card h-100">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-title">Total Pelanggan</div>
              <div class="stat-value text-warning" style="font-size: 1.35rem;"><?= number_format($totalPelanggan) ?> <span class="fs-6 text-muted fw-normal">Orang</span></div>
              <span class="text-white-50 small">Database konsumen</span>
            </div>
            <div class="stat-icon amber">
              <i data-lucide="users"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Grafik Section (Chart.js) -->
    <div class="row g-4 mb-4">
      <!-- Grafik Penjualan Bulanan -->
      <div class="col-lg-8">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <div>
              <h6 class="fw-bold text-white mb-0">Grafik Penjualan Motor</h6>
              <span class="text-muted small">Tren pendapatan per bulan (Rupiah)</span>
            </div>
            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2 py-1">Realtime</span>
          </div>
          <div class="ms-card-body">
            <div style="position: relative; height: 280px; width: 100%;">
              <canvas id="salesChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Grafik Distribusi Metode Pembayaran -->
      <div class="col-lg-4">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <div>
              <h6 class="fw-bold text-white mb-0">Metode Pembayaran</h6>
              <span class="text-muted small">Proporsi Tunai, Transfer & Kredit</span>
            </div>
          </div>
          <div class="ms-card-body d-flex flex-column justify-content-center align-items-center">
            <div style="position: relative; height: 210px; width: 100%;">
              <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="text-center mt-3 text-muted small">
              Data transaksi lunas & cicilan berjalan
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row: Motor Terlaris & Transaksi Terbaru -->
    <div class="row g-4 mb-4">
      <!-- Motor Terlaris -->
      <div class="col-lg-5">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0">Motor Paling Banyak Terjual</h6>
            <a href="<?= base_url('admin/motor/index.php') ?>" class="text-danger small fw-semibold">Lihat Semua</a>
          </div>
          <div class="ms-card-body p-0">
            <?php if (empty($topSellingMotors)): ?>
              <div class="p-4 text-center text-muted small">Belum ada data penjualan tercatat.</div>
            <?php else: ?>
              <div class="list-group list-group-flush bg-transparent">
                <?php foreach ($topSellingMotors as $idx => $m): ?>
                  <div class="list-group-item bg-transparent border-secondary border-opacity-10 py-3 px-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                      <span class="badge rounded-circle bg-dark border border-secondary border-opacity-25 text-white fw-bold d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                        #<?= $idx + 1 ?>
                      </span>
                      <img src="<?= getMotorImage($m['foto']) ?>" alt="<?= htmlspecialchars($m['nama_motor']) ?>" class="rounded" style="width: 50px; height: 35px; object-fit: cover;">
                      <div>
                        <div class="text-white fw-semibold small"><?= htmlspecialchars($m['nama_motor']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($m['nama_merek']) ?> &bull; <?= formatRupiah($m['harga']) ?></div>
                      </div>
                    </div>
                    <div class="text-end">
                      <div class="text-success fw-bold small"><?= $m['total_terjual'] ?> Unit</div>
                      <div class="text-white-50" style="font-size: 0.75rem;"><?= formatRupiah($m['total_omset']) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Transaksi Penjualan Terbaru -->
      <div class="col-lg-7">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0">Transaksi Penjualan Terbaru</h6>
            <a href="<?= base_url('admin/transaksi/index.php') ?>" class="text-danger small fw-semibold">Lihat Semua Transaksi</a>
          </div>
          <div class="table-responsive">
            <table class="table table-custom-dark mb-0">
              <thead>
                <tr>
                  <th>No. Transaksi</th>
                  <th>Pelanggan</th>
                  <th>Tanggal</th>
                  <th>Total Bayar</th>
                  <th>Metode</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentTransactions)): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentTransactions as $trx): ?>
                    <tr>
                      <td class="fw-bold text-white">
                        <a href="<?= base_url('admin/transaksi/detail.php?id=' . $trx['id']) ?>" class="text-white text-decoration-underline">
                          <?= htmlspecialchars($trx['no_transaksi']) ?>
                        </a>
                      </td>
                      <td><?= htmlspecialchars($trx['nama_pelanggan']) ?></td>
                      <td class="text-muted small"><?= date('d/m/Y', strtotime($trx['tanggal'])) ?></td>
                      <td class="fw-bold text-white"><?= formatRupiah($trx['total_bayar']) ?></td>
                      <td>
                        <span class="badge bg-dark border border-secondary border-opacity-25 text-white-50">
                          <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($trx['status'] === 'Lunas'): ?>
                          <span class="badge badge-status-lunas">Lunas</span>
                        <?php elseif ($trx['status'] === 'Cicilan Berjalan'): ?>
                          <span class="badge badge-status-cicilan">Cicilan</span>
                        <?php elseif ($trx['status'] === 'Pending'): ?>
                          <span class="badge badge-status-pending">Pending</span>
                        <?php else: ?>
                          <span class="badge badge-status-batal">Batal</span>
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
    </div>

    <!-- Alert Stok Menipis -->
    <?php if (!empty($lowStockMotors)): ?>
      <div class="ms-card mb-4">
        <div class="ms-card-header bg-danger bg-opacity-10 border-danger border-opacity-25">
          <div class="d-flex align-items-center gap-2 text-danger fw-bold">
            <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
            <span>Peringatan Stok Motor Menipis</span>
          </div>
          <span class="small text-danger">Harap segera lakukan pemesanan restock unit</span>
        </div>
        <div class="ms-card-body">
          <div class="row g-3">
            <?php foreach ($lowStockMotors as $lm): ?>
              <div class="col-sm-6 col-md-3">
                <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3">
                  <img src="<?= getMotorImage($lm['foto']) ?>" alt="<?= htmlspecialchars($lm['nama_motor']) ?>" class="rounded" style="width: 50px; height: 40px; object-fit: cover;">
                  <div class="overflow-hidden">
                    <div class="text-white fw-bold text-truncate small"><?= htmlspecialchars($lm['nama_motor']) ?></div>
                    <div class="text-danger fw-bold small">Sisa Stok: <?= $lm['stok'] ?> unit</div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

<!-- Chart.js Script (Local Bundle + CDN Fallback) -->
<script src="<?= base_url('assets/js/chart.umd.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
      console.warn('Pustaka Chart.js tidak dapat dimuat.');
      return;
    }

    // 1. Line / Area Chart Tren Penjualan Bulanan
    const canvasSales = document.getElementById('salesChart');
    if (canvasSales) {
      const ctxSales = canvasSales.getContext('2d');
      const gradient = ctxSales.createLinearGradient(0, 0, 0, 280);
      gradient.addColorStop(0, 'rgba(239, 68, 68, 0.45)');
      gradient.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

      new Chart(ctxSales, {
        type: 'line',
        data: {
          labels: <?= json_encode($salesChartLabels) ?>,
          datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode($salesChartData) ?>,
            borderColor: '#ef4444',
            borderWidth: 3,
            backgroundColor: gradient,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#ef4444',
            pointBorderColor: '#ffffff',
            pointRadius: 5,
            pointHoverRadius: 7
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#181d29',
              titleColor: '#ffffff',
              bodyColor: '#cbd5e1',
              borderColor: 'rgba(255,255,255,0.1)',
              borderWidth: 1,
              callbacks: {
                label: function(context) {
                  return ' Pendapatan: Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                }
              }
            }
          },
          scales: {
            x: {
              grid: { color: 'rgba(255, 255, 255, 0.05)' },
              ticks: { color: '#94a3b8' }
            },
            y: {
              grid: { color: 'rgba(255, 255, 255, 0.05)' },
              ticks: {
                color: '#94a3b8',
                callback: function(value) {
                  return 'Rp ' + (value / 1000000).toFixed(0) + ' Jt';
                }
              }
            }
          }
        }
      });
    }

    // 2. Doughnut Chart Metode Pembayaran
    const canvasMethod = document.getElementById('paymentMethodChart');
    if (canvasMethod) {
      const ctxMethod = canvasMethod.getContext('2d');
      new Chart(ctxMethod, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode(!empty($methodLabels) ? $methodLabels : ['Tunai', 'Transfer', 'Kredit']) ?>,
          datasets: [{
            data: <?= json_encode(!empty($methodCounts) ? $methodCounts : [1, 1, 1]) ?>,
            backgroundColor: ['#ef4444', '#3b82f6', '#10b981'],
            borderWidth: 2,
            borderColor: '#1c2232'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { color: '#cbd5e1', padding: 15, font: { size: 11 } }
            }
          },
          cutout: '70%'
        }
      });
    }
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
