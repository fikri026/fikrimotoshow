<?php
/**
 * Data Pembayaran & Input Angsuran - Petugas
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Kelola Pembayaran - Petugas MotoShow';
$activeMenu = 'pembayaran';

$search = cleanInput($_GET['q'] ?? '');

$sql = "
    SELECT p.*, t.no_transaksi, c.nama_lengkap as nama_pelanggan
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    JOIN customers c ON t.customer_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.no_pembayaran LIKE ? OR t.no_transaksi LIKE ? OR c.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Pembayaran</h4>
        <p class="text-muted small mb-0">Catat penerimaan angsuran cicilan motor dan cetak bukti kuitansi</p>
      </div>
      <div>
        <a href="<?= base_url('admin/pembayaran/input.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Input Pembayaran Angsuran
        </a>
      </div>
    </div>

    <!-- Search Form -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-6">
            <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari No. Bayar, Transaksi, atau Pelanggan..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary-ms">
              <i data-lucide="search" style="width: 16px; height: 16px;"></i> Cari
            </button>
            <?php if (!empty($search)): ?>
              <a href="<?= base_url('petugas/pembayaran.php') ?>" class="btn btn-outline-ms">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Pembayaran -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>No. Pembayaran</th>
              <th>Transaksi & Pelanggan</th>
              <th>Tanggal</th>
              <th>Jumlah Bayar</th>
              <th>Metode</th>
              <th>Sisa Bayar</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat pembayaran.</td></tr>
            <?php else: ?>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($p['no_pembayaran']) ?></td>
                  <td>
                    <div class="fw-semibold text-danger"><?= htmlspecialchars($p['no_transaksi']) ?></div>
                    <div class="text-white small"><?= htmlspecialchars($p['nama_pelanggan']) ?></div>
                  </td>
                  <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></td>
                  <td class="fw-bold text-success"><?= formatRupiah($p['jumlah_bayar']) ?></td>
                  <td><span class="badge bg-dark border border-secondary text-white-50"><?= htmlspecialchars($p['metode_pembayaran']) ?></span></td>
                  <td class="text-white small"><?= $p['sisa_bayar'] > 0 ? formatRupiah($p['sisa_bayar']) : '-' ?></td>
                  <td>
                    <span class="badge <?= $p['status'] === 'Lunas' ? 'badge-status-lunas' : 'badge-status-pending' ?>">
                      <?= htmlspecialchars($p['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="<?= base_url('admin/pembayaran/invoice.php?id=' . $p['id']) ?>" target="_blank" class="btn btn-outline-ms btn-sm p-1 px-2" title="Cetak Kuitansi">
                      <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Kuitansi
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
