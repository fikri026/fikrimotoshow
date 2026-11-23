<?php
/**
 * Manajemen Merek Motor - List & Tambah Modal
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Manajemen Merek Motor - MotoShow';
$activeMenu = 'merek';

// Ambil merek beserta jumlah model motor yang terdaftar
$sql = "
    SELECT b.*, COUNT(m.id) as total_motor 
    FROM brands b 
    LEFT JOIN motors m ON b.id = m.brand_id 
    GROUP BY b.id 
    ORDER BY b.nama_merek ASC
";
$brands = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Merek Motor</h4>
        <p class="text-muted small mb-0">Kelola daftar pabrikan motor yang bekerja sama dengan MotoShow</p>
      </div>
      <div>
        <a href="<?= base_url('admin/merek/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Tambah Merek Baru
        </a>
      </div>
    </div>

    <div class="row g-4">
      <?php foreach ($brands as $b): ?>
        <div class="col-md-6 col-lg-4">
          <div class="ms-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-2 py-1">
                  <?= htmlspecialchars($b['negara_asal'] ?: 'Internasional') ?>
                </span>
                <span class="badge bg-dark border border-secondary border-opacity-25 text-white-50">
                  <?= $b['total_motor'] ?> Model Unit
                </span>
              </div>
              <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars($b['nama_merek']) ?></h5>
              <p class="text-muted small mb-4 lh-sm">
                <?= htmlspecialchars($b['deskripsi'] ?: 'Tidak ada deskripsi singkat untuk merek ini.') ?>
              </p>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-10">
              <a href="<?= base_url('admin/motor/index.php?brand=' . $b['id']) ?>" class="text-white-50 small d-inline-flex align-items-center gap-1">
                <i data-lucide="bike" style="width: 14px; height: 14px;"></i> Lihat Motor
              </a>
              <div class="btn-group btn-group-sm">
                <a href="<?= base_url('admin/merek/edit.php?id=' . $b['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-warning" title="Edit">
                  <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
                </a>
                <a href="<?= base_url('admin/merek/hapus.php?id=' . $b['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-danger" title="Hapus" onclick="return confirmDelete(event, this.href, 'Hapus merek <?= addslashes($b['nama_merek']) ?>?')">
                  <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
