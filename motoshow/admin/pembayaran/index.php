<?php
/**
 * Manajemen Pembayaran - List
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin', 'petugas']);

$pdo = getDBConnection();
$pageTitle = 'Manajemen Pembayaran - MotoShow';
$activeMenu = 'pembayaran';

$search = cleanInput($_GET['q'] ?? '');
$filterMetode = cleanInput($_GET['metode'] ?? '');
$filterStatus = cleanInput($_GET['status'] ?? '');

$sql = "
    SELECT p.*, t.no_transaksi, t.total_bayar as total_tagihan_trx,
           c.nama_lengkap as nama_pelanggan, c.no_hp
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

if (!empty($filterMetode)) {
    $sql .= " AND p.metode_pembayaran = ?";
    $params[] = $filterMetode;
}

if (!empty($filterStatus)) {
    $sql .= " AND p.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Manajemen Pembayaran</h4>
        <p class="text-muted small mb-0">Kelola riwayat pelunasan tunai, konfirmasi transfer bank, dan angsuran kredit</p>
      </div>
      <div>
        <a href="<?= base_url('admin/pembayaran/input.php') ?>" class="btn btn-primary-ms">
          <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Input Pembayaran Baru
        </a>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-5">
            <input type="text" name="q" class="form-control form-control-ms" placeholder="Cari No. Pembayaran, Transaksi, atau Pelanggan..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-3">
            <select name="metode" class="form-select form-select-ms">
              <option value="">Semua Metode Pembayaran</option>
              <option value="Tunai" <?= $filterMetode === 'Tunai' ? 'selected' : '' ?>>Tunai</option>
              <option value="Transfer" <?= $filterMetode === 'Transfer' ? 'selected' : '' ?>>Transfer Bank</option>
              <option value="Kredit" <?= $filterMetode === 'Kredit' ? 'selected' : '' ?>>Kredit / Cicilan</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-ms">
              <option value="">Semua Status</option>
              <option value="Lunas" <?= $filterStatus === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
              <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Diverifikasi" <?= $filterStatus === 'Diverifikasi' ? 'selected' : '' ?>>Diverifikasi</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($filterMetode) || !empty($filterStatus)): ?>
              <a href="<?= base_url('admin/pembayaran/index.php') ?>" class="btn btn-outline-ms" title="Reset">
                <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data Pembayaran -->
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
              <th>Kredit (DP / Sisa)</th>
              <th>Bukti Transfer</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">Belum ada data pembayaran.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td>
                    <span class="font-monospace fw-bold text-white"><?= htmlspecialchars($p['no_pembayaran']) ?></span>
                  </td>
                  <td>
                    <a href="<?= base_url('admin/transaksi/detail.php?id=' . $p['transaction_id']) ?>" class="fw-semibold text-danger">
                      <?= htmlspecialchars($p['no_transaksi']) ?>
                    </a>
                    <div class="text-white small"><?= htmlspecialchars($p['nama_pelanggan']) ?></div>
                  </td>
                  <td class="text-muted small">
                    <?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?>
                  </td>
                  <td>
                    <span class="fw-bold text-success fs-6"><?= formatRupiah($p['jumlah_bayar']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-dark border border-secondary border-opacity-25 text-white">
                      <?= htmlspecialchars($p['metode_pembayaran']) ?>
                    </span>
                  </td>
                  <td class="small">
                    <?php if ($p['metode_pembayaran'] === 'Kredit'): ?>
                      <div>DP: <?= formatRupiah($p['dp']) ?></div>
                      <div class="text-danger fw-semibold">Sisa: <?= formatRupiah($p['sisa_bayar']) ?></div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($p['bukti_transfer'])): ?>
                      <button type="button" class="btn btn-outline-ms btn-sm p-1 px-2" data-bs-toggle="modal" data-bs-target="#buktiModal<?= $p['id'] ?>">
                        <i data-lucide="image" style="width: 14px; height: 14px;"></i> Lihat Bukti
                      </button>

                      <!-- Modal Bukti Transfer -->
                      <div class="modal fade" id="buktiModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content bg-dark text-white border-secondary">
                            <div class="modal-header border-secondary border-opacity-25">
                              <h6 class="modal-title fw-bold">Bukti Transfer - <?= htmlspecialchars($p['no_pembayaran']) ?></h6>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-3">
                              <div class="p-4 bg-black rounded border border-secondary border-opacity-25 text-white-50">
                                <i data-lucide="receipt" style="width: 48px; height: 48px;" class="mb-2 text-danger"></i>
                                <div class="fw-bold text-white mb-1">Bukti Transfer Bank Resmi</div>
                                <div class="small">File: <?= htmlspecialchars($p['bukti_transfer']) ?></div>
                                <div class="small text-success mt-2 fw-semibold">Nominal: <?= formatRupiah($p['jumlah_bayar']) ?></div>
                              </div>
                            </div>
                            <div class="modal-footer border-secondary border-opacity-25">
                              <button type="button" class="btn btn-outline-ms" data-bs-dismiss="modal">Tutup</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small">Tanpa lampiran</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($p['status'] === 'Lunas'): ?>
                      <span class="badge badge-status-lunas">Lunas</span>
                    <?php elseif ($p['status'] === 'Diverifikasi'): ?>
                      <span class="badge badge-status-tersedia">Diverifikasi</span>
                    <?php else: ?>
                      <span class="badge badge-status-pending">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= base_url('admin/pembayaran/invoice.php?id=' . $p['id']) ?>" target="_blank" class="btn btn-outline-ms p-1 px-2" title="Cetak Kuitansi">
                        <i data-lucide="receipt" style="width: 15px; height: 15px;"></i>
                      </a>
                      <?php if ($p['status'] === 'Pending' && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?= base_url('admin/pembayaran/verifikasi.php?id=' . $p['id']) ?>" class="btn btn-outline-ms p-1 px-2 text-success" title="Verifikasi Pembayaran" onclick="return confirm('Verifikasi pembayaran ini sekarang?')">
                          <i data-lucide="check" style="width: 15px; height: 15px;"></i>
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
