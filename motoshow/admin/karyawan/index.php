<?php
/**
 * Manajemen Karyawan - List
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Manajemen Data Karyawan - MotoShow';
$activeMenu = 'karyawan';

$search = cleanInput($_GET['q'] ?? '');
$filterJabatan = cleanInput($_GET['jabatan'] ?? '');

$sql = "SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.nama_lengkap LIKE ? OR e.nip LIKE ? OR e.no_hp LIKE ? OR e.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterJabatan)) {
    $sql .= " AND e.jabatan = ?";
    $params[] = $filterJabatan;
}

$sql .= " ORDER BY e.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Data Karyawan</h4>
        <p class="text-muted small mb-0">Kelola staf showroom, sales executive, dan petugas kasir MotoShow</p>
      </div>
      <div>
        <a href="<?= base_url('admin/karyawan/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Tambah Karyawan Baru
        </a>
      </div>
    </div>

    <!-- Search Form -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
              </span>
              <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari nama, NIP, atau kontak..." value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-md-4">
            <select name="jabatan" class="form-select form-select-ms">
              <option value="">Semua Jabatan</option>
              <option value="General Manager" <?= $filterJabatan === 'General Manager' ? 'selected' : '' ?>>General Manager</option>
              <option value="Sales & Kasir" <?= $filterJabatan === 'Sales & Kasir' ? 'selected' : '' ?>>Sales & Kasir</option>
              <option value="Sales Executive" <?= $filterJabatan === 'Sales Executive' ? 'selected' : '' ?>>Sales Executive</option>
              <option value="Customer Relations" <?= $filterJabatan === 'Customer Relations' ? 'selected' : '' ?>>Customer Relations</option>
              <option value="Mekanik Ahli" <?= $filterJabatan === 'Mekanik Ahli' ? 'selected' : '' ?>>Mekanik Ahli</option>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary-ms">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($filterJabatan)): ?>
              <a href="<?= base_url('admin/karyawan/index.php') ?>" class="btn btn-outline-ms">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data Karyawan -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>NIP</th>
              <th>Nama Karyawan</th>
              <th>Jabatan</th>
              <th>Kontak (HP & Email)</th>
              <th>Status</th>
              <th>Akun Login</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($employees)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">Belum ada data karyawan.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($employees as $e): ?>
                <tr>
                  <td><span class="badge bg-dark border border-secondary border-opacity-25 text-white"><?= htmlspecialchars($e['nip']) ?></span></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($e['nama_lengkap']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($e['alamat'] ?: '-') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 px-2 py-1">
                      <?= htmlspecialchars($e['jabatan']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="text-white small"><?= htmlspecialchars($e['no_hp']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($e['email'] ?: '-') ?></div>
                  </td>
                  <td>
                    <?php if ($e['status'] === 'Aktif'): ?>
                      <span class="badge badge-status-tersedia">Aktif</span>
                    <?php elseif ($e['status'] === 'Cuti'): ?>
                      <span class="badge badge-status-cicilan">Cuti</span>
                    <?php else: ?>
                      <span class="badge badge-status-batal">Nonaktif</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($e['user_id']): ?>
                      <span class="text-success small fw-semibold">
                        <i data-lucide="check" style="width: 14px; height: 14px; vertical-align: -2px;"></i> Terhubung (<?= htmlspecialchars($e['username']) ?>)
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">Tanpa Akun</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/karyawan/edit.php?id=' . $e['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-warning" title="Edit">
                        <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
                      </a>
                      <a href="<?= base_url('admin/karyawan/hapus.php?id=' . $e['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-danger" title="Hapus" onclick="return confirmDelete(event, this.href, 'Hapus karyawan <?= addslashes($e['nama_lengkap']) ?>?')">
                        <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
