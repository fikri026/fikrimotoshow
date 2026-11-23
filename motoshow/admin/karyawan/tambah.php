<?php
/**
 * Tambah Karyawan Baru
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Tambah Karyawan - MotoShow';
$activeMenu = 'karyawan';
$error = '';

// Ambil user yang belum terhubung ke karyawan (opsional)
$availableUsers = $pdo->query("
    SELECT u.id, u.username, u.nama_lengkap, u.role 
    FROM users u 
    LEFT JOIN employees e ON u.id = e.user_id 
    WHERE e.id IS NULL AND u.role IN ('admin', 'petugas')
")->fetchAll();

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
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE nip = ?");
        $stmt->execute([$nip]);
        if ($stmt->fetch()) {
            $error = 'NIP "' . htmlspecialchars($nip) . '" sudah digunakan.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO employees (user_id, nip, nama_lengkap, jabatan, no_hp, email, alamat, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nip, $namaLengkap, $jabatan, $noHp, $email, $alamat, $status]);

            setFlash('success', 'Data karyawan ' . htmlspecialchars($namaLengkap) . ' berhasil ditambahkan!');
            header('Location: ' . base_url('admin/karyawan/index.php'));
            exit;
        }
    }
}

$randomNip = 'EMP-' . str_pad(rand(10, 999), 3, '0', STR_PAD_LEFT);

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
        <h4 class="fw-black text-white mb-0">Tambah Karyawan Baru</h4>
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
                  <label class="form-label-ms">NIP (Nomor Induk Pegawai) *</label>
                  <input type="text" name="nip" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['nip'] ?? $randomNip) ?>" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label-ms">Nama Lengkap Karyawan *</label>
                  <input type="text" name="nama_lengkap" class="form-control form-control-ms" placeholder="Nama lengkap karyawan" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Jabatan *</label>
                  <select name="jabatan" class="form-select form-select-ms" required>
                    <option value="General Manager">General Manager</option>
                    <option value="Sales & Kasir" selected>Sales & Kasir</option>
                    <option value="Sales Executive">Sales Executive</option>
                    <option value="Customer Relations">Customer Relations</option>
                    <option value="Mekanik Ahli">Mekanik Ahli</option>
                    <option value="Admin Operasional">Admin Operasional</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Status Kepegawaian *</label>
                  <select name="status" class="form-select form-select-ms" required>
                    <option value="Aktif" selected>Aktif</option>
                    <option value="Cuti">Cuti</option>
                    <option value="Nonaktif">Nonaktif</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Nomor HP / WA *</label>
                  <input type="text" name="no_hp" class="form-control form-control-ms" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Email</label>
                  <input type="email" name="email" class="form-control form-control-ms" placeholder="karyawan@motoshow.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="col-md-12">
                  <label class="form-label-ms">Hubungkan dengan Akun Login Pengguna (Opsional)</label>
                  <select name="user_id" class="form-select form-select-ms">
                    <option value="">-- Tanpa Akun Login Sistem --</option>
                    <?php foreach ($availableUsers as $u): ?>
                      <option value="<?= $u['id'] ?>">
                        <?= htmlspecialchars($u['nama_lengkap']) ?> (<?= htmlspecialchars($u['username']) ?> - <?= htmlspecialchars($u['role']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Alamat Tempat Tinggal</label>
                  <textarea name="alamat" rows="2" class="form-control form-control-ms" placeholder="Alamat domisili..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Karyawan
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
