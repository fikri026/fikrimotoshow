<?php
/**
 * Pemesanan Motor (Booking) - Pelanggan
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['pelanggan']);

$pdo = getDBConnection();
$pageTitle = 'Pemesanan Motor - MotoShow';
$activeMenu = 'pemesanan';
$error = '';

// Ambil customer ID
$stmtC = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmtC->execute([$_SESSION['user_id']]);
$customer = $stmtC->fetch();

if (!$customer) {
    // Buat customer record jika belum ada
    $stmtC = $pdo->prepare("INSERT INTO customers (user_id, nik, nama_lengkap, no_hp, email, alamat) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtC->execute([
        $_SESSION['user_id'],
        '320101' . rand(1000000000, 9999999999),
        $_SESSION['user_name'],
        $_SESSION['user']['no_hp'] ?? '08123456789',
        $_SESSION['user_email'] ?? '',
        'Alamat Showroom Konsumen'
    ]);
    $customerId = $pdo->lastInsertId();
} else {
    $customerId = $customer['id'];
}

// Handle Cancel Booking
if (isset($_GET['cancel_id'])) {
    $cancelId = (int)$_GET['cancel_id'];
    $stmtCancel = $pdo->prepare("UPDATE bookings SET status = 'Dibatalkan' WHERE id = ? AND customer_id = ? AND status = 'Menunggu Konfirmasi'");
    $stmtCancel->execute([$cancelId, $customerId]);
    setFlash('success', 'Pemesanan berhasil dibatalkan.');
    header('Location: ' . base_url('pelanggan/pemesanan.php'));
    exit;
}

// Handle Booking Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motorId = (int)($_POST['motor_id'] ?? 0);
    $rencanaBayar = cleanInput($_POST['rencana_pembayaran'] ?? 'Tunai');
    $rencanaDp = (float)($_POST['rencana_dp'] ?? 0);
    $rencanaTenor = (int)($_POST['rencana_tenor'] ?? 0);
    $catatan = cleanInput($_POST['catatan'] ?? '');

    if ($motorId <= 0) {
        $error = 'Harap pilih unit motor yang ingin dipesan.';
    } else {
        $noBooking = generateNoBooking($pdo);
        $stmtBkg = $pdo->prepare("
            INSERT INTO bookings (no_booking, customer_id, motor_id, tanggal_booking, rencana_pembayaran, rencana_dp, rencana_tenor, catatan, status)
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'Menunggu Konfirmasi')
        ");
        $stmtBkg->execute([
            $noBooking,
            $customerId,
            $motorId,
            $rencanaBayar,
            $rencanaDp,
            $rencanaTenor,
            $catatan
        ]);

        setFlash('success', 'Pengajuan booking motor dengan nomor ' . $noBooking . ' berhasil dikirim! Tim sales showroom kami akan segera menghubungi Anda.');
        header('Location: ' . base_url('pelanggan/pemesanan.php'));
        exit;
    }
}

// Ambil motor tersedia untuk dropdown booking
$motors = $pdo->query("SELECT m.id, m.nama_motor, m.harga, m.warna, b.nama_merek FROM motors m JOIN brands b ON m.brand_id = b.id WHERE m.status = 'Tersedia' ORDER BY m.nama_motor ASC")->fetchAll();
$selectedMotorId = (int)($_GET['motor_id'] ?? 0);

// Ambil riwayat booking user ini
$stmtMyBkg = $pdo->prepare("
    SELECT b.*, m.nama_motor, m.harga, m.warna, m.foto, br.nama_merek 
    FROM bookings b 
    JOIN motors m ON b.motor_id = m.id 
    JOIN brands br ON m.brand_id = br.id 
    WHERE b.customer_id = ? 
    ORDER BY b.id DESC
");
$stmtMyBkg->execute([$customerId]);
$myBookings = $stmtMyBkg->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Pemesanan Sepeda Motor (Booking)</h4>
        <p class="text-muted small mb-0">Ajukan reservasi unit motor pilihan Anda tanpa harus antre lama di showroom</p>
      </div>
      <div>
        <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-outline-ms">
          <i data-lucide="bike" style="width: 18px; height: 18px;"></i> Lihat Katalog Lainnya
        </a>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2" style="width: 20px; height: 20px;"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <!-- Form Booking Baru -->
      <div class="col-lg-5">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
              <i data-lucide="plus-circle" class="text-danger" style="width: 18px; height: 18px;"></i> Form Booking Motor Baru
            </h6>
          </div>
          <div class="ms-card-body">
            <form method="POST" action="">
              <div class="mb-3">
                <label class="form-label-ms">Pilih Unit Motor *</label>
                <select name="motor_id" id="motorSelect" class="form-select form-select-ms" required>
                  <option value="">-- Pilih Model Motor --</option>
                  <?php foreach ($motors as $m): ?>
                    <option value="<?= $m['id'] ?>" data-price="<?= $m['harga'] ?>" <?= ($selectedMotorId == $m['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($m['nama_merek']) ?> - <?= htmlspecialchars($m['nama_motor']) ?> (<?= formatRupiah($m['harga']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label-ms">Rencana Cara Pembayaran *</label>
                <select name="rencana_pembayaran" id="rencanaBayarSelect" class="form-select form-select-ms" required onchange="toggleKreditFields(this.value)">
                  <option value="Tunai">Tunai / Cash</option>
                  <option value="Transfer">Transfer Bank</option>
                  <option value="Kredit">Kredit / Cicilan Showroom</option>
                </select>
              </div>

              <div id="kreditFields" style="display: none;" class="p-3 bg-dark rounded border border-secondary border-opacity-25 mb-3">
                <div class="mb-2">
                  <label class="form-label-ms small">Perkiraan Uang Muka (DP Rp)</label>
                  <input type="number" name="rencana_dp" class="form-control form-control-ms" placeholder="Contoh: 5000000" min="0" step="500000" value="5000000">
                </div>
                <div>
                  <label class="form-label-ms small">Rencana Jangka Tenor</label>
                  <select name="rencana_tenor" class="form-select form-select-ms">
                    <option value="12">12 Bulan</option>
                    <option value="24" selected>24 Bulan</option>
                    <option value="36">36 Bulan</option>
                  </select>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label-ms">Catatan Tambahan / Preferensi Pengambilan</label>
                <textarea name="catatan" rows="3" class="form-control form-control-ms" placeholder="Contoh: Warna favorit, rencana survei showroom hari Sabtu, minta dihubungi via WA..."></textarea>
              </div>

              <button type="submit" class="btn btn-primary-ms w-100 justify-content-center py-2">
                <i data-lucide="send" style="width: 18px; height: 18px;"></i> Kirim Pengajuan Booking
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Riwayat Booking Saya -->
      <div class="col-lg-7">
        <div class="ms-card h-100">
          <div class="ms-card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-white mb-0">Daftar Antrean & Status Booking Anda</h6>
            <span class="badge bg-dark border border-secondary text-white-50"><?= count($myBookings) ?> Data</span>
          </div>
          <div class="table-responsive">
            <table class="table table-custom-dark mb-0">
              <thead>
                <tr>
                  <th>No. Booking</th>
                  <th>Unit Motor</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($myBookings)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i data-lucide="inbox" style="width: 36px; height: 36px; opacity: 0.3;" class="mb-2"></i>
                      <div>Belum ada pengajuan booking. Silakan pilih unit di form sebelah kiri.</div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($myBookings as $b): ?>
                    <tr>
                      <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($b['no_booking']) ?></td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="<?= getMotorImage($b['foto']) ?>" alt="<?= htmlspecialchars($b['nama_motor']) ?>" class="rounded" style="width: 40px; height: 28px; object-fit: cover;">
                          <div>
                            <div class="fw-semibold text-white small"><?= htmlspecialchars($b['nama_motor']) ?></div>
                            <div class="text-danger small" style="font-size: 0.72rem;"><?= formatRupiah($b['harga']) ?> &bull; <?= htmlspecialchars($b['rencana_pembayaran']) ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="text-muted small"><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                      <td>
                        <?php if ($b['status'] === 'Dikonfirmasi'): ?>
                          <span class="badge badge-status-tersedia">Dikonfirmasi</span>
                        <?php elseif ($b['status'] === 'Menunggu Konfirmasi'): ?>
                          <span class="badge badge-status-cicilan">Menunggu</span>
                        <?php elseif ($b['status'] === 'Selesai'): ?>
                          <span class="badge badge-status-lunas">Selesai</span>
                        <?php else: ?>
                          <span class="badge badge-status-batal">Dibatalkan</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <?php if ($b['status'] === 'Menunggu Konfirmasi'): ?>
                          <a href="<?= base_url('pelanggan/pemesanan.php?cancel_id=' . $b['id']) ?>" class="btn btn-outline-ms btn-sm p-1 px-2 text-danger" title="Batalkan Booking" onclick="return confirm('Apakah Anda yakin ingin membatalkan pengajuan booking ini?')">
                            Batalkan
                          </a>
                        <?php else: ?>
                          <span class="text-muted small">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

<script>
  function toggleKreditFields(val) {
    const kf = document.getElementById('kreditFields');
    if (val === 'Kredit') {
      kf.style.display = 'block';
    } else {
      kf.style.display = 'none';
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
