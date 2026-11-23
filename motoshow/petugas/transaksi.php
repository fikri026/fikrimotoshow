<?php
/**
 * Transaksi Penjualan - Petugas
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Transaksi Kasir - Petugas MotoShow';
$activeMenu = 'transaksi';

$search = cleanInput($_GET['q'] ?? '');

$sql = "
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.no_hp,
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR ', ') as motor_dibeli
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN motors m ON td.motor_id = m.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (t.no_transaksi LIKE ? OR c.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY t.id ORDER BY t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Transaksi Penjualan Showroom</h4>
        <p class="text-muted small mb-0">Input kasir, cetak faktur, dan status pelunasan transaksi</p>
      </div>
      <div>
        <a href="<?= base_url('petugas/transaksi_tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Input Transaksi Baru
        </a>
      </div>
    </div>

    <!-- Tabel Transaksi -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>No. Transaksi</th>
              <th>Tanggal</th>
              <th>Pelanggan</th>
              <th>Unit Motor</th>
              <th>Metode</th>
              <th class="text-end">Total Bayar</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transactions)): ?>
              <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada transaksi.</td></tr>
            <?php else: ?>
              <?php foreach ($transactions as $t): ?>
                <tr>
                  <td>
                    <a href="<?= base_url('admin/transaksi/detail.php?id=' . $t['id']) ?>" class="fw-bold text-white text-decoration-underline font-monospace">
                      <?= htmlspecialchars($t['no_transaksi']) ?>
                    </a>
                  </td>
                  <td class="text-muted small"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($t['nama_pelanggan']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($t['no_hp']) ?></div>
                  </td>
                  <td class="small text-white-50" style="max-width: 220px;"><?= htmlspecialchars($t['motor_dibeli'] ?: '-') ?></td>
                  <td><span class="badge bg-dark border border-secondary text-white-50"><?= htmlspecialchars($t['metode_pembayaran']) ?></span></td>
                  <td class="text-end fw-bold text-danger"><?= formatRupiah($t['total_bayar']) ?></td>
                  <td>
                    <?php if ($t['status'] === 'Lunas'): ?>
                      <span class="badge badge-status-lunas">Lunas</span>
                    <?php elseif ($t['status'] === 'Cicilan Berjalan'): ?>
                      <span class="badge badge-status-cicilan">Cicilan</span>
                    <?php else: ?>
                      <span class="badge badge-status-pending"><?= htmlspecialchars($t['status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/transaksi/detail.php?id=' . $t['id']) ?>" class="btn btn-outline-ms p-1 px-2" title="Detail">
                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                      </a>
                      <a href="<?= base_url('admin/transaksi/cetak.php?id=' . $t['id']) ?>" target="_blank" class="btn btn-outline-ms p-1 px-2 text-info" title="Cetak Faktur">
                        <i data-lucide="printer" style="width: 15px; height: 15px;"></i>
                      </a>
                    </div>
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
