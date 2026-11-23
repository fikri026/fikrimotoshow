<?php
/**
 * Edit Data Motor
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Edit Data Motor - MotoShow';
$activeMenu = 'motor';
$error = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM motors WHERE id = ?");
$stmt->execute([$id]);
$motor = $stmt->fetch();

if (!$motor) {
    setFlash('danger', 'Data motor tidak ditemukan!');
    header('Location: ' . base_url('admin/motor/index.php'));
    exit;
}

$brands = $pdo->query("SELECT * FROM brands ORDER BY nama_merek ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kodeMotor = cleanInput($_POST['kode_motor'] ?? '');
    $namaMotor = cleanInput($_POST['nama_motor'] ?? '');
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $tipeMotor = cleanInput($_POST['tipe_motor'] ?? 'Matic');
    $tahunProduksi = (int)($_POST['tahun_produksi'] ?? date('Y'));
    $warna = cleanInput($_POST['warna'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $kapasitasMesin = (int)($_POST['kapasitas_mesin'] ?? 0);
    $jenisTransmisi = cleanInput($_POST['jenis_transmisi'] ?? '');
    $jenisBahanBakar = cleanInput($_POST['jenis_bahan_bakar'] ?? 'Bensin');
    $konsumsiBbm = cleanInput($_POST['konsumsi_bbm'] ?? '');
    $kapasitasTangki = cleanInput($_POST['kapasitas_tangki'] ?? '');
    $sistemPengereman = cleanInput($_POST['sistem_pengereman'] ?? '');
    $suspensi = cleanInput($_POST['suspensi'] ?? '');
    $dimensi = cleanInput($_POST['dimensi'] ?? '');
    $berat = cleanInput($_POST['berat'] ?? '');
    $deskripsi = cleanInput($_POST['deskripsi'] ?? '');
    $status = cleanInput($_POST['status'] ?? 'Tersedia');

    if (empty($kodeMotor) || empty($namaMotor) || empty($brandId) || $harga <= 0) {
        $error = 'Harap isi Kode Motor, Nama Motor, Merek, dan Harga dengan valid.';
    } else {
        // Cek duplikasi kode motor dengan record lain
        $stmt = $pdo->prepare("SELECT id FROM motors WHERE kode_motor = ? AND id != ?");
        $stmt->execute([$kodeMotor, $id]);
        if ($stmt->fetch()) {
            $error = 'Kode motor "' . htmlspecialchars($kodeMotor) . '" sudah digunakan motor lain.';
        } else {
            $fotoName = $motor['foto'];
            if (!empty($_FILES['foto']['name'])) {
                $uploadResult = handleUploadFoto($_FILES['foto'], 'motors');
                if ($uploadResult['success']) {
                    // Hapus foto lama jika bukan default
                    if ($fotoName && file_exists(dirname(dirname(__DIR__)) . '/assets/uploads/motors/' . $fotoName)) {
                        @unlink(dirname(dirname(__DIR__)) . '/assets/uploads/motors/' . $fotoName);
                    }
                    $fotoName = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            if (empty($error)) {
                $sql = "UPDATE motors SET 
                    kode_motor = ?, nama_motor = ?, brand_id = ?, tipe_motor = ?, tahun_produksi = ?, 
                    warna = ?, harga = ?, stok = ?, kapasitas_mesin = ?, jenis_transmisi = ?, 
                    jenis_bahan_bakar = ?, konsumsi_bbm = ?, kapasitas_tangki = ?, 
                    sistem_pengereman = ?, suspensi = ?, dimensi = ?, berat = ?, 
                    deskripsi = ?, foto = ?, status = ?
                    WHERE id = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $kodeMotor, $namaMotor, $brandId, $tipeMotor, $tahunProduksi,
                    $warna, $harga, $stok, $kapasitasMesin, $jenisTransmisi,
                    $jenisBahanBakar, $konsumsiBbm, $kapasitasTangki,
                    $sistemPengereman, $suspensi, $dimensi, $berat,
                    $deskripsi, $fotoName, $status, $id
                ]);

                setFlash('success', 'Data motor ' . htmlspecialchars($namaMotor) . ' berhasil diperbarui!');
                header('Location: ' . base_url('admin/motor/index.php'));
                exit;
            }
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
        <a href="<?= base_url('admin/motor/index.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Data Motor
        </a>
        <h4 class="fw-black text-white mb-0">Edit Data Motor: <?= htmlspecialchars($motor['nama_motor']) ?></h4>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2" style="width: 20px; height: 20px;"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">
                <i data-lucide="info" class="me-1 text-danger" style="width: 18px; height: 18px;"></i> Informasi Pokok Motor
              </h6>
            </div>
            <div class="ms-card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label-ms">Kode Motor *</label>
                  <input type="text" name="kode_motor" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['kode_motor'] ?? $motor['kode_motor']) ?>" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label-ms">Nama Motor *</label>
                  <input type="text" name="nama_motor" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['nama_motor'] ?? $motor['nama_motor']) ?>" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label-ms">Merek Motor *</label>
                  <select name="brand_id" class="form-select form-select-ms" required>
                    <?php foreach ($brands as $b): ?>
                      <option value="<?= $b['id'] ?>" <?= ((isset($_POST['brand_id']) ? $_POST['brand_id'] : $motor['brand_id']) == $b['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['nama_merek']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Tipe Motor *</label>
                  <select name="tipe_motor" class="form-select form-select-ms" required>
                    <?php 
                    $currTipe = $_POST['tipe_motor'] ?? $motor['tipe_motor'];
                    foreach (['Matic', 'Sport', 'Naked', 'Bebek', 'Trail', 'Cruiser'] as $tp): ?>
                      <option value="<?= $tp ?>" <?= $currTipe === $tp ? 'selected' : '' ?>><?= $tp ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Tahun Produksi *</label>
                  <input type="number" name="tahun_produksi" class="form-control form-control-ms" min="2000" max="2035" value="<?= htmlspecialchars($_POST['tahun_produksi'] ?? $motor['tahun_produksi']) ?>" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label-ms">Warna *</label>
                  <input type="text" name="warna" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['warna'] ?? $motor['warna']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Harga Motor (OTR Rp) *</label>
                  <input type="number" name="harga" class="form-control form-control-ms" min="0" step="50000" value="<?= htmlspecialchars($_POST['harga'] ?? (int)$motor['harga']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Stok (Unit) *</label>
                  <input type="number" name="stok" class="form-control form-control-ms" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? $motor['stok']) ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">
                <i data-lucide="wrench" class="me-1 text-danger" style="width: 18px; height: 18px;"></i> Spesifikasi Teknis Mesin & Rangka
              </h6>
            </div>
            <div class="ms-card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label-ms">Kapasitas Mesin (CC) *</label>
                  <input type="number" name="kapasitas_mesin" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['kapasitas_mesin'] ?? $motor['kapasitas_mesin']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Jenis Transmisi *</label>
                  <input type="text" name="jenis_transmisi" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['jenis_transmisi'] ?? $motor['jenis_transmisi']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Jenis Bahan Bakar</label>
                  <select name="jenis_bahan_bakar" class="form-select form-select-ms">
                    <?php $bbm = $_POST['jenis_bahan_bakar'] ?? $motor['jenis_bahan_bakar']; ?>
                    <option value="Bensin" <?= $bbm === 'Bensin' ? 'selected' : '' ?>>Bensin</option>
                    <option value="Listrik" <?= $bbm === 'Listrik' ? 'selected' : '' ?>>Listrik (EV)</option>
                    <option value="Hybrid" <?= $bbm === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Konsumsi Bahan Bakar</label>
                  <input type="text" name="konsumsi_bbm" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['konsumsi_bbm'] ?? $motor['konsumsi_bbm']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Kapasitas Tangki</label>
                  <input type="text" name="kapasitas_tangki" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['kapasitas_tangki'] ?? $motor['kapasitas_tangki']) ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Sistem Pengereman</label>
                  <input type="text" name="sistem_pengereman" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['sistem_pengereman'] ?? $motor['sistem_pengereman']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Sistem Suspensi</label>
                  <input type="text" name="suspensi" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['suspensi'] ?? $motor['suspensi']) ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Dimensi Motor (P x L x T)</label>
                  <input type="text" name="dimensi" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['dimensi'] ?? $motor['dimensi']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Berat Motor</label>
                  <input type="text" name="berat" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['berat'] ?? $motor['berat']) ?>">
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Deskripsi Lengkap Motor</label>
                  <textarea name="deskripsi" rows="3" class="form-control form-control-ms"><?= htmlspecialchars($_POST['deskripsi'] ?? $motor['deskripsi']) ?></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">
                <i data-lucide="image" class="me-1 text-danger" style="width: 18px; height: 18px;"></i> Foto Unit Motor
              </h6>
            </div>
            <div class="ms-card-body text-center">
              <div class="mb-3">
                <img src="<?= getMotorImage($motor['foto']) ?>" alt="Preview" class="ms-image-preview rounded border border-secondary border-opacity-25 w-100" style="max-height: 220px; object-fit: cover;">
              </div>
              <div class="mb-3 text-start">
                <label class="form-label-ms">Ganti File Foto (Opsional)</label>
                <input type="file" name="foto" class="form-control form-control-ms ms-image-upload-input" accept="image/jpeg,image/png,image/webp">
                <div class="text-muted small mt-1">Kosongkan jika tidak ingin mengganti foto saat ini.</div>
              </div>
            </div>
          </div>

          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">
                <i data-lucide="check-square" class="me-1 text-danger" style="width: 18px; height: 18px;"></i> Status Ketersediaan
              </h6>
            </div>
            <div class="ms-card-body">
              <div class="mb-3">
                <label class="form-label-ms">Status Katalog</label>
                <select name="status" class="form-select form-select-ms">
                  <?php $st = $_POST['status'] ?? $motor['status']; ?>
                  <option value="Tersedia" <?= $st === 'Tersedia' ? 'selected' : '' ?>>Tersedia (Ready Stock)</option>
                  <option value="Habis" <?= $st === 'Habis' ? 'selected' : '' ?>>Habis (Out of Stock)</option>
                  <option value="Segera Hadir" <?= $st === 'Segera Hadir' ? 'selected' : '' ?>>Segera Hadir (Coming Soon)</option>
                </select>
              </div>

              <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms py-2 justify-content-center">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Perubahan
                </button>
                <a href="<?= base_url('admin/motor/index.php') ?>" class="btn btn-outline-ms py-2 justify-content-center">
                  Batal
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
