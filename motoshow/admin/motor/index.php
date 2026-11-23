<?php
/**
 * Manajemen Data Motor - List
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Manajemen Data Motor - MotoShow';
$activeMenu = 'motor';

// Filter & Pencarian
$search = cleanInput($_GET['q'] ?? '');
$filterBrand = cleanInput($_GET['brand'] ?? '');
$filterTipe = cleanInput($_GET['tipe'] ?? '');
$filterStatus = cleanInput($_GET['status'] ?? '');

$sql = "
    SELECT m.*, b.nama_merek 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (m.nama_motor LIKE ? OR m.kode_motor LIKE ? OR m.warna LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterBrand)) {
    $sql .= " AND m.brand_id = ?";
    $params[] = $filterBrand;
}

if (!empty($filterTipe)) {
    $sql .= " AND m.tipe_motor = ?";
    $params[] = $filterTipe;
}

if (!empty($filterStatus)) {
    $sql .= " AND m.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$motors = $stmt->fetchAll();

// Daftar Brand untuk Filter
$brands = $pdo->query("SELECT * FROM brands ORDER BY nama_merek ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Data Motor</h4>
        <p class="text-muted small mb-0">Kelola katalog unit motor, stok fisik, spesifikasi teknis dan harga showroom</p>
      </div>
      <div>
        <a href="<?= base_url('admin/motor/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Tambah Unit Motor
        </a>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
              </span>
              <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari nama, kode, atau warna..." value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <select name="brand" class="form-select form-select-ms">
              <option value="">Semua Merek</option>
              <?php foreach ($brands as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $filterBrand == $b['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($b['nama_merek']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <select name="tipe" class="form-select form-select-ms">
              <option value="">Semua Tipe</option>
              <option value="Matic" <?= $filterTipe === 'Matic' ? 'selected' : '' ?>>Matic</option>
              <option value="Sport" <?= $filterTipe === 'Sport' ? 'selected' : '' ?>>Sport</option>
              <option value="Naked" <?= $filterTipe === 'Naked' ? 'selected' : '' ?>>Naked</option>
              <option value="Bebek" <?= $filterTipe === 'Bebek' ? 'selected' : '' ?>>Bebek</option>
              <option value="Trail" <?= $filterTipe === 'Trail' ? 'selected' : '' ?>>Trail</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-ms">
              <option value="">Semua Status</option>
              <option value="Tersedia" <?= $filterStatus === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
              <option value="Habis" <?= $filterStatus === 'Habis' ? 'selected' : '' ?>>Habis</option>
              <option value="Segera Hadir" <?= $filterStatus === 'Segera Hadir' ? 'selected' : '' ?>>Segera Hadir</option>
            </select>
          </div>
          <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center p-2" title="Terapkan Filter">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
            </button>
            <?php if (!empty($search) || !empty($filterBrand) || !empty($filterTipe) || !empty($filterStatus)): ?>
              <a href="<?= base_url('admin/motor/index.php') ?>" class="btn btn-outline-ms p-2" title="Reset Filter">
                <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data Motor -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th style="width: 80px;">Foto</th>
              <th>Kode & Nama Motor</th>
              <th>Merek / Tipe</th>
              <th>CC / Transmisi</th>
              <th>Harga OTR</th>
              <th>Stok</th>
              <th>Status</th>
              <th class="text-end" style="width: 140px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($motors)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i data-lucide="bike" class="mb-2" style="width: 36px; height: 36px; opacity: 0.3;"></i>
                  <div>Tidak ada data motor ditemukan.</div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($motors as $m): ?>
                <tr>
                  <td>
                    <img src="<?= getMotorImage($m['foto']) ?>" alt="<?= htmlspecialchars($m['nama_motor']) ?>" class="rounded" style="width: 65px; height: 45px; object-fit: cover;">
                  </td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($m['nama_motor']) ?></div>
                    <div class="text-muted small">
                      <span class="badge bg-dark border border-secondary border-opacity-25 text-white-50"><?= htmlspecialchars($m['kode_motor']) ?></span>
                      <span>&bull; <?= htmlspecialchars($m['warna']) ?> &bull; <?= $m['tahun_produksi'] ?></span>
                    </div>
                  </td>
                  <td>
                    <span class="fw-semibold text-white"><?= htmlspecialchars($m['nama_merek']) ?></span>
                    <div class="text-muted small"><?= htmlspecialchars($m['tipe_motor']) ?></div>
                  </td>
                  <td>
                    <div class="text-white small fw-semibold"><?= $m['kapasitas_mesin'] ?> CC</div>
                    <div class="text-muted small"><?= htmlspecialchars($m['jenis_transmisi']) ?></div>
                  </td>
                  <td class="fw-bold text-danger">
                    <?= formatRupiah($m['harga']) ?>
                  </td>
                  <td>
                    <?php if ($m['stok'] > 3): ?>
                      <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1"><?= $m['stok'] ?> Unit</span>
                    <?php elseif ($m['stok'] > 0): ?>
                      <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 px-2 py-1"><?= $m['stok'] ?> Unit (Menipis)</span>
                    <?php else: ?>
                      <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-2 py-1">Kosong</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($m['status'] === 'Tersedia'): ?>
                      <span class="badge badge-status-tersedia">Tersedia</span>
                    <?php elseif ($m['status'] === 'Habis'): ?>
                      <span class="badge badge-status-habis">Habis</span>
                    <?php else: ?>
                      <span class="badge badge-status-pending">Segera Hadir</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/motor/detail.php?id=' . $m['id']) ?>" class="btn btn-outline-ms p-1 px-2" title="Detail Spesifikasi">
                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                      </a>
                      <a href="<?= base_url('admin/motor/edit.php?id=' . $m['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-warning" title="Edit Motor">
                        <i data-lucide="edit-3" style="width: 15px; height: 15px;"></i>
                      </a>
                      <a href="<?= base_url('admin/motor/hapus.php?id=' . $m['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-danger" title="Hapus Motor" onclick="return confirmDelete(event, this.href, 'Hapus unit motor <?= addslashes($m['nama_motor']) ?>? Data yang terhubung dengan transaksi tidak dapat dihapus.')">
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
