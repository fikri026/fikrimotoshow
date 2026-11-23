<?php
/**
 * Detail dan Spesifikasi Motor
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Detail Spesifikasi Motor - MotoShow';
$activeMenu = 'motor';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT m.*, b.nama_merek, b.negara_asal 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE m.id = ?
");
$stmt->execute([$id]);
$motor = $stmt->fetch();

if (!$motor) {
    setFlash('danger', 'Data motor tidak ditemukan!');
    header('Location: ' . base_url('admin/motor/index.php'));
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
      <div>
        <a href="<?= base_url($_SESSION['user_role'] === 'admin' ? 'admin/motor/index.php' : 'petugas/motor.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Katalog Motor
        </a>
        <h4 class="fw-black text-white mb-0"><?= htmlspecialchars($motor['nama_motor']) ?></h4>
      </div>
      <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <div class="d-flex gap-2">
          <a href="<?= base_url('admin/motor/edit.php?id=' . $motor['id']) ?>" class="btn btn-primary-ms">
            <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i> Edit Motor
          </a>
        </div>
      <?php endif; ?>
    </div>

    <div class="row g-4">
      <!-- Kolom Kiri: Galeri Foto & Quick Info -->
      <div class="col-lg-5">
        <div class="ms-card mb-4 overflow-hidden">
          <div class="p-3 bg-dark text-center border-bottom border-secondary border-opacity-25 position-relative">
            <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2 <?= $motor['status'] === 'Tersedia' ? 'badge-status-tersedia' : 'badge-status-habis' ?>">
              <?= htmlspecialchars($motor['status']) ?>
            </span>
            <img src="<?= getMotorImage($motor['foto']) ?>" alt="<?= htmlspecialchars($motor['nama_motor']) ?>" class="rounded img-fluid w-100" style="max-height: 320px; object-fit: contain;">
          </div>
          <div class="ms-card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <span class="text-muted small">Harga Rekomendasi (OTR)</span>
                <div class="fs-3 fw-black text-danger"><?= formatRupiah($motor['harga']) ?></div>
              </div>
              <div class="text-end">
                <span class="text-muted small">Stok Unit</span>
                <div class="fs-4 fw-bold text-white"><?= $motor['stok'] ?> <span class="fs-6 fw-normal text-muted">Unit</span></div>
              </div>
            </div>

            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
              <div class="row text-center g-2">
                <div class="col-4 border-end border-secondary border-opacity-25">
                  <div class="text-muted" style="font-size: 0.75rem;">MEREK</div>
                  <div class="text-white fw-bold small"><?= htmlspecialchars($motor['nama_merek']) ?></div>
                </div>
                <div class="col-4 border-end border-secondary border-opacity-25">
                  <div class="text-muted" style="font-size: 0.75rem;">TIPE</div>
                  <div class="text-white fw-bold small"><?= htmlspecialchars($motor['tipe_motor']) ?></div>
                </div>
                <div class="col-4">
                  <div class="text-muted" style="font-size: 0.75rem;">TAHUN</div>
                  <div class="text-white fw-bold small"><?= $motor['tahun_produksi'] ?></div>
                </div>
              </div>
            </div>

            <p class="text-muted small mb-0 lh-base">
              <?= nl2br(htmlspecialchars($motor['deskripsi'] ?? 'Tidak ada catatan deskripsi tambahan untuk unit ini.')) ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Kolom Kanan: Lembar Spesifikasi Teknis Lengkap -->
      <div class="col-lg-7">
        <div class="ms-card mb-4">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="cpu" class="text-danger" style="width: 18px; height: 18px;"></i>
              Spesifikasi Performa Mesin & Transmisi
            </h6>
          </div>
          <div class="ms-card-body">
            <div class="spec-item">
              <span class="label">Kapasitas Mesin (CC)</span>
              <span class="val"><?= $motor['kapasitas_mesin'] ?> CC</span>
            </div>
            <div class="spec-item">
              <span class="label">Sistem Transmisi</span>
              <span class="val"><?= htmlspecialchars($motor['jenis_transmisi']) ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Jenis Bahan Bakar</span>
              <span class="val"><?= htmlspecialchars($motor['jenis_bahan_bakar']) ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Konsumsi Bahan Bakar (Rata-rata)</span>
              <span class="val"><?= htmlspecialchars($motor['konsumsi_bbm'] ?: '40-45 km/liter') ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Kapasitas Tangki Bahan Bakar</span>
              <span class="val"><?= htmlspecialchars($motor['kapasitas_tangki'] ?: '7.0 Liter') ?></span>
            </div>
          </div>
        </div>

        <div class="ms-card mb-4">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="shield" class="text-danger" style="width: 18px; height: 18px;"></i>
              Rangka, Dimensi & Pengereman
            </h6>
          </div>
          <div class="ms-card-body">
            <div class="spec-item">
              <span class="label">Sistem Pengereman</span>
              <span class="val"><?= htmlspecialchars($motor['sistem_pengereman'] ?: 'Double Disc ABS') ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Sistem Suspensi</span>
              <span class="val"><?= htmlspecialchars($motor['suspensi'] ?: 'Teleskopik Depan & Monoshock Belakang') ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Dimensi (P x L x T)</span>
              <span class="val"><?= htmlspecialchars($motor['dimensi'] ?: '1.935 x 740 x 1.160 mm') ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Berat Kosong Motor</span>
              <span class="val"><?= htmlspecialchars($motor['berat'] ?: '130 kg') ?></span>
            </div>
            <div class="spec-item">
              <span class="label">Pilihan Warna Unit</span>
              <span class="val"><?= htmlspecialchars($motor['warna']) ?></span>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
