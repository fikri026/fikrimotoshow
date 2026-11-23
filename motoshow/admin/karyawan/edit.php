<?php
/**
 * Edit Karyawan
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Edit Data Karyawan - MotoShow';
$activeMenu = 'karyawan';
$error = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    setFlash('danger', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url('admin/karyawan/index.php'));
    exit;
}

// User list for linking
$availableUsers = $pdo->prepare("
    SELECT u.id, u.username, u.nama_lengkap, u.role 
    FROM users u 
    LEFT JOIN employees e ON u.id = e.user_id 
    WHERE (e.id IS NULL OR e.id = ?) AND u.role IN ('admin', 'petugas')
");
$availableUsers->execute([$id]);
$users = $availableUsers->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = cleanInput($_POST['nip'] ?? '');
    $namaLengkap = cleanInput($_POST['nama_lengkap'] ?? '');
    $jabatan = cleanInput($_POST['jabatan'] ?? 'Sales Executive');
    $noHp = cleanInput($_POST['no_hp'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $alamat = cleanInput($_POST['alamat'] ?? '');
    $status = cleanInput($_POST['status'] ?? 'Aktif');
    $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    if (empty($nip) || empty($namaLengkap) || empty($noHp)) {
        $error = 'Harap isi NIP, Nama Lengkap, dan Nomor Telepon.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE nip = ? AND id != ?");
        $stmt->execute([$nip, $id]);
        if ($stmt->fetch()) {
            $error = 'NIP "' . htmlspecialchars($nip) . '" sudah digunakan oleh karyawan lain.';
        } else {
            $stmt = $pdo->prepare("UPDATE employees SET user_id = ?, nip = ?, nama_lengkap = ?, jabatan = ?, no_hp = ?, email = ?, alamat = ?, status = ? WHERE id = ?");
            $stmt->execute([$userId, $nip, $namaLengkap, $jabatan, $noHp, $email, $alamat, $status, $id]);

            setFlash('success', 'Data karyawan ' . htmlspecialchars($namaLengkap) . ' berhasil diperbarui!');
            header('Location: ' . base_url('admin/karyawan/index.php'));
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
        <a href="<?= base_url('admin/karyawan/index.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Data Karyawan
        </a>
        <h4 class="fw-black text-white mb-0">Edit Karyawan: <?= htmlspecialchars($emp['nama_lengkap']) ?></h4>
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
                <div class="col-md-4">
                  <label class="form-label-ms">NIP *</label>
                  <input type="text" name="nip" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['nip'] ?? $emp['nip']) ?>" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label-ms">Nama Lengkap *</label>
                  <input type="text" name="nama_lengkap" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? $emp['nama_lengkap']) ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Jabatan *</label>
                  <select name="jabatan" class="form-select form-select-ms" required>
                    <?php
                    $jabatanList = ['General Manager', 'Sales & Kasir', 'Sales Executive', 'Customer Relations', 'Mekanik Ahli', 'Admin Operasional'];
                    $curJab = $_POST['jabatan'] ?? $emp['jabatan'];
                    foreach ($jabatanList as $j): ?>
                      <option value="<?= $j ?>" <?= $curJab === $j ? 'selected' : '' ?>><?= $j ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Status Kepegawaian *</label>
                  <select name="status" class="form-select form-select-ms" required>
                    <?php
                    $statusList = ['Aktif', 'Cuti', 'Nonaktif'];
                    $curSt = $_POST['status'] ?? $emp['status'];
                    foreach ($statusList as $s): ?>
                      <option value="<?= $s ?>" <?= $curSt === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Nomor HP / WA *</label>
                  <input type="text" name="no_hp" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['no_hp'] ?? $emp['no_hp']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Email</label>
                  <input type="email" name="email" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['email'] ?? $emp['email']) ?>">
                </div>

                <div class="col-md-12">
                  <label class="form-label-ms">Hubungkan dengan Akun Login Pengguna</label>
                  <select name="user_id" class="form-select form-select-ms">
                    <option value="">-- Tanpa Akun Login Sistem --</option>
                    <?php 
                    $curUid = $_POST['user_id'] ?? $emp['user_id'];
                    foreach ($users as $u): ?>
                      <option value="<?= $u['id'] ?>" <?= $curUid == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nama_lengkap']) ?> (<?= htmlspecialchars($u['username']) ?> - <?= htmlspecialchars($u['role']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Alamat Tempat Tinggal</label>
                  <textarea name="alamat" rows="2" class="form-control form-control-ms"><?= htmlspecialchars($_POST['alamat'] ?? $emp['alamat']) ?></textarea>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Perubahan
                </button>
                <a href="<?= base_url('admin/karyawan/index.php') ?>" class="btn btn-outline-ms">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
