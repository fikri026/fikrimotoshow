<?php
/**
 * Register Pelanggan MotoShow
 */
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: ' . base_url('pelanggan/dashboard.php'));
    exit;
}

$error = '';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = cleanInput($_POST['nama_lengkap'] ?? '');
    $nik = cleanInput($_POST['nik'] ?? '');
    $noHp = cleanInput($_POST['no_hp'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $alamat = cleanInput($_POST['alamat'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($namaLengkap) || empty($nik) || empty($noHp) || empty($username) || empty($password)) {
        $error = 'Harap lengkapi semua kolom bertanda bintang (*).';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal harus 6 karakter.';
    } else {
        // Cek username atau email atau NIK sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username atau Email sudah terdaftar. Silakan gunakan yang lain.';
        } else {
            $stmtNik = $pdo->prepare("SELECT id FROM customers WHERE nik = ?");
            $stmtNik->execute([$nik]);
            if ($stmtNik->fetch()) {
                $error = 'Nomor NIK sudah terdaftar di sistem pelanggan kami.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // 1. Simpan ke tabel users
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, email, nama_lengkap, role, no_hp) VALUES (?, ?, ?, ?, 'pelanggan', ?)");
                    $stmtUser->execute([$username, $hashed, $email, $namaLengkap, $noHp]);
                    $userId = $pdo->lastInsertId();

                    // 2. Simpan ke tabel customers
                    $stmtCust = $pdo->prepare("INSERT INTO customers (user_id, nik, nama_lengkap, no_hp, email, alamat) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtCust->execute([$userId, $nik, $namaLengkap, $noHp, $email, $alamat]);

                    $pdo->commit();

                    setFlash('success', 'Pendaftaran akun berhasil! Silakan masuk dengan akun baru Anda.');
                    header('Location: ' . base_url('auth/login.php'));
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Terjadi kesalahan saat pendaftaran: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun Pelanggan - MotoShow</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body {
      background: radial-gradient(circle at top right, rgba(239, 68, 68, 0.15), transparent 40%),
                  radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.1), transparent 40%),
                  #0c0f17;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 1rem;
    }
    .auth-card {
      width: 100%;
      max-width: 650px;
      background: #161b27;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 2.25rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
    }
  </style>
</head>
<body>

<div class="auth-card">
  <div class="text-center mb-4">
    <a href="<?= base_url() ?>">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="MotoShow" height="40" class="mb-2">
    </a>
    <h5 class="fw-bold text-white mb-1">Registrasi Pelanggan</h5>
    <p class="text-muted small">Daftarkan akun Anda untuk kemudahan pemesanan motor impian</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center border-0 small py-2 px-3 mb-3" role="alert">
      <i data-lucide="alert-circle" class="me-2 flex-shrink-0" style="width: 18px; height: 18px;"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label-ms">Nama Lengkap *</label>
        <input type="text" name="nama_lengkap" class="form-control form-control-ms" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label-ms">NIK (Nomor Induk Kependudukan) *</label>
        <input type="text" name="nik" class="form-control form-control-ms" placeholder="16 digit NIK" maxlength="20" value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label-ms">Nomor Telepon / WhatsApp *</label>
        <input type="text" name="no_hp" class="form-control form-control-ms" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label-ms">Email *</label>
        <input type="email" name="email" class="form-control form-control-ms" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="col-12">
        <label class="form-label-ms">Alamat Lengkap *</label>
        <textarea name="alamat" rows="2" class="form-control form-control-ms" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota..." required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="form-label-ms">Username *</label>
        <input type="text" name="username" class="form-control form-control-ms" placeholder="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label-ms">Kata Sandi *</label>
        <input type="password" name="password" class="form-control form-control-ms" placeholder="Min. 6 karakter" required>
      </div>
      <div class="col-md-4">
        <label class="form-label-ms">Ulangi Kata Sandi *</label>
        <input type="password" name="confirm_password" class="form-control form-control-ms" placeholder="Ulangi sandi" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary-ms w-100 justify-content-center py-2 mt-4">
      <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Daftar Sekarang
    </button>
  </form>

  <div class="text-center mt-4 text-muted small">
    Sudah memiliki akun? <a href="<?= base_url('auth/login.php') ?>" class="fw-semibold text-danger">Masuk di sini</a>
    <div class="mt-2">
      <a href="<?= base_url('index.php') ?>" class="text-secondary small">&larr; Kembali ke Beranda Showroom</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
