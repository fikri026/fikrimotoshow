<?php
/**
 * Tambah Merek Motor Baru
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Tambah Merek Motor - MotoShow';
$activeMenu = 'merek';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaMerek = cleanInput($_POST['nama_merek'] ?? '');
    $negaraAsal = cleanInput($_POST['negara_asal'] ?? '');
    $deskripsi = cleanInput($_POST['deskripsi'] ?? '');

    if (empty($namaMerek)) {
        $error = 'Nama merek tidak boleh kosong.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM brands WHERE nama_merek = ?");
        $stmt->execute([$namaMerek]);
        if ($stmt->fetch()) {
            $error = 'Nama merek "' . htmlspecialchars($namaMerek) . '" sudah ada di database.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO brands (nama_merek, negara_asal, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$namaMerek, $negaraAsal, $deskripsi]);

            setFlash('success', 'Merek motor ' . htmlspecialchars($namaMerek) . ' berhasil ditambahkan!');
            header('Location: ' . base_url('admin/merek/index.php'));
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
        <a href="<?= base_url('admin/merek/index.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Merek Motor
        </a>
        <h4 class="fw-black text-white mb-0">Tambah Merek Motor Baru</h4>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2" style="width: 20px; height: 20px;"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-6">
        <div class="ms-card">
          <div class="ms-card-body p-4">
            <form method="POST" action="">
              <div class="mb-3">
                <label class="form-label-ms">Nama Merek *</label>
                <input type="text" name="nama_merek" class="form-control form-control-ms" placeholder="Contoh: Ducati, BMW, Harley-Davidson" value="<?= htmlspecialchars($_POST['nama_merek'] ?? '') ?>" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label-ms">Negara Asal</label>
                <input type="text" name="negara_asal" class="form-control form-control-ms" placeholder="Contoh: Italia, Jerman, Amerika Serikat" value="<?= htmlspecialchars($_POST['negara_asal'] ?? '') ?>">
              </div>
              <div class="mb-4">
                <label class="form-label-ms">Deskripsi Merek</label>
                <textarea name="deskripsi" rows="3" class="form-control form-control-ms" placeholder="Informasi singkat tentang pabrikan..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Merek
                </button>
                <a href="<?= base_url('admin/merek/index.php') ?>" class="btn btn-outline-ms">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
