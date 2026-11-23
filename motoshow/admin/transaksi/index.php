<?php
/**
 * Manajemen Transaksi Penjualan - List
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Transaksi Penjualan - MotoShow';
$activeMenu = 'transaksi';

$search = cleanInput($_GET['q'] ?? '');
$filterMetode = cleanInput($_GET['metode'] ?? '');
$filterStatus = cleanInput($_GET['status'] ?? '');
$tglMulai = cleanInput($_GET['tgl_mulai'] ?? '');
$tglSelesai = cleanInput($_GET['tgl_selesai'] ?? '');

$sql = "
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.no_hp, u.nama_lengkap as nama_kasir,
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR ', ') as motor_dibeli
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN motors m ON td.motor_id = m.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (t.no_transaksi LIKE ? OR c.nama_lengkap LIKE ? OR m.nama_motor LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterMetode)) {
    $sql .= " AND t.metode_pembayaran = ?";
    $params[] = $filterMetode;
}

if (!empty($filterStatus)) {
    $sql .= " AND t.status = ?";
    $params[] = $filterStatus;
}

if (!empty($tglMulai) && !empty($tglSelesai)) {
    $sql .= " AND t.tanggal BETWEEN ? AND ?";
    $params[] = $tglMulai;
    $params[] = $tglSelesai;
}

$sql .= " GROUP BY t.id ORDER BY t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Transaksi Penjualan Motor</h4>
        <p class="text-muted small mb-0">Kelola order penjualan tunai, transfer, dan simulasi pembiayaan kredit</p>
      </div>
      <div>
        <a href="<?= base_url('admin/transaksi/tambah.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Input Transaksi Baru
        </a>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-3">
            <input type="text" name="q" class="form-control form-control-ms" placeholder="No. Transaksi / Pelanggan..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-2">
            <select name="metode" class="form-select form-select-ms">
              <option value="">Semua Metode</option>
              <option value="Tunai" <?= $filterMetode === 'Tunai' ? 'selected' : '' ?>>Tunai</option>
              <option value="Transfer" <?= $filterMetode === 'Transfer' ? 'selected' : '' ?>>Transfer</option>
              <option value="Kredit" <?= $filterMetode === 'Kredit' ? 'selected' : '' ?>>Kredit</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-ms">
              <option value="">Semua Status</option>
              <option value="Lunas" <?= $filterStatus === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
              <option value="Cicilan Berjalan" <?= $filterStatus === 'Cicilan Berjalan' ? 'selected' : '' ?>>Cicilan Berjalan</option>
              <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Batal" <?= $filterStatus === 'Batal' ? 'selected' : '' ?>>Batal</option>
            </select>
          </div>
          <div class="col-md-2">
            <input type="date" name="tgl_mulai" class="form-control form-control-ms" value="<?= htmlspecialchars($tglMulai) ?>">
          </div>
          <div class="col-md-2">
            <input type="date" name="tgl_selesai" class="form-control form-control-ms" value="<?= htmlspecialchars($tglSelesai) ?>">
          </div>
          <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center p-2" title="Terapkan Filter">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
            </button>
            <?php if (!empty($search) || !empty($filterMetode) || !empty($filterStatus) || !empty($tglMulai)): ?>
              <a href="<?= base_url('admin/transaksi/index.php') ?>" class="btn btn-outline-ms p-2" title="Reset">
                <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data Transaksi -->
    <div class="ms-card">
      <div class="table-responsive">
        <table class="table table-custom-dark mb-0">
          <thead>
            <tr>
              <th>No. Transaksi</th>
              <th>Tanggal</th>
              <th>Pelanggan</th>
              <th>Unit Motor</th>
              <th>Total Bayar</th>
              <th>Metode</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transactions)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">Belum ada catatan transaksi penjualan.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($transactions as $t): ?>
                <tr>
                  <td>
                    <a href="<?= base_url('admin/transaksi/detail.php?id=' . $t['id']) ?>" class="fw-bold text-white text-decoration-underline font-monospace">
                      <?= htmlspecialchars($t['no_transaksi']) ?>
                    </a>
                    <div class="text-muted small">Petugas: <?= htmlspecialchars($t['nama_kasir'] ?: 'Admin') ?></div>
                  </td>
                  <td class="text-muted small">
                    <?= date('d M Y', strtotime($t['tanggal'])) ?>
                  </td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($t['nama_pelanggan']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($t['no_hp']) ?></div>
                  </td>
                  <td class="small text-white-50" style="max-width: 200px;">
                    <?= htmlspecialchars($t['motor_dibeli'] ?: '-') ?>
                  </td>
                  <td>
                    <div class="fw-bold text-danger"><?= formatRupiah($t['total_bayar']) ?></div>
                    <?php if ($t['diskon'] > 0): ?>
                      <div class="text-muted small" style="font-size: 0.75rem;">Diskon: <?= formatRupiah($t['diskon']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-dark border border-secondary border-opacity-25 text-white">
                      <?= htmlspecialchars($t['metode_pembayaran']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($t['status'] === 'Lunas'): ?>
                      <span class="badge badge-status-lunas">Lunas</span>
                    <?php elseif ($t['status'] === 'Cicilan Berjalan'): ?>
                      <span class="badge badge-status-cicilan">Cicilan</span>
                    <?php elseif ($t['status'] === 'Pending'): ?>
                      <span class="badge badge-status-pending">Pending</span>
                    <?php else: ?>
                      <span class="badge badge-status-batal">Batal</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/transaksi/detail.php?id=' . $t['id']) ?>" class="btn btn-outline-ms p-1 px-2" title="Detail Transaksi">
                        <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                      </a>
                      <a href="<?= base_url('admin/transaksi/cetak.php?id=' . $t['id']) ?>" target="_blank" class="btn btn-outline-ms p-1 px-2 text-info" title="Cetak Faktur">
                        <i data-lucide="printer" style="width: 15px; height: 15px;"></i>
                      </a>
                      <?php if ($t['status'] !== 'Batal' && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?= base_url('admin/transaksi/batal.php?id=' . $t['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-danger" title="Batalkan Transaksi" onclick="return confirmDelete(event, this.href, 'Batalkan transaksi <?= addslashes($t['no_transaksi']) ?>? Stok motor akan dikembalikan otomatis ke gudang.')">
                          <i data-lucide="x-circle" style="width: 15px; height: 15px;"></i>
                        </a>
                      <?php endif; ?>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
