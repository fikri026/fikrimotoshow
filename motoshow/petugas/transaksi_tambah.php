<?php
/**
 * Input Transaksi Baru - Petugas
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Kasir Penjualan Motor - Petugas MotoShow';
$activeMenu = 'transaksi';
$error = '';

$customers = $pdo->query("SELECT id, nama_lengkap, nik, no_hp FROM customers ORDER BY nama_lengkap ASC")->fetchAll();
$motors = $pdo->query("
    SELECT m.id, m.kode_motor, m.nama_motor, m.warna, m.harga, m.stok, b.nama_merek 
    FROM motors m 
    JOIN brands b ON m.brand_id = b.id 
    WHERE m.status = 'Tersedia' AND m.stok > 0 
    ORDER BY m.nama_motor ASC
")->fetchAll();

$autoNoTrx = generateNoTransaksi($pdo);
$preSelectedMotor = (int)($_GET['motor_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noTransaksi = cleanInput($_POST['no_transaksi'] ?? '');
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $motorId = (int)($_POST['motor_id'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $diskon = (float)($_POST['diskon'] ?? 0);
    $metodePembayaran = cleanInput($_POST['metode_pembayaran'] ?? 'Tunai');
    $catatan = cleanInput($_POST['catatan'] ?? '');
    $tanggal = cleanInput($_POST['tanggal'] ?? date('Y-m-d'));

    $dp = (float)($_POST['dp'] ?? 0);
    $tenor = (int)($_POST['tenor'] ?? 12);

    if (empty($customerId) || empty($motorId) || $jumlah <= 0) {
        $error = 'Harap pilih pelanggan dan unit motor yang valid.';
    } else {
        $stmtMotor = $pdo->prepare("SELECT id, nama_motor, harga, stok FROM motors WHERE id = ? FOR UPDATE");

        try {
            $pdo->beginTransaction();

            $stmtMotor->execute([$motorId]);
            $motorData = $stmtMotor->fetch();

            if (!$motorData) {
                throw new Exception('Data motor tidak ditemukan.');
            }

            if ($motorData['stok'] < $jumlah) {
                throw new Exception('Stok tidak mencukupi (Tersisa: ' . $motorData['stok'] . ' unit).');
            }

            $hargaSatuan = (float)$motorData['harga'];
            $totalHarga = $hargaSatuan * $jumlah;
            $totalBayar = max(0, $totalHarga - $diskon);

            $statusTransaksi = ($metodePembayaran === 'Kredit') ? 'Cicilan Berjalan' : 'Lunas';
            $sisaBayar = ($metodePembayaran === 'Kredit') ? max(0, $totalBayar - $dp) : 0;
            $cicilanPerBulan = ($metodePembayaran === 'Kredit' && $tenor > 0) ? round($sisaBayar / $tenor) : 0;

            // 1. Simpan Transaksi
            $stmtTrx = $pdo->prepare("
                INSERT INTO transactions (no_transaksi, customer_id, user_id, tanggal, total_harga, diskon, total_bayar, metode_pembayaran, status, catatan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtTrx->execute([
                $noTransaksi, $customerId, $_SESSION['user_id'], $tanggal,
                $totalHarga, $diskon, $totalBayar, $metodePembayaran, $statusTransaksi, $catatan
            ]);
            $transactionId = $pdo->lastInsertId();

            // 2. Simpan Detail Transaksi
            $stmtDetail = $pdo->prepare("
                INSERT INTO transaction_details (transaction_id, motor_id, jumlah, harga_satuan, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtDetail->execute([$transactionId, $motorId, $jumlah, $hargaSatuan, $totalHarga]);

            // 3. PENGURANGAN STOK MOTOR OTOMATIS
            $newStok = $motorData['stok'] - $jumlah;
            $newStatus = ($newStok <= 0) ? 'Habis' : 'Tersedia';
            $stmtUpdateStok = $pdo->prepare("UPDATE motors SET stok = ?, status = ? WHERE id = ?");
            $stmtUpdateStok->execute([$newStok, $newStatus, $motorId]);

            // 4. Catat Pembayaran
            $noPembayaran = generateNoPembayaran($pdo);
            $jumlahBayarAwal = ($metodePembayaran === 'Kredit') ? $dp : $totalBayar;
            $keteranganBayar = ($metodePembayaran === 'Kredit') ? 'Uang Muka (DP) Kredit ' . $tenor . ' Bulan' : 'Pelunasan Transaksi Kasir';

            $stmtPay = $pdo->prepare("
                INSERT INTO payments (transaction_id, no_pembayaran, tanggal_bayar, jumlah_bayar, metode_pembayaran, dp, tenor, cicilan_per_bulan, sisa_bayar, status, keterangan)
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'Lunas', ?)
            ");
            $stmtPay->execute([
                $transactionId, $noPembayaran, $jumlahBayarAwal, $metodePembayaran,
                $dp, $tenor, $cicilanPerBulan, $sisaBayar, $keteranganBayar
            ]);

            $pdo->commit();

            setFlash('success', 'Transaksi kasir ' . htmlspecialchars($noTransaksi) . ' berhasil diselesaikan! Stok otomatis diperbarui.');
            header('Location: ' . base_url('admin/transaksi/detail.php?id=' . $transactionId));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal memproses transaksi: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <a href="<?= base_url('petugas/transaksi.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Transaksi
        </a>
        <h4 class="fw-black text-white mb-0">Kasir Transaksi Penjualan</h4>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2" style="width: 20px; height: 20px;"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">Form Pemesanan & Kasir</h6>
            </div>
            <div class="ms-card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label-ms">No. Transaksi</label>
                  <input type="text" name="no_transaksi" class="form-control form-control-ms font-monospace fw-bold text-danger" value="<?= $autoNoTrx ?>" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Tanggal</label>
                  <input type="date" name="tanggal" class="form-control form-control-ms" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-12">
                  <label class="form-label-ms">Pilih Pelanggan *</label>
                  <select name="customer_id" class="form-select form-select-ms" required>
                    <option value="">-- Pilih Konsumen --</option>
                    <?php foreach ($customers as $c): ?>
                      <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['nama_lengkap']) ?> (NIK: <?= htmlspecialchars($c['nik']) ?> - HP: <?= htmlspecialchars($c['no_hp']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-8">
                  <label class="form-label-ms">Pilih Unit Motor *</label>
                  <select name="motor_id" id="motorSelect" class="form-select form-select-ms" required>
                    <option value="">-- Pilih Unit Motor --</option>
                    <?php foreach ($motors as $m): ?>
                      <option value="<?= $m['id'] ?>" data-price="<?= $m['harga'] ?>" data-stok="<?= $m['stok'] ?>" <?= ($preSelectedMotor == $m['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nama_merek']) ?> - <?= htmlspecialchars($m['nama_motor']) ?> (Stok: <?= $m['stok'] ?> | <?= formatRupiah($m['harga']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label-ms">Jumlah Unit *</label>
                  <input type="number" name="jumlah" id="jumlahBeli" class="form-control form-control-ms" value="1" min="1" max="10" required>
                  <div id="stokAlert" class="small text-muted mt-1">Stok: -</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Harga Satuan OTR (Rp)</label>
                  <input type="text" id="displayHargaSatuan" class="form-control form-control-ms fw-bold" value="Rp 0" readonly>
                  <input type="hidden" id="transaksiTotal" value="0" data-price="0">
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Potongan Diskon (Rp)</label>
                  <input type="number" name="diskon" id="transaksiDiskon" class="form-control form-control-ms" value="0" min="0" step="100000">
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Catatan</label>
                  <textarea name="catatan" rows="2" class="form-control form-control-ms" placeholder="Helm, jaket, plat nomor, perlengkapan berkendara..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="ms-card mb-4">
            <div class="ms-card-header">
              <h6 class="fw-bold text-white mb-0">Metode & Skema Pembayaran</h6>
            </div>
            <div class="ms-card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label-ms">Metode Pembayaran *</label>
                  <select name="metode_pembayaran" id="metodePembayaran" class="form-select form-select-ms" required>
                    <option value="Tunai">Tunai (Cash di Kasir)</option>
                    <option value="Transfer">Transfer Rekening Resmi Bank</option>
                    <option value="Kredit">Kredit / Cicilan Showroom</option>
                  </select>
                </div>
              </div>

              <!-- Skema Kredit -->
              <div id="kreditSection" style="display: none;" class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label-ms">Uang Muka (DP Rp) *</label>
                    <input type="number" name="dp" id="transaksiDP" class="form-control form-control-ms" value="5000000" min="0" step="500000">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label-ms">Pilihan Tenor *</label>
                    <select name="tenor" id="transaksiTenor" class="form-select form-select-ms">
                      <option value="12">12 Bulan (1 Tahun)</option>
                      <option value="24" selected>24 Bulan (2 Tahun)</option>
                      <option value="36">36 Bulan (3 Tahun)</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <div class="p-3 bg-dark rounded border border-secondary border-opacity-25">
                      <div class="text-muted small">Sisa Pembayaran</div>
                      <div class="fs-5 fw-bold text-white" id="sisaBayarDisplay">Rp 0</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="p-3 bg-dark rounded border border-secondary border-opacity-25">
                      <div class="text-muted small">Angsuran per Bulan</div>
                      <div class="fs-5 fw-bold text-danger" id="cicilanDisplay">Rp 0 / bulan</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="ms-card p-4 position-sticky" style="top: 90px;">
            <h6 class="fw-bold text-white mb-3">Ringkasan Kasir</h6>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small">Subtotal Unit</span>
              <span class="text-white fw-bold small" id="subtotalDisplay">Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted small">Diskon</span>
              <span class="text-success fw-bold small" id="diskonDisplay">Rp 0</span>
            </div>
            <hr class="border-secondary border-opacity-25 my-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <span class="fw-bold text-white">Total Tagihan</span>
              <span class="fs-4 fw-black text-danger" id="totalBayarDisplay">Rp 0</span>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary-ms py-2 justify-content-center">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i> Selesaikan Transaksi
              </button>
              <a href="<?= base_url('petugas/transaksi.php') ?>" class="btn btn-outline-ms py-2 justify-content-center">Batal</a>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const motorSelect = document.getElementById('motorSelect');
    const displayHargaSatuan = document.getElementById('displayHargaSatuan');
    const transaksiTotal = document.getElementById('transaksiTotal');
    const jumlahBeli = document.getElementById('jumlahBeli');
    const stokAlert = document.getElementById('stokAlert');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const diskonDisplay = document.getElementById('diskonDisplay');
    const inputDiskon = document.getElementById('transaksiDiskon');

    function updateMotorInfo() {
      const selectedOption = motorSelect.options[motorSelect.selectedIndex];
      if (selectedOption && selectedOption.value) {
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const stok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
        const qty = parseInt(jumlahBeli.value) || 1;

        displayHargaSatuan.value = formatRupiahJs(price);
        stokAlert.innerText = 'Stok fisik tersedia: ' + stok + ' unit';
        stokAlert.className = stok > 0 ? 'text-success small mt-1' : 'text-danger small mt-1';
        jumlahBeli.max = stok;

        const subtotal = price * qty;
        transaksiTotal.value = subtotal;
        transaksiTotal.setAttribute('data-price', subtotal);
        subtotalDisplay.innerText = formatRupiahJs(subtotal);

        const diskon = parseFloat(inputDiskon.value) || 0;
        diskonDisplay.innerText = '-' + formatRupiahJs(diskon);

        const totalBersih = Math.max(0, subtotal - diskon);
        document.getElementById('totalBayarDisplay').innerText = formatRupiahJs(totalBersih);
      } else {
        displayHargaSatuan.value = 'Rp 0';
        transaksiTotal.value = 0;
        transaksiTotal.setAttribute('data-price', 0);
        stokAlert.innerText = 'Stok tersedia: -';
        subtotalDisplay.innerText = 'Rp 0';
        diskonDisplay.innerText = 'Rp 0';
        document.getElementById('totalBayarDisplay').innerText = 'Rp 0';
      }
    }

    if (motorSelect) {
      motorSelect.addEventListener('change', updateMotorInfo);
      if (motorSelect.value) updateMotorInfo();
    }
    if (jumlahBeli) jumlahBeli.addEventListener('input', updateMotorInfo);
    if (inputDiskon) inputDiskon.addEventListener('input', updateMotorInfo);
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
