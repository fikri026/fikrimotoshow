<?php
/**
 * Data Pelanggan - Petugas
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Kelola Pelanggan - Petugas MotoShow';
$activeMenu = 'pelanggan';

$search = cleanInput($_GET['q'] ?? '');

$sql = "SELECT * FROM customers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nama_lengkap LIKE ? OR nik LIKE ? OR no_hp LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Data Pelanggan</h4>
        <p class="text-muted small mb-0">Input dan kelola identitas konsumen yang berkunjung ke showroom</p>
      </div>
      <div>
        <a href="<?= base_url('admin/pelanggan/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Input Konsumen Baru
        </a>
      </div>
    </div>

    <!-- Search Card -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-6">
            <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari nama pelanggan, NIK, atau No. HP..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary-ms">
              <i data-lucide="search" style="width: 16px; height: 16px;"></i> Cari
            </button>
            <?php if (!empty($search)): ?>
              <a href="<?= base_url('petugas/pelanggan.php') ?>" class="btn btn-outline-ms">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Pelanggan -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Lengkap & NIK</th>
              <th>Nomor HP / WA</th>
              <th>Email</th>
              <th>Alamat Domisili</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customers)): ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data pelanggan ditemukan.</td></tr>
            <?php else: ?>
              <?php foreach ($customers as $c): ?>
                <tr>
                  <td class="text-muted fw-bold">#<?= $c['id'] ?></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($c['nama_lengkap']) ?></div>
                    <div class="text-muted small">NIK: <?= htmlspecialchars($c['nik']) ?></div>
                  </td>
                  <td class="text-white small"><?= htmlspecialchars($c['no_hp']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($c['email'] ?: '-') ?></td>
                  <td class="small text-white-50" style="max-width: 250px;"><?= htmlspecialchars($c['alamat']) ?></td>
                  <td class="text-end">
                    <a href="<?= base_url('admin/pelanggan/edit.php?id=' . $c['id']) ?>" class="btn btn-outline-ms btn-sm p-1 px-2 text-warning" title="Edit">
                      <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
