<?php
/**
 * Manajemen Akun Pengguna Sistem
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Manajemen Akun Pengguna - MotoShow';
$activeMenu = 'pengguna';

$search = cleanInput($_GET['q'] ?? '');
$filterRole = cleanInput($_GET['role'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR nama_lengkap LIKE ? OR email LIKE ? OR no_hp LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterRole)) {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}

$sql .= " ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Akun Pengguna</h4>
        <p class="text-muted small mb-0">Kelola kredensial, hak akses Administrator, Petugas Kasir, dan Pelanggan</p>
      </div>
      <div>
        <a href="<?= base_url('admin/pengguna/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Buat Akun Baru
        </a>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
              </span>
              <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari username, nama, atau email..." value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <select name="role" class="form-select form-select-ms">
              <option value="">Semua Hak Akses (Role)</option>
              <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
              <option value="petugas" <?= $filterRole === 'petugas' ? 'selected' : '' ?>>Petugas</option>
              <option value="pelanggan" <?= $filterRole === 'pelanggan' ? 'selected' : '' ?>>Pelanggan</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-primary-ms">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($filterRole)): ?>
              <a href="<?= base_url('admin/pengguna/index.php') ?>" class="btn btn-outline-ms">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Pengguna -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Pengguna</th>
              <th>Username & Email</th>
              <th>Nomor Telepon</th>
              <th>Hak Akses (Role)</th>
              <th>Terdaftar</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td class="text-muted fw-bold">#<?= $u['id'] ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                      <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div class="fw-bold text-white"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                  </div>
                </td>
                <td>
                  <div class="text-white small fw-semibold font-monospace"><?= htmlspecialchars($u['username']) ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td class="small text-white-50"><?= htmlspecialchars($u['no_hp'] ?: '-') ?></td>
                <td>
                  <?php if ($u['role'] === 'admin'): ?>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1">ADMIN</span>
                  <?php elseif ($u['role'] === 'petugas'): ?>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 px-2 py-1">PETUGAS</span>
                  <?php else: ?>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1">PELANGGAN</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= base_url('admin/pengguna/edit.php?id=' . $u['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-warning" title="Edit Akun">
                      <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
                    </a>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                      <a href="<?= base_url('admin/pengguna/hapus.php?id=' . $u['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-danger" title="Hapus Akun" onclick="return confirmDelete(event, this.href, 'Hapus akun <?= addslashes($u['username']) ?>?')">
                        <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
