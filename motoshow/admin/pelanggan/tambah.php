<?php
/**
 * Tambah Pelanggan Baru
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Tambah Pelanggan - MotoShow';
$activeMenu = 'pelanggan';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = cleanInput($_POST['nama_lengkap'] ?? '');
    $nik = cleanInput($_POST['nik'] ?? '');
    $noHp = cleanInput($_POST['no_hp'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $alamat = cleanInput($_POST['alamat'] ?? '');

    if (empty($namaLengkap) || empty($nik) || empty($noHp) || empty($alamat)) {
        $error = 'Harap lengkapi semua kolom bertanda bintang (*).';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE nik = ?");
        $stmt->execute([$nik]);
        if ($stmt->fetch()) {
            $error = 'Nomor NIK "' . htmlspecialchars($nik) . '" sudah terdaftar sebelumnya.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (nik, nama_lengkap, no_hp, email, alamat) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nik, $namaLengkap, $noHp, $email, $alamat]);

            setFlash('success', 'Data pelanggan ' . htmlspecialchars($namaLengkap) . ' berhasil ditambahkan!');
            $redir = ($_SESSION['user_role'] === 'admin') ? 'admin/pelanggan/index.php' : 'petugas/pelanggan.php';
            header('Location: ' . base_url($redir));
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <a href="<?= base_url($_SESSION['user_role'] === 'admin' ? 'admin/pelanggan/index.php' : 'petugas/pelanggan.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Data Pelanggan
        </a>
        <h4 class="fw-black text-white mb-0">Tambah Data Pelanggan</h4>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2" style="width: 20px; height: 20px;"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-8">
        <div class="ms-card">
          <div class="ms-card-body p-4">
            <form method="POST" action="">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label-ms">Nama Lengkap *</label>
                  <input type="text" name="nama_lengkap" class="form-control form-control-ms" placeholder="Nama lengkap sesuai KTP" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required autofocus>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">NIK (Nomor Induk Kependudukan) *</label>
                  <input type="text" name="nik" class="form-control form-control-ms" placeholder="16 digit NIK" maxlength="20" value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Nomor HP / WhatsApp *</label>
                  <input type="text" name="no_hp" class="form-control form-control-ms" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Alamat Email</label>
                  <input type="email" name="email" class="form-control form-control-ms" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Alamat Domisili Lengkap *</label>
                  <textarea name="alamat" rows="3" class="form-control form-control-ms" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos..." required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Data Pelanggan
                </button>
                <a href="<?= base_url($_SESSION['user_role'] === 'admin' ? 'admin/pelanggan/index.php' : 'petugas/pelanggan.php') ?>" class="btn btn-outline-ms">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
