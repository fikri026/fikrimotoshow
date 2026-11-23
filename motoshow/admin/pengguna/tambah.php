<?php
/**
 * Tambah Pengguna Baru
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Tambah Pengguna Baru - MotoShow';
$activeMenu = 'pengguna';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $namaLengkap = cleanInput($_POST['nama_lengkap'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $noHp = cleanInput($_POST['no_hp'] ?? '');
    $role = cleanInput($_POST['role'] ?? 'petugas');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($namaLengkap) || empty($email) || empty($password)) {
        $error = 'Harap lengkapi semua kolom wajib.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username atau Email sudah terdaftar.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO users (username, password, email, nama_lengkap, role, no_hp) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$username, $hashed, $email, $namaLengkap, $role, $noHp]);

            setFlash('success', 'Akun ' . htmlspecialchars($username) . ' berhasil dibuat!');
            header('Location: ' . base_url('admin/pengguna/index.php'));
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
        <a href="<?= base_url('admin/pengguna/index.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Manajemen Akun
        </a>
        <h4 class="fw-black text-white mb-0">Tambah Akun Pengguna Baru</h4>
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
                  <input type="text" name="nama_lengkap" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required autofocus>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Hak Akses (Role) *</label>
                  <select name="role" class="form-select form-select-ms" required>
                    <option value="petugas" selected>Petugas Kasir & Sales</option>
                    <option value="admin">Administrator Utama</option>
                    <option value="pelanggan">Pelanggan</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Username *</label>
                  <input type="text" name="username" class="form-control form-control-ms" placeholder="username_login" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Email *</label>
                  <input type="email" name="email" class="form-control form-control-ms" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Kata Sandi (Password) *</label>
                  <input type="password" name="password" class="form-control form-control-ms" placeholder="Min. 6 karakter" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Nomor Telepon / WA</label>
                  <input type="text" name="no_hp" class="form-control form-control-ms" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Buat Akun
                </button>
                <a href="<?= base_url('admin/pengguna/index.php') ?>" class="btn btn-outline-ms">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
