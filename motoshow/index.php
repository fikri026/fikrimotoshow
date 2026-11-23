<?php
/**
 * MotoShow - Halaman Beranda Utama Website Showroom
 */
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

// Ambil data motor unggulan untuk ditampilkan di beranda
$stmtMotors = $pdo->query("
    SELECT m.*, b.nama_merek 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE m.status = 'Tersedia' 
    ORDER BY m.id ASC 
    LIMIT 6
");
$featuredMotors = $stmtMotors->fetchAll();

// Ambil data merek motor
$brands = $pdo->query("SELECT * FROM brands ORDER BY nama_merek ASC")->fetchAll();

// Cek status login pengguna
$isUserLoggedIn = isLoggedIn();
$currentUserRole = $_SESSION['user_role'] ?? '';
$dashboardUrl = base_url('auth/login.php');

if ($isUserLoggedIn) {
    if ($currentUserRole === 'admin') {
        $dashboardUrl = base_url('admin/dashboard.php');
    } elseif ($currentUserRole === 'petugas') {
        $dashboardUrl = base_url('petugas/dashboard.php');
    } else {
        $dashboardUrl = base_url('pelanggan/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MotoShow - Showroom Motor Modern & Terpercaya</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    .public-navbar {
      background-color: rgba(15, 17, 23, 0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding: 0.85rem 0;
      position: sticky;
      top: 0;
      z-index: 1050;
    }
    .hero-glow {
      position: absolute;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(239, 68, 68, 0.22) 0%, transparent 70%);
      top: -100px;
      right: -100px;
      border-radius: 50%;
      pointer-events: none;
    }
    .brand-chip {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 9999px;
      padding: 0.6rem 1.4rem;
      color: #cbd5e1;
      font-weight: 600;
      font-size: 0.92rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .brand-chip:hover {
      background: rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
      color: #ffffff;
      transform: translateY(-2px);
    }
    .feature-card {
      background: #161b27;
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 14px;
      padding: 2rem;
      height: 100%;
      transition: all 0.25s ease;
    }
    .feature-card:hover {
      border-color: rgba(239, 68, 68, 0.3);
      transform: translateY(-4px);
    }
  </style>
</head>
<body style="background-color: #0c0f17; color: #f8fafc;">

<!-- Public Navbar -->
<nav class="public-navbar">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="<?= base_url() ?>" class="d-flex align-items-center gap-2 text-decoration-none">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="MotoShow" height="38">
    </a>

    <div class="d-none d-md-flex align-items-center gap-4">
      <a href="<?= base_url() ?>" class="text-white fw-semibold small text-decoration-none">Beranda</a>
      <a href="<?= base_url('pelanggan/katalog.php') ?>" class="text-white-50 small text-decoration-none">Katalog Motor</a>
      <a href="#simulasi" class="text-white-50 small text-decoration-none">Simulasi Kredit</a>
      <a href="#keunggulan" class="text-white-50 small text-decoration-none">Keunggulan</a>
      <a href="#kontak" class="text-white-50 small text-decoration-none">Kontak & Lokasi</a>
    </div>

    <div class="d-flex align-items-center gap-2">
      <?php if ($isUserLoggedIn): ?>
        <a href="<?= $dashboardUrl ?>" class="btn btn-primary-ms btn-sm">
          <i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i> Dashboard (<?= htmlspecialchars($currentUserRole) ?>)
        </a>
        <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-outline-ms btn-sm" title="Keluar">
          <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
        </a>
      <?php else: ?>
        <a href="<?= base_url('auth/login.php') ?>" class="btn btn-outline-ms btn-sm">
          <i data-lucide="log-in" style="width: 15px; height: 15px;"></i> Masuk
        </a>
        <a href="<?= base_url('auth/register.php') ?>" class="btn btn-primary-ms btn-sm">
          <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i> Daftar
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<header class="hero-section position-relative">
  <div class="hero-glow"></div>
  <div class="container position-relative" style="z-index: 2;">
    <div class="row align-items-center gy-5">
      <div class="col-lg-7">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger bg-opacity-15 border border-danger border-opacity-25 text-danger small fw-bold mb-3">
          <i data-lucide="award" style="width: 16px; height: 16px;"></i> Dealer Resmi & Showroom Motor Premium
        </div>
        <h1 class="hero-title text-white">
          Wujudkan Motor Impian Anda di <span class="text-danger">MotoShow</span>
        </h1>
        <p class="hero-subtitle">
          Pilihan lengkap motor matic, sport, bebek, dan touring dari pabrikan ternama dunia. Nikmati kemudahan transaksi tunai, transfer, hingga skema kredit ringan dengan proses cepat dan garansi resmi.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-primary-ms px-4 py-2 fs-6">
            <i data-lucide="bike" style="width: 20px; height: 20px;"></i> Jelajahi Katalog Motor
          </a>
          <a href="#simulasi" class="btn btn-outline-ms px-4 py-2 fs-6">
            <i data-lucide="calculator" style="width: 20px; height: 20px;"></i> Hitung Cicilan Kredit
          </a>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-5 pt-4 border-top border-secondary border-opacity-10 g-3">
          <div class="col-4">
            <div class="fs-3 fw-black text-white">100%</div>
            <div class="text-muted small">Unit Asli & Bergaransi</div>
          </div>
          <div class="col-4">
            <div class="fs-3 fw-black text-danger">5 Merek</div>
            <div class="text-muted small">Pabrikan Resmi Dunia</div>
          </div>
          <div class="col-4">
            <div class="fs-3 fw-black text-white">1 Hari</div>
            <div class="text-muted small">Proses Cepat Disetujui</div>
          </div>
        </div>
      </div>

      <div class="col-lg-5 text-center">
        <div class="p-4 rounded-4 position-relative" style="background: linear-gradient(145deg, #1c2232, #121622); border: 1px solid rgba(255,255,255,0.08);">
          <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-3 py-2">Unit Terlaris</span>
          <img src="<?= base_url('assets/uploads/motors/motor_pcx160.jpg') ?>" alt="Honda PCX 160" class="img-fluid rounded mb-3" style="max-height: 260px; object-fit: contain;">
          <h5 class="fw-bold text-white mb-1">Honda PCX 160 ABS</h5>
          <div class="text-danger fw-black fs-4 mb-3">Rp 36.500.000</div>
          <a href="<?= base_url('pelanggan/detail_motor.php?id=1') ?>" class="btn btn-outline-ms w-100 justify-content-center">
            Lihat Spesifikasi Lengkap
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Brand Selector Carousel -->
<section class="py-4 border-bottom border-secondary border-opacity-10" style="background-color: #10141f;">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">
      <span class="text-muted small fw-bold text-uppercase me-2">Merek Resmi:</span>
      <?php foreach ($brands as $b): ?>
        <a href="<?= base_url('pelanggan/katalog.php?brand=' . $b['id']) ?>" class="brand-chip">
          <i data-lucide="tag" style="width: 14px; height: 14px;" class="text-danger"></i>
          <span><?= htmlspecialchars($b['nama_merek']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Featured Motors Section -->
<section class="py-5">
  <div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
      <div>
        <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-3 py-1 mb-2">Pilihan Terbaik</span>
        <h3 class="fw-black text-white mb-1">Motor Pilihan Showroom</h3>
        <p class="text-muted small mb-0">Ragam varian sepeda motor terfavorit siap pakai dengan kondisi prima</p>
      </div>
      <div>
        <a href="<?= base_url('pelanggan/katalog.php') ?>" class="text-danger fw-bold small text-decoration-none">
          Lihat Semua Motor (Katalog Lengkap) &rarr;
        </a>
      </div>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredMotors as $m): ?>
        <div class="col-sm-6 col-lg-4">
          <div class="motor-card">
            <div class="motor-card-img-wrapper">
              <span class="motor-card-brand-badge"><?= htmlspecialchars($m['nama_merek']) ?></span>
              <span class="motor-card-badge">
                <span class="badge badge-status-tersedia">Tersedia (Stok: <?= $m['stok'] ?>)</span>
              </span>
              <img src="<?= getMotorImage($m['foto']) ?>" alt="<?= htmlspecialchars($m['nama_motor']) ?>">
            </div>
            <div class="motor-card-content">
              <h5 class="motor-title text-truncate"><?= htmlspecialchars($m['nama_motor']) ?></h5>
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
                <a href="<?= base_url('pelanggan/pemesanan.php?motor_id=' . $m['id']) ?>" class="btn btn-primary-ms btn-sm flex-fill justify-content-center">
                  <i data-lucide="calendar-check" style="width: 14px; height: 14px;"></i> Pesan Sekarang
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Simulasi Kredit Widget -->
<section id="simulasi" class="py-5" style="background-color: #121622; border-top: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06);">
  <div class="container py-4">
    <div class="row align-items-center gy-4">
      <div class="col-lg-5">
        <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-3 py-1 mb-2">Financing & Credit</span>
        <h3 class="fw-black text-white mb-2">Simulasi Kredit Pintar</h3>
        <p class="text-muted small mb-4">
          Hitung estimasi pembayaran uang muka (DP) dan besar cicilan bulanan yang sesuai dengan kemampuan anggaran Anda.
        </p>

        <div class="feature-card p-3 mb-3 d-flex align-items-center gap-3">
          <div class="stat-icon red"><i data-lucide="percent"></i></div>
          <div>
            <div class="fw-bold text-white small">Bunga Kompetitif & Transparan</div>
            <div class="text-muted small">Tanpa biaya tersembunyi dengan angsuran tetap.</div>
          </div>
        </div>

        <div class="feature-card p-3 d-flex align-items-center gap-3">
          <div class="stat-icon green"><i data-lucide="shield-check"></i></div>
          <div>
            <div class="fw-bold text-white small">Asuransi Kehilangan Terpadu</div>
            <div class="text-muted small">Proteksi unit kendaraan selama masa cicilan berjalan.</div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="ms-card p-4">
          <h5 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
            <i data-lucide="calculator" class="text-danger" style="width: 20px; height: 20px;"></i> Kalkulator Angsuran
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-ms">Harga Motor (OTR Rp)</label>
              <input type="number" id="calcHarga" class="form-control form-control-ms" value="36500000" step="500000">
            </div>
            <div class="col-md-6">
              <label class="form-label-ms">Uang Muka / DP (Rp)</label>
              <input type="number" id="calcDP" class="form-control form-control-ms" value="7000000" step="500000">
            </div>
            <div class="col-md-12">
              <label class="form-label-ms">Pilihan Jangka Tenor (Bulan)</label>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-ms flex-fill active" id="btnTenor12" onclick="setTenor(12)">12 Bulan (1 Thn)</button>
                <button type="button" class="btn btn-outline-ms flex-fill" id="btnTenor24" onclick="setTenor(24)">24 Bulan (2 Thn)</button>
                <button type="button" class="btn btn-outline-ms flex-fill" id="btnTenor36" onclick="setTenor(36)">36 Bulan (3 Thn)</button>
              </div>
            </div>
          </div>

          <div class="mt-4 p-4 rounded bg-dark border border-secondary border-opacity-25 text-center">
            <div class="text-muted small text-uppercase fw-bold mb-1">Perkiraan Angsuran Bulanan:</div>
            <div class="fs-1 fw-black text-danger" id="calcResult">Rp 2.458.333</div>
            <div class="text-muted small mt-1" id="calcDetail">Sisa Pokok: Rp 29.500.000 selama 12 bulan</div>
          </div>

          <div class="text-center mt-3">
            <a href="<?= base_url('pelanggan/pemesanan.php') ?>" class="btn btn-primary-ms px-4">
              <i data-lucide="send" style="width: 16px; height: 16px;"></i> Ajukan Kredit Sekarang
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Keunggulan Section -->
<section id="keunggulan" class="py-5">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-3 py-1 mb-2">Mengapa Memilih Kami?</span>
      <h3 class="fw-black text-white">Standar Layanan Showroom MotoShow</h3>
      <p class="text-muted small">Komitmen utama kami adalah memberikan kepuasan dan rasa aman dalam setiap kepemilikan kendaraan bermotor.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="feature-card text-center">
          <div class="stat-icon red mx-auto mb-3"><i data-lucide="shield"></i></div>
          <h6 class="fw-bold text-white mb-2">Unit 100% Original</h6>
          <p class="text-muted small mb-0">Seluruh unit sepeda motor didatangkan langsung dari pabrikan resmi lengkap dengan buku garansi.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card text-center">
          <div class="stat-icon blue mx-auto mb-3"><i data-lucide="zap"></i></div>
          <h6 class="fw-bold text-white mb-2">Proses Approval Cepat</h6>
          <p class="text-muted small mb-0">Pengajuan pesanan dan berkas pembiayaan kredit diproses secara kilat tanpa berbelit-belit.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card text-center">
          <div class="stat-icon green mx-auto mb-3"><i data-lucide="credit-card"></i></div>
          <h6 class="fw-bold text-white mb-2">Pilihan Pembayaran Fleksibel</h6>
          <p class="text-muted small mb-0">Mendukung pelunasan cash tunai di kasir, transfer antar bank, hingga cicilan tenor sampai 3 tahun.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card text-center">
          <div class="stat-icon amber mx-auto mb-3"><i data-lucide="file-check"></i></div>
          <h6 class="fw-bold text-white mb-2">Legalitas Aman & Terjamin</h6>
          <p class="text-muted small mb-0">Pengurusan STNK, Plat Nomor, dan BPKB dikawal profesional hingga sampai ke tangan Anda.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Kontak & Lokasi Section -->
<section id="kontak" class="py-5" style="background-color: #10141f; border-top: 1px solid rgba(255,255,255,0.06);">
  <div class="container py-4">
    <div class="row gy-4 align-items-center">
      <div class="col-lg-6">
        <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 px-3 py-1 mb-2">Hubungi Showroom</span>
        <h3 class="fw-black text-white mb-3">Kunjungi Showroom Utama Kami</h3>
        <p class="text-muted small mb-4">
          Tim sales konsultan kami siap menyambut dan mendampingi Anda melihat langsung unit kendaraan impian.
        </p>

        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-start gap-3">
            <div class="stat-icon red flex-shrink-0" style="width: 42px; height: 42px;"><i data-lucide="map-pin" style="width: 20px; height: 20px;"></i></div>
            <div>
              <div class="fw-bold text-white small">Alamat Showroom:</div>
              <div class="text-muted small">Jl. Showroom Raya Boulevard No. 88, Kebayoran Baru, Jakarta Selatan</div>
            </div>
          </div>
          <div class="d-flex align-items-start gap-3">
            <div class="stat-icon green flex-shrink-0" style="width: 42px; height: 42px;"><i data-lucide="phone" style="width: 20px; height: 20px;"></i></div>
            <div>
              <div class="fw-bold text-white small">Telepon & WhatsApp:</div>
              <div class="text-muted small">(021) 7890-1234 &bull; 0812-3456-7890</div>
            </div>
          </div>
          <div class="d-flex align-items-start gap-3">
            <div class="stat-icon blue flex-shrink-0" style="width: 42px; height: 42px;"><i data-lucide="clock" style="width: 20px; height: 20px;"></i></div>
            <div>
              <div class="fw-bold text-white small">Jam Operasional:</div>
              <div class="text-muted small">Senin &ndash; Sabtu: 08.00 &ndash; 18.00 WIB | Minggu: 09.00 &ndash; 16.00 WIB</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="ms-card p-4">
          <h5 class="fw-bold text-white mb-3">Kirim Pesan Konsultasi</h5>
          <form onsubmit="event.preventDefault(); alert('Terima kasih! Pesan Anda telah kami terima. Tim konsultan MotoShow akan segera menghubungi nomor Anda.'); this.reset();">
            <div class="mb-3">
              <label class="form-label-ms">Nama Lengkap</label>
              <input type="text" class="form-control form-control-ms" placeholder="Nama Anda" required>
            </div>
            <div class="mb-3">
              <label class="form-label-ms">Nomor WhatsApp</label>
              <input type="text" class="form-control form-control-ms" placeholder="08xxxxxxxxxx" required>
            </div>
            <div class="mb-3">
              <label class="form-label-ms">Unit Motor yang Diminati</label>
              <input type="text" class="form-control form-control-ms" placeholder="Contoh: Honda PCX 160 / Yamaha NMAX">
            </div>
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center py-2">
              <i data-lucide="send" style="width: 16px; height: 16px;"></i> Kirim Pertanyaan
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="py-4 border-top border-secondary border-opacity-10 text-white-50 small" style="background-color: #0c0f17;">
  <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
    <div>
      &copy; <?= date('Y') ?> <strong class="text-white">MotoShow</strong> &mdash; Modern Motorcycle Showroom System.
    </div>
    <div class="d-flex gap-3">
      <a href="<?= base_url('admin/index.php') ?>" class="text-muted text-decoration-none">Area Admin</a>
      <a href="<?= base_url('petugas/index.php') ?>" class="text-muted text-decoration-none">Area Petugas</a>
      <a href="<?= base_url('pelanggan/index.php') ?>" class="text-muted text-decoration-none">Area Pelanggan</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();

  let selectedTenor = 12;
  function setTenor(t) {
    selectedTenor = t;
    document.getElementById('btnTenor12').classList.toggle('active', t === 12);
    document.getElementById('btnTenor24').classList.toggle('active', t === 24);
    document.getElementById('btnTenor36').classList.toggle('active', t === 36);
    calculateCredit();
  }

  function calculateCredit() {
    const harga = parseFloat(document.getElementById('calcHarga').value) || 0;
    const dp = parseFloat(document.getElementById('calcDP').value) || 0;
    const sisa = Math.max(0, harga - dp);
    const cicilan = Math.round(sisa / selectedTenor);

    document.getElementById('calcResult').innerText = 'Rp ' + cicilan.toLocaleString('id-ID') + ' / bln';
    document.getElementById('calcDetail').innerText = 'Sisa Pokok: Rp ' + sisa.toLocaleString('id-ID') + ' selama ' + selectedTenor + ' bulan';
  }

  document.getElementById('calcHarga').addEventListener('input', calculateCredit);
  document.getElementById('calcDP').addEventListener('input', calculateCredit);
  calculateCredit();
</script>
</body>
</html>
