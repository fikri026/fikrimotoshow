<?php
/**
 * Manajemen Data Pelanggan - List
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Manajemen Data Pelanggan - MotoShow';
$activeMenu = 'pelanggan';

$search = cleanInput($_GET['q'] ?? '');

$sql = "
    SELECT c.*, 
           COUNT(DISTINCT t.id) as total_transaksi,
           COALESCE(SUM(t.total_bayar), 0) as total_belanja
    FROM customers c
    LEFT JOIN transactions t ON c.id = t.customer_id AND t.status != 'Batal'
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.nama_lengkap LIKE ? OR c.nik LIKE ? OR c.no_hp LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Data Pelanggan</h4>
        <p class="text-muted small mb-0">Kelola informasi identitas, kontak, dan riwayat belanja pelanggan MotoShow</p>
      </div>
      <div>
        <a href="<?= base_url('admin/pelanggan/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Tambah Pelanggan
        </a>
      </div>
    </div>

    <!-- Search Form -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
              </span>
              <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari nama, NIK, No. HP, atau email pelanggan..." value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary-ms">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i> Cari Data
            </button>
            <?php if (!empty($search)): ?>
              <a href="<?= base_url('admin/pelanggan/index.php') ?>" class="btn btn-outline-ms">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data Pelanggan -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Lengkap & NIK</th>
              <th>Kontak (HP & Email)</th>
              <th>Alamat</th>
              <th>Tgl Daftar</th>
              <th>Total Transaksi</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customers)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i data-lucide="users" class="mb-2" style="width: 36px; height: 36px; opacity: 0.3;"></i>
                  <div>Belum ada data pelanggan tercatat.</div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($customers as $c): ?>
                <tr>
                  <td class="text-muted fw-bold">#<?= $c['id'] ?></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($c['nama_lengkap']) ?></div>
                    <div class="text-muted small">NIK: <span class="font-monospace"><?= htmlspecialchars($c['nik']) ?></span></div>
                  </td>
                  <td>
                    <div class="text-white small">
                      <i data-lucide="phone" style="width: 13px; height: 13px; vertical-align: -1px;" class="text-danger me-1"></i>
                      <?= htmlspecialchars($c['no_hp']) ?>
                    </div>
                    <div class="text-muted small">
                      <i data-lucide="mail" style="width: 13px; height: 13px; vertical-align: -1px;" class="me-1"></i>
                      <?= htmlspecialchars($c['email'] ?: '-') ?>
                    </div>
                  </td>
                  <td class="small text-muted" style="max-width: 200px;">
                    <?= htmlspecialchars($c['alamat']) ?>
                  </td>
                  <td class="text-muted small">
                    <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                  </td>
                  <td>
                    <span class="badge bg-dark border border-secondary border-opacity-25 text-white fw-bold">
                      <?= $c['total_transaksi'] ?> Transaksi
                    </span>
                    <div class="text-success small fw-semibold mt-1">
                      <?= formatRupiah($c['total_belanja']) ?>
                    </div>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/pelanggan/edit.php?id=' . $c['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-warning" title="Edit">
                        <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
                      </a>
                      <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?= base_url('admin/pelanggan/hapus.php?id=' . $c['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-danger" title="Hapus" onclick="return confirmDelete(event, this.href, 'Hapus data pelanggan <?= addslashes($c['nama_lengkap']) ?>?')">
                          <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                        </a>
                      <?php endif; ?>
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
