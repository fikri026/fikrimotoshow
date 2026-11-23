<?php
/**
 * Riwayat Transaksi - Petugas
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['petugas']);

$pdo = getDBConnection();
$pageTitle = 'Riwayat Transaksi - Petugas MotoShow';
$activeMenu = 'riwayat';

$search = cleanInput($_GET['q'] ?? '');

$sql = "
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.no_hp,
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR ', ') as motor_list
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
$history = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Riwayat Transaksi Penjualan</h4>
        <p class="text-muted small mb-0">Arsip seluruh transaksi yang diproses di showroom MotoShow</p>
      </div>
      <div>
        <a href="<?= base_url('petugas/transaksi_tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Input Transaksi Baru
        </a>
      </div>
    </div>

    <!-- Tabel Riwayat -->
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
            <?php if (empty($history)): ?>
              <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada riwayat transaksi.</td></tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($h['no_transaksi']) ?></td>
                  <td class="text-muted small"><?= date('d/m/Y', strtotime($h['tanggal'])) ?></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($h['nama_pelanggan']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($h['no_hp']) ?></div>
                  </td>
                  <td class="small text-white-50" style="max-width: 220px;"><?= htmlspecialchars($h['motor_list'] ?: '-') ?></td>
                  <td><span class="badge bg-dark border border-secondary text-white-50"><?= htmlspecialchars($h['metode_pembayaran']) ?></span></td>
                  <td class="text-end fw-bold text-danger"><?= formatRupiah($h['total_bayar']) ?></td>
                  <td>
                    <span class="badge <?= $h['status'] === 'Lunas' ? 'badge-status-lunas' : ($h['status'] === 'Cicilan Berjalan' ? 'badge-status-cicilan' : 'badge-status-batal') ?>">
                      <?= htmlspecialchars($h['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/transaksi/detail.php?id=' . $h['id']) ?>" class="btn btn-outline-ms p-1 px-2" title="Detail">
                        <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                      </a>
                      <a href="<?= base_url('admin/transaksi/cetak.php?id=' . $h['id']) ?>" target="_blank" class="btn btn-outline-ms p-1 px-2 text-info" title="Cetak Faktur">
                        <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
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
