<?php
/**
 * Login Page MotoShow
 */
require_once __DIR__ . '/../config/database.php';

// Jika sudah login, redirect sesuai role
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
    } elseif ($role === 'petugas') {
        header('Location: ' . base_url('petugas/dashboard.php'));
    } else {
        header('Location: ' . base_url('pelanggan/dashboard.php'));
    }
    exit;
}

$error = '';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Harap masukkan username dan password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_name'] = $user['nama_lengkap'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user'] = $user;

            setFlash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['nama_lengkap']) . '!');

            if ($user['role'] === 'admin') {
                header('Location: ' . base_url('admin/dashboard.php'));
            } elseif ($user['role'] === 'petugas') {
                header('Location: ' . base_url('petugas/dashboard.php'));
            } else {
                header('Location: ' . base_url('pelanggan/dashboard.php'));
            }
            exit;
        } else {
            $error = 'Username / email atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - MotoShow Showroom</title>

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
      padding: 1.5rem;
    }
    .auth-card {
      width: 100%;
      max-width: 440px;
      background: #161b27;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 2.25rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
    }
    .quick-pill {
      cursor: pointer;
      font-size: 0.75rem;
      padding: 0.35rem 0.65rem;
      border-radius: 6px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #cbd5e1;
      transition: all 0.2s ease;
    }
    .quick-pill:hover {
      background: rgba(239, 68, 68, 0.2);
      border-color: #ef4444;
      color: #ffffff;
    }
  </style>
</head>
<body>

<div class="auth-card">
  <!-- Brand Header -->
  <div class="text-center mb-4">
    <a href="<?= base_url() ?>">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="MotoShow" height="42" class="mb-2">
    </a>
    <h5 class="fw-bold text-white mb-1">Masuk ke Sistem</h5>
    <p class="text-muted small">Silakan masukkan kredensial akun Anda</p>
  </div>

  <?php renderFlash(); ?>

  <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center border-0 small py-2 px-3 mb-3" role="alert">
      <i data-lucide="alert-circle" class="me-2 flex-shrink-0" style="width: 18px; height: 18px;"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label-ms">Username atau Email</label>
      <div class="input-group">
        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
          <i data-lucide="user" style="width: 18px; height: 18px;"></i>
        </span>
        <input type="text" name="username" id="usernameInput" class="form-control form-control-ms" placeholder="Username / Email" required autofocus>
      </div>
    </div>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="form-label-ms mb-0">Kata Sandi</label>
      </div>
      <div class="input-group">
        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-white-50">
          <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
        </span>
        <input type="password" name="password" id="passwordInput" class="form-control form-control-ms" placeholder="••••••••" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary-ms w-100 justify-content-center py-2 mt-2">
      <i data-lucide="log-in" style="width: 18px; height: 18px;"></i> Masuk Sekarang
    </button>
  </form>

  <!-- Quick Demo Fill -->
  <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
    <div class="text-muted small mb-2 text-center">Akun Pengujian Demo (Klik untuk isi cepat):</div>
    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <span class="quick-pill" onclick="fillCred('admin', 'admin123')">
        <i data-lucide="shield" style="width: 12px; height: 12px; vertical-align: -1px;"></i> Admin
      </span>
      <span class="quick-pill" onclick="fillCred('petugas', 'petugas123')">
        <i data-lucide="user-check" style="width: 12px; height: 12px; vertical-align: -1px;"></i> Petugas
      </span>
      <span class="quick-pill" onclick="fillCred('pelanggan', 'pelanggan123')">
        <i data-lucide="smile" style="width: 12px; height: 12px; vertical-align: -1px;"></i> Pelanggan
      </span>
    </div>
  </div>

  <div class="text-center mt-4 text-muted small">
    Belum punya akun pelanggan? <a href="<?= base_url('auth/register.php') ?>" class="fw-semibold text-danger">Daftar di sini</a>
    <div class="mt-2">
      <a href="<?= base_url('index.php') ?>" class="text-secondary small">&larr; Kembali ke Beranda Showroom</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();
  function fillCred(user, pass) {
    document.getElementById('usernameInput').value = user;
    document.getElementById('passwordInput').value = pass;
  }
</script>
</body>
</html>
