<?php
/**
 * Riwayat Pembelian & Faktur - Pelanggan
 */
require_once __DIR__ . '/../config/database.php';
checkAuth(['pelanggan']);

$pdo = getDBConnection();
$pageTitle = 'Riwayat Pembelian - MotoShow';
$activeMenu = 'riwayat';

// Customer ID
$stmtC = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmtC->execute([$_SESSION['user_id']]);
$cust = $stmtC->fetch();
$customerId = $cust ? $cust['id'] : 0;

$stmtTrx = $pdo->prepare("
    SELECT t.*, 
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR ', ') as motor_list
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN motors m ON td.motor_id = m.id
    WHERE t.customer_id = ?
    GROUP BY t.id
    ORDER BY t.id DESC
");
$stmtTrx->execute([$customerId]);
$purchases = $stmtTrx->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="page-body">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Riwayat Pembelian & Faktur Motor</h4>
        <p class="text-muted small mb-0">Arsip pembelian resmi unit kendaraan bermotor dan bukti faktur sah kepemilikan</p>
      </div>
      <div>
        <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="shopping-bag" style="width: 18px; height: 18px;"></i> Katalog Motor
        </a>
      </div>
    </div>

    <!-- Tabel Riwayat Pembelian -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>No. Faktur</th>
              <th>Tanggal Pembelian</th>
              <th>Unit Motor</th>
              <th>Metode Pembayaran</th>
              <th class="text-end">Total Pembayaran</th>
              <th>Status Transaksi</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($purchases)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i data-lucide="receipt" style="width: 48px; height: 48px; opacity: 0.3;" class="mb-2"></i>
                  <div>Anda belum memiliki riwayat pembelian motor di showroom MotoShow.</div>
                  <a href="<?= base_url('pelanggan/katalog.php') ?>" class="btn btn-outline-ms btn-sm mt-3">Lihat Katalog Motor</a>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($purchases as $p): ?>
                <tr>
                  <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($p['no_transaksi']) ?></td>
                  <td class="text-muted small"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                  <td class="small text-white-50" style="max-width: 250px;"><?= htmlspecialchars($p['motor_list'] ?: '-') ?></td>
                  <td>
                    <span class="badge bg-dark border border-secondary text-white-50"><?= htmlspecialchars($p['metode_pembayaran']) ?></span>
                  </td>
                  <td class="text-end fw-bold text-danger fs-6"><?= formatRupiah($p['total_bayar']) ?></td>
                  <td>
                    <?php if ($p['status'] === 'Lunas'): ?>
                      <span class="badge badge-status-lunas">Lunas</span>
                    <?php elseif ($p['status'] === 'Cicilan Berjalan'): ?>
                      <span class="badge badge-status-cicilan">Cicilan</span>
                    <?php else: ?>
                      <span class="badge badge-status-batal"><?= htmlspecialchars($p['status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <a href="<?= base_url('admin/transaksi/cetak.php?id=' . $p['id']) ?>" target="_blank" class="btn btn-outline-ms btn-sm p-1 px-3 text-info" title="Lihat Faktur">
                      <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Faktur
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
