<?php
/**
 * Input Pembayaran Angsuran / Pelunasan
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Input Pembayaran - MotoShow';
$activeMenu = 'pembayaran';
$error = '';

$trxIdParam = (int)($_GET['trx_id'] ?? 0);

// Ambil transaksi yang belum lunas atau cicilan berjalan
$transactions = $pdo->query("
    SELECT t.id, t.no_transaksi, t.total_bayar, t.metode_pembayaran, t.status,
           c.nama_lengkap as nama_pelanggan,
           COALESCE((SELECT sisa_bayar FROM payments WHERE transaction_id = t.id ORDER BY id DESC LIMIT 1), t.total_bayar) as sisa_terakhir
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    WHERE t.status IN ('Cicilan Berjalan', 'Pending')
    ORDER BY t.id DESC
")->fetchAll();

$autoNoPay = generateNoPembayaran($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = (int)($_POST['transaction_id'] ?? 0);
    $jumlahBayar = (float)($_POST['jumlah_bayar'] ?? 0);
    $metode = cleanInput($_POST['metode_pembayaran'] ?? 'Tunai');
    $keterangan = cleanInput($_POST['keterangan'] ?? '');

    if (empty($transactionId) || $jumlahBayar <= 0) {
        $error = 'Harap pilih transaksi dan masukkan jumlah pembayaran yang valid.';
    } else {
        try {
            $pdo->beginTransaction();

            // Cek sisa pembayaran terakhir
            $stmtLast = $pdo->prepare("SELECT sisa_bayar, tenor, cicilan_per_bulan FROM payments WHERE transaction_id = ? ORDER BY id DESC LIMIT 1");
            $stmtLast->execute([$transactionId]);
            $lastPay = $stmtLast->fetch();

            $sisaSebelumnya = $lastPay ? (float)$lastPay['sisa_bayar'] : 0;
            $tenor = $lastPay ? (int)$lastPay['tenor'] : 0;
            $cicilan = $lastPay ? (float)$lastPay['cicilan_per_bulan'] : 0;

            $sisaBaru = max(0, $sisaSebelumnya - $jumlahBayar);

            // Bukti transfer jika ada
            $buktiName = null;
            if (!empty($_FILES['bukti_transfer']['name'])) {
                $uploadRes = handleUploadFoto($_FILES['bukti_transfer'], 'payments');
                if ($uploadRes['success']) {
                    $buktiName = $uploadRes['filename'];
                }
            }

            $noPay = generateNoPembayaran($pdo);
            $stmtInsert = $pdo->prepare("
                INSERT INTO payments (transaction_id, no_pembayaran, tanggal_bayar, jumlah_bayar, metode_pembayaran, dp, tenor, cicilan_per_bulan, sisa_bayar, bukti_transfer, status, keterangan)
                VALUES (?, ?, NOW(), ?, ?, 0, ?, ?, ?, ?, 'Lunas', ?)
            ");
            $stmtInsert->execute([
                $transactionId,
                $noPay,
                $jumlahBayar,
                $metode,
                $tenor,
                $cicilan,
                $sisaBaru,
                $buktiName,
                $keterangan ?: 'Pembayaran Angsuran Cicilan'
            ]);

            // Jika sisa bayar sudah 0, update transaksi menjadi Lunas!
            if ($sisaBaru <= 0) {
                $stmtUpdateTrx = $pdo->prepare("UPDATE transactions SET status = 'Lunas' WHERE id = ?");
                $stmtUpdateTrx->execute([$transactionId]);
            }

            $pdo->commit();

            setFlash('success', 'Pembayaran senilai ' . formatRupiah($jumlahBayar) . ' berhasil dicatat!');
            $redir = ($_SESSION['user_role'] === 'admin') ? 'admin/pembayaran/index.php' : 'petugas/pembayaran.php';
            header('Location: ' . base_url($redir));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan pembayaran: ' . $e->getMessage();
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
        <a href="<?= base_url($_SESSION['user_role'] === 'admin' ? 'admin/pembayaran/index.php' : 'petugas/pembayaran.php') ?>" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
          <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Data Pembayaran
        </a>
        <h4 class="fw-black text-white mb-0">Input Pembayaran / Angsuran</h4>
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
            <form method="POST" action="" enctype="multipart/form-data">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label-ms">Nomor Pembayaran</label>
                  <input type="text" class="form-control form-control-ms font-monospace text-danger fw-bold" value="<?= $autoNoPay ?>" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label-ms">Tanggal Pembayaran</label>
                  <input type="text" class="form-control form-control-ms" value="<?= date('d/m/Y H:i') ?> WIB" readonly>
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Pilih Transaksi yang Ditagih *</label>
                  <select name="transaction_id" id="trxSelect" class="form-select form-select-ms" required>
                    <option value="">-- Pilih Transaksi Belum Lunas / Cicilan Berjalan --</option>
                    <?php foreach ($transactions as $t): ?>
                      <option value="<?= $t['id'] ?>" data-sisa="<?= $t['sisa_terakhir'] ?>" <?= ($trxIdParam == $t['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['no_transaksi']) ?> &bull; <?= htmlspecialchars($t['nama_pelanggan']) ?> (Sisa Tagihan: <?= formatRupiah($t['sisa_terakhir']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Jumlah yang Dibayarkan (Rp) *</label>
                  <input type="number" name="jumlah_bayar" id="inputBayar" class="form-control form-control-ms" placeholder="Contoh: 3500000" min="10000" step="50000" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label-ms">Metode Pembayaran *</label>
                  <select name="metode_pembayaran" class="form-select form-select-ms" required>
                    <option value="Tunai">Tunai (Kasir Showroom)</option>
                    <option value="Transfer">Transfer Rekening Bank Showroom</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Unggah Bukti Struk / Transfer (Opsional)</label>
                  <input type="file" name="bukti_transfer" class="form-control form-control-ms" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="col-12">
                  <label class="form-label-ms">Keterangan / Catatan Pembayaran</label>
                  <input type="text" name="keterangan" class="form-control form-control-ms" placeholder="Contoh: Pembayaran angsuran cicilan ke-2">
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary-ms">
                  <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i> Simpan Pembayaran
                </button>
                <a href="<?= base_url($_SESSION['user_role'] === 'admin' ? 'admin/pembayaran/index.php' : 'petugas/pembayaran.php') ?>" class="btn btn-outline-ms">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="ms-card p-4">
          <h6 class="fw-bold text-white mb-3">Informasi Pelunasan</h6>
          <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 mb-3">
            <div class="text-muted small">Sisa Tagihan Saat Ini</div>
            <div class="fs-4 fw-bold text-white" id="infoSisa">Rp 0</div>
          </div>
          <p class="text-muted small mb-0 lh-base">
            Jika jumlah yang dibayarkan mencukupi untuk melunasi seluruh sisa tagihan, status transaksi penjualan akan otomatis diperbarui menjadi <strong>Lunas</strong>.
          </p>
        </div>
      </div>
    </div>

  </div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const trxSelect = document.getElementById('trxSelect');
    const infoSisa = document.getElementById('infoSisa');
    const inputBayar = document.getElementById('inputBayar');

    function checkSisa() {
      const opt = trxSelect.options[trxSelect.selectedIndex];
      if (opt && opt.value) {
        const sisa = parseFloat(opt.getAttribute('data-sisa')) || 0;
        infoSisa.innerText = formatRupiahJs(sisa);
        if (!inputBayar.value) {
          inputBayar.value = sisa;
        }
      } else {
        infoSisa.innerText = 'Rp 0';
      }
    }

    if (trxSelect) {
      trxSelect.addEventListener('change', checkSisa);
      checkSisa();
    }
  });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
