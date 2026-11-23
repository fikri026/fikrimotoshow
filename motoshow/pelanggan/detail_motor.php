<?php
/**
 * Detail dan Spesifikasi Motor - Pelanggan
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$pageTitle = 'Detail Motor - MotoShow';
$activeMenu = 'katalog';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT m.*, b.nama_merek, b.negara_asal, b.deskripsi as deskripsi_merek 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE m.id = ?
");
$stmt->execute([$id]);
$motor = $stmt->fetch();

if (!$motor) {
    setFlash('danger', 'Data unit motor tidak ditemukan.');
    header('Location: ' . base_url('pelanggan/katalog.php'));
    exit;
}

// Simulasi Cicilan Kredit Cepat (DP 20%, Tenor 12/24/36)
$dpSimulasi = $motor['harga'] * 0.20;
$sisaSimulasi = $motor['harga'] - $dpSimulasi;
$cicilan12 = round($sisaSimulasi / 12);
$cicilan24 = round($sisaSimulasi / 24);
$cicilan36 = round($sisaSimulasi / 36);

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
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
      <div>
        <a href="<?= base_url('pelanggan/katalog.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Katalog
        </a>
        <h3 class="fw-black text-white mb-0"><?= htmlspecialchars($motor['nama_motor']) ?></h3>
      </div>
      <div>
        <?php if ($motor['status'] === 'Tersedia'): ?>
          <a href="<?= base_url('pelanggan/pemesanan.php?motor_id=' . $motor['id']) ?>" class="btn btn-primary-ms px-4 py-2">
            <i data-lucide="calendar-check" style="width: 18px; height: 18px;"></i> Ajukan Pemesanan (Booking)
          </a>
        <?php else: ?>
          <button class="btn btn-outline-ms" disabled>Stok Sedang Habis</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="row g-4">
      <!-- Kolom Kiri: Galeri & Kartu Pembelian -->
      <div class="col-lg-5">
        <div class="ms-card mb-4 overflow-hidden">
          <div class="p-4 bg-dark text-center border-bottom border-secondary border-opacity-25 position-relative">
            <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2 <?= $motor['status'] === 'Tersedia' ? 'badge-status-tersedia' : 'badge-status-habis' ?>">
              <?= htmlspecialchars($motor['status']) ?> (Stok: <?= $motor['stok'] ?> Unit)
            </span>
            <img src="<?= getMotorImage($motor['foto']) ?>" alt="<?= htmlspecialchars($motor['nama_motor']) ?>" class="rounded img-fluid w-100" style="max-height: 320px; object-fit: contain;">
          </div>
          <div class="ms-card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <span class="text-muted small">Harga OTR Jakarta & Sekitarnya</span>
                <div class="fs-2 fw-black text-danger"><?= formatRupiah($motor['harga']) ?></div>
              </div>
            </div>

            <!-- Simulasi Cicilan Ringkas -->
            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-4">
              <div class="fw-bold text-white small mb-2 d-flex align-items-center gap-1">
                <i data-lucide="calculator" class="text-danger" style="width: 16px; height: 16px;"></i> Simulasi Kredit Ringan
              </div>
              <div class="row g-2 text-center small">
                <div class="col-4 border-end border-secondary border-opacity-25">
                  <div class="text-muted" style="font-size: 0.72rem;">12 BULAN</div>
                  <div class="fw-bold text-white"><?= formatRupiah($cicilan12) ?></div>
                </div>
                <div class="col-4 border-end border-secondary border-opacity-25">
                  <div class="text-muted" style="font-size: 0.72rem;">24 BULAN</div>
                  <div class="fw-bold text-danger"><?= formatRupiah($cicilan24) ?></div>
                </div>
                <div class="col-4">
                  <div class="text-muted" style="font-size: 0.72rem;">36 BULAN</div>
                  <div class="fw-bold text-white"><?= formatRupiah($cicilan36) ?></div>
                </div>
              </div>
              <div class="text-muted" style="font-size: 0.72rem; margin-top: 8px; text-align: center;">
                *Estimasi DP 20% (<?= formatRupiah($dpSimulasi) ?>) tanpa bunga tambahan.
              </div>
            </div>

            <h6 class="fw-bold text-white mb-2">Deskripsi Motor</h6>
            <p class="text-muted small lh-base mb-0">
              <?= nl2br(htmlspecialchars($motor['deskripsi'] ?: 'Unit motor resmi dengan garansi mesin pabrikan dan layanan purna jual showroom.')) ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Kolom Kanan: Detail Spesifikasi Lengkap -->
      <div class="col-lg-7">
        <!-- Rincian Mesin & Transmisi -->
        <div class="ms-card mb-4">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="gauge" class="text-danger" style="width: 18px; height: 18px;"></i>
              Spesifikasi Mesin & Performa
            </h6>
          </div>
          <div class="ms-card-body">
            <div class="spec-item"><span class="label">Merek & Pabrikan</span><span class="val"><?= htmlspecialchars($motor['nama_merek']) ?> (<?= htmlspecialchars($motor['negara_asal'] ?: 'Internasional') ?>)</span></div>
            <div class="spec-item"><span class="label">Tipe Motor</span><span class="val"><?= htmlspecialchars($motor['tipe_motor']) ?></span></div>
            <div class="spec-item"><span class="label">Tahun Pembuatan</span><span class="val"><?= $motor['tahun_produksi'] ?></span></div>
            <div class="spec-item"><span class="label">Kapasitas Silinder Mesin</span><span class="val"><?= $motor['kapasitas_mesin'] ?> CC</span></div>
            <div class="spec-item"><span class="label">Sistem Transmisi</span><span class="val"><?= htmlspecialchars($motor['jenis_transmisi']) ?></span></div>
            <div class="spec-item"><span class="label">Bahan Bakar</span><span class="val"><?= htmlspecialchars($motor['jenis_bahan_bakar']) ?></span></div>
            <div class="spec-item"><span class="label">Efisiensi / Konsumsi BBM</span><span class="val"><?= htmlspecialchars($motor['konsumsi_bbm'] ?: '40-45 km/liter') ?></span></div>
            <div class="spec-item"><span class="label">Kapasitas Tangki Bensin</span><span class="val"><?= htmlspecialchars($motor['kapasitas_tangki'] ?: '7.0 Liter') ?></span></div>
          </div>
        </div>

        <!-- Rangka & Dimensi -->
        <div class="ms-card mb-4">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="sliders" class="text-danger" style="width: 18px; height: 18px;"></i>
              Rangka, Dimensi & Pengereman
            </h6>
          </div>
          <div class="ms-card-body">
            <div class="spec-item"><span class="label">Sistem Pengereman</span><span class="val"><?= htmlspecialchars($motor['sistem_pengereman'] ?: 'Double Disc ABS') ?></span></div>
            <div class="spec-item"><span class="label">Sistem Suspensi</span><span class="val"><?= htmlspecialchars($motor['suspensi'] ?: 'Teleskopik Depan & Monoshock') ?></span></div>
            <div class="spec-item"><span class="label">Dimensi Kendaraan (P x L x T)</span><span class="val"><?= htmlspecialchars($motor['dimensi'] ?: '1.935 x 740 x 1.160 mm') ?></span></div>
            <div class="spec-item"><span class="label">Bobot / Berat Kosong</span><span class="val"><?= htmlspecialchars($motor['berat'] ?: '130 kg') ?></span></div>
            <div class="spec-item"><span class="label">Pilihan Warna Resmi</span><span class="val"><?= htmlspecialchars($motor['warna']) ?></span></div>
            <div class="spec-item"><span class="label">Status Unit Showroom</span><span class="val badge badge-status-tersedia"><?= htmlspecialchars($motor['status']) ?></span></div>
          </div>
        </div>

        <!-- Tombol Aksi Booking -->
        <?php if ($motor['status'] === 'Tersedia'): ?>
          <div class="p-4 rounded ms-card d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <div>
              <div class="fw-bold text-white">Tertarik Membawa Pulang Motor Ini?</div>
              <div class="text-muted small">Ajukan booking secara online sekarang dan amankan antrean unit Anda.</div>
            </div>
            <a href="<?= base_url('pelanggan/pemesanan.php?motor_id=' . $motor['id']) ?>" class="btn btn-primary-ms px-4 py-2 flex-shrink-0">
              <i data-lucide="calendar-check" style="width: 18px; height: 18px;"></i> Booking Unit Sekarang
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
