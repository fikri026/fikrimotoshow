<?php
/**
 * Tambah Data Motor Baru
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Tambah Motor Baru - MotoShow';
$activeMenu = 'motor';
$error = '';

// Ambil list merek
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
        // Cek keunikan kode_motor
        $stmt = $pdo->prepare("SELECT id FROM motors WHERE kode_motor = ?");
        $stmt->execute([$kodeMotor]);
        if ($stmt->fetch()) {
            $error = 'Kode motor "' . htmlspecialchars($kodeMotor) . '" sudah digunakan. Silakan gunakan kode lain.';
        } else {
            // Upload Foto jika ada
            $fotoName = null;
            if (!empty($_FILES['foto']['name'])) {
                $uploadResult = handleUploadFoto($_FILES['foto'], 'motors');
                if ($uploadResult['success']) {
                    $fotoName = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            if (empty($error)) {
                $sql = "INSERT INTO motors (
                    kode_motor, nama_motor, brand_id, tipe_motor, tahun_produksi, warna, 
                    harga, stok, kapasitas_mesin, jenis_transmisi, jenis_bahan_bakar, 
                    konsumsi_bbm, kapasitas_tangki, sistem_pengereman, suspensi, 
                    dimensi, berat, deskripsi, foto, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $kodeMotor, $namaMotor, $brandId, $tipeMotor, $tahunProduksi, $warna,
                    $harga, $stok, $kapasitasMesin, $jenisTransmisi, $jenisBahanBakar,
                    $konsumsiBbm, $kapasitasTangki, $sistemPengereman, $suspensi,
                    $dimensi, $berat, $deskripsi, $fotoName, $status
                ]);

                setFlash('success', 'Data motor ' . htmlspecialchars($namaMotor) . ' berhasil ditambahkan!');
                header('Location: ' . base_url('admin/motor/index.php'));
                exit;
            }
        }
    }
}

// Generate default kode motor jika belum diinput
$randomCode = 'MTR-' . strtoupper(substr(uniqid(), -5));

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
        <h4 class="fw-black text-white mb-0">Tambah Unit Motor Baru</h4>
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
        <!-- Kolom Kiri: Informasi Pokok & Spesifikasi Mesin -->
        <div class="col-lg-8">
          <!-- Card 1: Informasi Utama -->
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
                  <input type="text" name="kode_motor" class="form-control form-control-ms" value="<?= htmlspecialchars($_POST['kode_motor'] ?? $randomCode) ?>" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label-ms">Nama Motor *</label>
                  <input type="text" name="nama_motor" class="form-control form-control-ms" placeholder="Contoh: Honda PCX 160 ABS" value="<?= htmlspecialchars($_POST['nama_motor'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label-ms">Merek Motor *</label>
                  <select name="brand_id" class="form-select form-select-ms" required>
                    <option value="">-- Pilih Merek --</option>
                    <?php foreach ($brands as $b): ?>
                      <option value="<?= $b['id'] ?>" <?= (isset($_POST['brand_id']) && $_POST['brand_id'] == $b['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['nama_merek']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Tipe Motor *</label>
                  <select name="tipe_motor" class="form-select form-select-ms" required>
                    <option value="Matic">Matic</option>
                    <option value="Sport">Sport</option>
                    <option value="Naked">Naked</option>
                    <option value="Bebek">Bebek</option>
                    <option value="Trail">Trail</option>
                    <option value="Cruiser">Cruiser</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Tahun Produksi *</label>
                  <input type="number" name="tahun_produksi" class="form-control form-control-ms" min="2000" max="2035" value="<?= htmlspecialchars($_POST['tahun_produksi'] ?? date('Y')) ?>" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label-ms">Warna *</label>
                  <input type="text" name="warna" class="form-control form-control-ms" placeholder="Contoh: Matte Black" value="<?= htmlspecialchars($_POST['warna'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Harga Motor (OTR Rp) *</label>
                  <input type="number" name="harga" class="form-control form-control-ms" placeholder="Contoh: 36500000" min="0" step="50000" value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Stok Awal (Unit) *</label>
                  <input type="number" name="stok" class="form-control form-control-ms" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? 1) ?>" required>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Spesifikasi Teknis Lengkap -->
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
                  <input type="number" name="kapasitas_mesin" class="form-control form-control-ms" placeholder="Contoh: 160" value="<?= htmlspecialchars($_POST['kapasitas_mesin'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Jenis Transmisi *</label>
                  <input type="text" name="jenis_transmisi" class="form-control form-control-ms" placeholder="Contoh: Otomatis / CVT" value="<?= htmlspecialchars($_POST['jenis_transmisi'] ?? 'Otomatis / CVT') ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Jenis Bahan Bakar</label>
                  <select name="jenis_bahan_bakar" class="form-select form-select-ms">
                    <option value="Bensin">Bensin</option>
                    <option value="Listrik">Listrik (EV)</option>
                    <option value="Hybrid">Hybrid</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Konsumsi Bahan Bakar</label>
                  <input type="text" name="konsumsi_bbm" class="form-control form-control-ms" placeholder="Contoh: 45.0 km/liter" value="<?= htmlspecialchars($_POST['konsumsi_bbm'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Kapasitas Tangki</label>
                  <input type="text" name="kapasitas_tangki" class="form-control form-control-ms" placeholder="Contoh: 8.1 Liter" value="<?= htmlspecialchars($_POST['kapasitas_tangki'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Sistem Pengereman</label>
                  <input type="text" name="sistem_pengereman" class="form-control form-control-ms" placeholder="Contoh: Double Disc ABS" value="<?= htmlspecialchars($_POST['sistem_pengereman'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Sistem Suspensi</label>
                  <input type="text" name="suspensi" class="form-control form-control-ms" placeholder="Contoh: Teleskopik Depan & Ganda Belakang" value="<?= htmlspecialchars($_POST['suspensi'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Dimensi Motor (P x L x T)</label>
                  <input type="text" name="dimensi" class="form-control form-control-ms" placeholder="Contoh: 1.936 x 742 x 1.108 mm" value="<?= htmlspecialchars($_POST['dimensi'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Berat Motor</label>
                  <input type="text" name="berat" class="form-control form-control-ms" placeholder="Contoh: 132 kg" value="<?= htmlspecialchars($_POST['berat'] ?? '') ?>">
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Deskripsi Lengkap Motor</label>
                  <textarea name="deskripsi" rows="3" class="form-control form-control-ms" placeholder="Jelaskan fitur unggulan, performa, atau teknologi bawaan motor..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Foto & Status -->
        <div class="col-lg-4">
          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">
                <i data-lucide="image" class="me-1 text-danger" style="width: 18px; height: 18px;"></i> Foto Unit Motor
              </h6>
            </div>
            <div class="ms-card-body text-center">
              <div class="mb-3 text-center">
                <img src="<?= base_url('assets/img/default-motor.svg') ?>" alt="Preview" class="ms-image-preview rounded border border-secondary border-opacity-25 w-100" style="max-height: 220px; object-fit: cover;">
              </div>
              <div class="mb-3 text-start">
                <label class="form-label-ms">Pilih File Foto</label>
                <input type="file" name="foto" class="form-control form-control-ms ms-image-upload-input" accept="image/jpeg,image/png,image/webp">
                <div class="text-muted small mt-1">Format: JPG, PNG, WEBP. Maks 5 MB.</div>
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
                  <option value="Tersedia">Tersedia (Ready Stock)</option>
                  <option value="Habis">Habis (Out of Stock)</option>
                  <option value="Segera Hadir">Segera Hadir (Coming Soon)</option>
                </select>
              </div>

              <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms py-2 justify-content-center">
                  <i data-lucide="save" style="width: 18px; height: 18px;"></i> Simpan Data Motor
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
