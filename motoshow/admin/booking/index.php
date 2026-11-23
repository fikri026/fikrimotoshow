<?php
/**
 * Manajemen Pemesanan / Booking Motor
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Pemesanan Motor (Booking) - MotoShow';
$activeMenu = 'booking';

// Handle Update Status Booking
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $newStatus = cleanInput($_POST['status'] ?? '');
    if ($bookingId > 0 && in_array($newStatus, ['Menunggu Konfirmasi', 'Dikonfirmasi', 'Selesai', 'Dibatalkan'])) {
        $stmtU = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmtU->execute([$newStatus, $bookingId]);
        setFlash('success', 'Status booking berhasil diubah menjadi ' . $newStatus);
        header('Location: ' . base_url('admin/booking/index.php'));
        exit;
    }
}

// Filter Status
$filterStatus = cleanInput($_GET['status'] ?? '');

$sql = "
    SELECT b.*, c.nama_lengkap as nama_pelanggan, c.no_hp, c.email,
           m.nama_motor, m.harga, m.warna, m.foto, br.nama_merek
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN motors m ON b.motor_id = m.id
    JOIN brands br ON m.brand_id = br.id
    WHERE 1=1
";
$params = [];

if (!empty($filterStatus)) {
    $sql .= " AND b.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Pemesanan Unit Motor (Booking)</h4>
        <p class="text-muted small mb-0">Kelola reservasi dan antrean unit motor yang diajukan oleh calon pembeli</p>
      </div>
      <div>
        <a href="<?= base_url('admin/transaksi/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Buat Transaksi Langsung
        </a>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= base_url('admin/booking/index.php') ?>" class="btn <?= empty($filterStatus) ? 'btn-danger' : 'btn-outline-ms' ?> btn-sm">Semua Booking</a>
          <a href="<?= base_url('admin/booking/index.php?status=Menunggu+Konfirmasi') ?>" class="btn <?= $filterStatus === 'Menunggu Konfirmasi' ? 'btn-danger' : 'btn-outline-ms' ?> btn-sm">Menunggu Konfirmasi</a>
          <a href="<?= base_url('admin/booking/index.php?status=Dikonfirmasi') ?>" class="btn <?= $filterStatus === 'Dikonfirmasi' ? 'btn-danger' : 'btn-outline-ms' ?> btn-sm">Dikonfirmasi</a>
          <a href="<?= base_url('admin/booking/index.php?status=Selesai') ?>" class="btn <?= $filterStatus === 'Selesai' ? 'btn-danger' : 'btn-outline-ms' ?> btn-sm">Selesai</a>
          <a href="<?= base_url('admin/booking/index.php?status=Dibatalkan') ?>" class="btn <?= $filterStatus === 'Dibatalkan' ? 'btn-danger' : 'btn-outline-ms' ?> btn-sm">Dibatalkan</a>
        </div>
      </div>
    </div>

    <!-- Tabel Booking -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>No. Booking</th>
              <th>Tanggal</th>
              <th>Pemesan</th>
              <th>Unit Motor</th>
              <th>Rencana Pembayaran</th>
              <th>Catatan</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">Belum ada pengajuan booking motor.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookings as $b): ?>
                <tr>
                  <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($b['no_booking']) ?></td>
                  <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($b['tanggal_booking'])) ?></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($b['nama_pelanggan']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($b['no_hp']) ?></div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?= getMotorImage($b['foto']) ?>" alt="<?= htmlspecialchars($b['nama_motor']) ?>" class="rounded" style="width: 45px; height: 32px; object-fit: cover;">
                      <div>
                        <div class="fw-semibold text-white small"><?= htmlspecialchars($b['nama_motor']) ?></div>
                        <div class="text-danger small"><?= formatRupiah($b['harga']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-dark border border-secondary text-white"><?= htmlspecialchars($b['rencana_pembayaran']) ?></span>
                    <?php if ($b['rencana_pembayaran'] === 'Kredit'): ?>
                      <div class="text-muted small" style="font-size: 0.75rem;">DP: <?= formatRupiah($b['rencana_dp']) ?> &bull; <?= $b['rencana_tenor'] ?> Bln</div>
                    <?php endif; ?>
                  </td>
                  <td class="small text-muted" style="max-width: 180px;">
                    <?= htmlspecialchars($b['catatan'] ?: '-') ?>
                  </td>
                  <td>
                    <?php if ($b['status'] === 'Dikonfirmasi'): ?>
                      <span class="badge badge-status-tersedia">Dikonfirmasi</span>
                    <?php elseif ($b['status'] === 'Menunggu Konfirmasi'): ?>
                      <span class="badge badge-status-cicilan">Menunggu</span>
                    <?php elseif ($b['status'] === 'Selesai'): ?>
                      <span class="badge badge-status-lunas">Selesai</span>
                    <?php else: ?>
                      <span class="badge badge-status-batal">Dibatalkan</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <!-- Dropdown update status -->
                    <div class="dropdown d-inline-block">
                      <button class="btn btn-outline-ms btn-sm dropdown-toggle py-1 px-2" type="button" data-bs-toggle="dropdown">
                        Ubah Status
                      </button>
                      <ul class="dropdown-menu dropdown-menu-dark-custom dropdown-menu-end">
                        <li>
                          <form method="POST" action="">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="status" value="Dikonfirmasi">
                            <button type="submit" class="dropdown-item text-success">
                              <i data-lucide="check" style="width: 14px; height: 14px;"></i> Konfirmasi Booking
                            </button>
                          </form>
                        </li>
                        <li>
                          <form method="POST" action="">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="status" value="Selesai">
                            <button type="submit" class="dropdown-item text-primary">
                              <i data-lucide="check-circle-2" style="width: 14px; height: 14px;"></i> Tandai Selesai
                            </button>
                          </form>
                        </li>
                        <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                        <li>
                          <form method="POST" action="">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="status" value="Dibatalkan">
                            <button type="submit" class="dropdown-item text-danger">
                              <i data-lucide="x" style="width: 14px; height: 14px;"></i> Batalkan Booking
                            </button>
                          </form>
                        </li>
                      </ul>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
