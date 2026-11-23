<?php
/**
 * Katalog Motor untuk Pelanggan & Konsumen
 */
require_once __DIR__ . '/../config/database.php';
// Boleh dibuka pelanggan atau publik jika login
$pdo = getDBConnection();
$pageTitle = 'Katalog Sepeda Motor - MotoShow';
$activeMenu = 'katalog';

$search = cleanInput($_GET['q'] ?? '');
$filterBrand = cleanInput($_GET['brand'] ?? '');
$filterTipe = cleanInput($_GET['tipe'] ?? '');
$filterMaxHarga = (float)($_GET['max_harga'] ?? 0);
$sortBy = cleanInput($_GET['sort'] ?? 'termurah');

$sql = "
    SELECT m.*, b.nama_merek 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (m.nama_motor LIKE ? OR b.nama_merek LIKE ? OR m.warna LIKE ?)";
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

if ($filterMaxHarga > 0) {
    $sql .= " AND m.harga <= ?";
    $params[] = $filterMaxHarga;
}

if ($sortBy === 'termahal') {
    $sql .= " ORDER BY m.harga DESC";
} elseif ($sortBy === 'terbaru') {
    $sql .= " ORDER BY m.tahun_produksi DESC, m.id DESC";
} else {
    $sql .= " ORDER BY m.harga ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$motors = $stmt->fetchAll();

$brands = $pdo->query("SELECT * FROM brands ORDER BY nama_merek ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
if (isLoggedIn()) {
    require_once __DIR__ . '/../includes/sidebar.php';
}
?>

<div class="main-content">
  <?php 
  if (isLoggedIn()) {
      require_once __DIR__ . '/../includes/navbar.php';
  } else {
      // Navbar sederhana untuk publik
      echo '
      <nav class="app-navbar justify-content-between px-4">
        <a href="' . base_url() . '" class="d-flex align-items-center gap-2 text-decoration-none">
          <img src="' . base_url('assets/img/logo.svg') . '" alt="MotoShow" height="34">
        </a>
        <div class="d-flex gap-2">
          <a href="' . base_url('auth/login.php') . '" class="btn btn-outline-ms btn-sm">Masuk</a>
          <a href="' . base_url('auth/register.php') . '" class="btn btn-primary-ms btn-sm">Daftar Akun</a>
        </div>
      </nav>';
  }
  ?>

  <div class="page-body">
    <?php renderFlash(); ?>

    <!-- Catalog Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Katalog Sepeda Motor</h4>
        <p class="text-muted small mb-0">Jelajahi seluruh varian motor unggulan dengan spesifikasi lengkap dan simulasi pembiayaan</p>
      </div>
      <div class="text-white-50 small">
        Menampilkan <strong><?= count($motors) ?></strong> unit motor
      </div>
    </div>

    <!-- Filter & Pencarian Box -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-3">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
              </span>
              <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari nama motor..." value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-md-2">
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
            <select name="max_harga" class="form-select form-select-ms">
              <option value="">Semua Rentang Harga</option>
              <option value="25000000" <?= $filterMaxHarga == 25000000 ? 'selected' : '' ?>>&le; Rp 25 Juta</option>
              <option value="40000000" <?= $filterMaxHarga == 40000000 ? 'selected' : '' ?>>&le; Rp 40 Juta</option>
              <option value="60000000" <?= $filterMaxHarga == 60000000 ? 'selected' : '' ?>>&le; Rp 60 Juta</option>
              <option value="100000000" <?= $filterMaxHarga == 100000000 ? 'selected' : '' ?>>&le; Rp 100 Juta</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="sort" class="form-select form-select-ms">
              <option value="termurah" <?= $sortBy === 'termurah' ? 'selected' : '' ?>>Harga: Termurah</option>
              <option value="termahal" <?= $sortBy === 'termahal' ? 'selected' : '' ?>>Harga: Tertinggi</option>
              <option value="terbaru" <?= $sortBy === 'terbaru' ? 'selected' : '' ?>>Tahun Terbaru</option>
            </select>
          </div>
          <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center p-2" title="Filter">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
            </button>
            <?php if (!empty($search) || !empty($filterBrand) || !empty($filterTipe) || $filterMaxHarga > 0): ?>
              <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-outline-ms p-2" title="Reset">
                <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Motor Cards Grid -->
    <div class="row g-4">
      <?php if (empty($motors)): ?>
        <div class="col-12 text-center py-5 text-muted">
          <i data-lucide="search-x" style="width: 50px; height: 50px; opacity: 0.3;" class="mb-2"></i>
          <h5>Tidak Ada Unit Motor yang Cocok</h5>
          <p class="small text-muted">Coba ubah kata kunci pencarian atau sesuaikan batas filter harga Anda.</p>
          <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-primary-ms btn-sm">Lihat Semua Motor</a>
        </div>
      <?php else: ?>
        <?php foreach ($motors as $m): ?>
          <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="motor-card">
              <div class="motor-card-img-wrapper">
                <span class="motor-card-brand-badge"><?= htmlspecialchars($m['nama_merek']) ?></span>
                <span class="motor-card-badge">
                  <?php if ($m['status'] === 'Tersedia'): ?>
                    <span class="badge badge-status-tersedia">Ready</span>
                  <?php elseif ($m['status'] === 'Habis'): ?>
                    <span class="badge badge-status-habis">Habis</span>
                  <?php else: ?>
                    <span class="badge badge-status-pending">Segera Hadir</span>
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
                  <span class="motor-spec-pill"><?= $m['tahun_produksi'] ?></span>
                  <span class="motor-spec-pill"><?= htmlspecialchars($m['warna']) ?></span>
                </div>

                <div class="mt-auto d-flex gap-2">
                  <a href="<?= base_url('pelanggan/detail_motor.php?id=' . $m['id']) ?>" class="btn btn-outline-ms btn-sm flex-fill justify-content-center">
                    <i data-lucide="info" style="width: 14px; height: 14px;"></i> Spesifikasi
                  </a>
                  <?php if ($m['status'] === 'Tersedia'): ?>
                    <a href="<?= base_url('pelanggan/pemesanan.php?motor_id=' . $m['id']) ?>" class="btn btn-primary-ms btn-sm flex-fill justify-content-center">
                      <i data-lucide="calendar-check" style="width: 14px; height: 14px;"></i> Pesan
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
