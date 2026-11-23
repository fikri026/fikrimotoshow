<?php
/**
 * Edit Akun Pengguna
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Edit Akun Pengguna - MotoShow';
$activeMenu = 'pengguna';
$error = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$usr = $stmt->fetch();

if (!$usr) {
    setFlash('danger', 'Data akun pengguna tidak ditemukan.');
    header('Location: ' . base_url('admin/pengguna/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $namaLengkap = cleanInput($_POST['nama_lengkap'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $noHp = cleanInput($_POST['no_hp'] ?? '');
    $role = cleanInput($_POST['role'] ?? 'petugas');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($namaLengkap) || empty($email)) {
        $error = 'Harap lengkapi nama lengkap, username, dan email.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        if ($stmt->fetch()) {
            $error = 'Username atau Email sudah dipakai akun lain.';
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmtUpdate = $pdo->prepare("UPDATE users SET username = ?, password = ?, email = ?, nama_lengkap = ?, role = ?, no_hp = ? WHERE id = ?");
                $stmtUpdate->execute([$username, $hashed, $email, $namaLengkap, $role, $noHp, $id]);
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE users SET username = ?, email = ?, nama_lengkap = ?, role = ?, no_hp = ? WHERE id = ?");
                $stmtUpdate->execute([$username, $email, $namaLengkap, $role, $noHp, $id]);
            }

            // Jika mengedit diri sendiri, perbarui data session
            if ($id == $_SESSION['user_id']) {
                $_SESSION['user_name'] = $namaLengkap;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
            }

            setFlash('success', 'Data akun ' . htmlspecialchars($username) . ' berhasil diperbarui!');
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
        <h4 class="fw-black text-white mb-0">Edit Akun: <?= htmlspecialchars($usr['username']) ?></h4>
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
                  <input type="text" name="nama_lengkap" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? $usr['nama_lengkap']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Hak Akses (Role) *</label>
                  <select name="role" class="form-select form-select-ms" required <?= ($usr['id'] == $_SESSION['user_id']) ? 'disabled' : '' ?>>
                    <?php $curRole = $_POST['role'] ?? $usr['role']; ?>
                    <option value="admin" <?= $curRole === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="petugas" <?= $curRole === 'petugas' ? 'selected' : '' ?>>Petugas Kasir</option>
                    <option value="pelanggan" <?= $curRole === 'pelanggan' ? 'selected' : '' ?>>Pelanggan</option>
                  </select>
                  <?php if ($usr['id'] == $_SESSION['user_id']): ?>
                    <input type="hidden" name="role" value="<?= $usr['role'] ?>">
                    <span class="text-muted small" style="font-size: 0.72rem;">Role sendiri tidak dapat diubah di sini.</span>
                  <?php endif; ?>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Username *</label>
                  <input type="text" name="username" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['username'] ?? $usr['username']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Email *</label>
                  <input type="email" name="email" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['email'] ?? $usr['email']) ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Ubah Kata Sandi (Opsional)</label>
                  <input type="password" name="password" class="form-control form-control-ms" placeholder="Kosongkan jika sandi tidak diganti">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Nomor Telepon / WA</label>
                  <input type="text" name="no_hp" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['no_hp'] ?? $usr['no_hp']) ?>">
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Perubahan
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
