<?php
/**
 * Katalog & Cek Stok Motor - Petugas
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Katalog & Stok Motor - Petugas MotoShow';
$activeMenu = 'motor';

$search = cleanInput($_GET['q'] ?? '');
$filterBrand = cleanInput($_GET['brand'] ?? '');
$filterTipe = cleanInput($_GET['tipe'] ?? '');

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

$sql .= " ORDER BY m.nama_motor ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$motors = $stmt->fetchAll();

$brands = $pdo->query("SELECT * FROM brands ORDER BY nama_merek ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Daftar & Ketersediaan Stok Motor</h4>
        <p class="text-muted small mb-0">Informasi spesifikasi teknis dan stok unit fisik untuk panduan penjualan</p>
      </div>
      <div>
        <a href="<?= base_url('petugas/transaksi_tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="shopping-cart" style="width: 18px; height: 18px;"></i> Jual Unit Ini
        </a>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-5">
            <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari nama motor, kode, atau warna..." value="<?= htmlspecialchars($search) ?>">
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
          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($filterBrand) || !empty($filterTipe)): ?>
              <a href="<?= base_url('petugas/motor.php') ?>" class="btn btn-outline-ms" title="Reset">
                <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Grid Kartu Motor -->
    <div class="row g-4">
      <?php if (empty($motors)): ?>
        <div class="col-12 text-center py-5 text-muted">
          <i data-lucide="bike" style="width: 48px; height: 48px; opacity: 0.3;" class="mb-2"></i>
          <div>Unit motor yang Anda cari tidak ditemukan.</div>
        </div>
      <?php else: ?>
        <?php foreach ($motors as $m): ?>
          <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="motor-card">
              <div class="motor-card-img-wrapper">
                <span class="motor-card-brand-badge"><?= htmlspecialchars($m['nama_merek']) ?></span>
                <span class="motor-card-badge">
                  <?php if ($m['stok'] > 3): ?>
                    <span class="badge badge-status-tersedia">Stok: <?= $m['stok'] ?></span>
                  <?php elseif ($m['stok'] > 0): ?>
                    <span class="badge badge-status-cicilan">Sisa <?= $m['stok'] ?> Unit</span>
                  <?php else: ?>
                    <span class="badge badge-status-habis">Habis</span>
                  <?php endif; ?>
                </span>
                <img src="<?= getMotorImage($m['foto']) ?>" alt="<?= htmlspecialchars($m['nama_motor']) ?>">
              </div>
              <div class="motor-card-content">
                <h6 class="motor-title text-truncate" title="<?= htmlspecialchars($m['nama_motor']) ?>">
                  <?= htmlspecialchars($m['nama_motor']) ?>
                </h6>
                <div class="motor-price"><?= formatRupiah($m['harga']) ?></div>

                <div class="motor-spec-pills">
                  <span class="motor-spec-pill"><?= $m['kapasitas_mesin'] ?> CC</span>
                  <span class="motor-spec-pill"><?= htmlspecialchars($m['tipe_motor']) ?></span>
                  <span class="motor-spec-pill"><?= htmlspecialchars($m['warna']) ?></span>
                </div>

                <div class="mt-auto d-flex gap-2">
                  <a href="<?= base_url('admin/motor/detail.php?id=' . $m['id']) ?>" class="btn btn-outline-ms btn-sm flex-fill justify-content-center">
                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Spesifikasi
                  </a>
                  <?php if ($m['stok'] > 0): ?>
                    <a href="<?= base_url('petugas/transaksi_tambah.php?motor_id=' . $m['id']) ?>" class="btn btn-primary-ms btn-sm px-3" title="Pilih Unit untuk Dijual">
                      <i data-lucide="shopping-cart" style="width: 14px; height: 14px;"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
