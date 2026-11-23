<?php
/**
 * Laporan Penjualan Showroom Motor MotoShow
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$pageTitle = 'Laporan Penjualan - MotoShow';
$activeMenu = 'laporan';

// Filter Periode: Harian, Bulanan, Tahunan, Custom
$periode = cleanInput($_GET['periode'] ?? 'bulan_ini');
$tglMulai = cleanInput($_GET['tgl_mulai'] ?? '');
$tglSelesai = cleanInput($_GET['tgl_selesai'] ?? '');

$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');

if ($periode === 'hari_ini') {
    $tglMulai = $today;
    $tglSelesai = $today;
    $labelPeriode = 'Hari Ini (' . formatTanggal($today) . ')';
} elseif ($periode === 'bulan_ini') {
    $tglMulai = date('Y-m-01');
    $tglSelesai = date('Y-m-t');
    $labelPeriode = 'Bulan Ini (' . date('F Y') . ')';
} elseif ($periode === 'tahun_ini') {
    $tglMulai = $thisYear . '-01-01';
    $tglSelesai = $thisYear . '-12-31';
    $labelPeriode = 'Tahun Ini (' . $thisYear . ')';
} else {
    if (empty($tglMulai)) $tglMulai = date('Y-m-01');
    if (empty($tglSelesai)) $tglSelesai = $today;
    $labelPeriode = 'Periode ' . formatTanggal($tglMulai) . ' s/d ' . formatTanggal($tglSelesai);
}

// 1. Ringkasan Pendapatan & Unit Terjual Periode Terpilih
$stmtSummary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT t.id) as total_trx,
        COALESCE(SUM(td.jumlah), 0) as total_unit,
        COALESCE(SUM(t.total_harga), 0) as total_kotor,
        COALESCE(SUM(t.diskon), 0) as total_diskon,
        COALESCE(SUM(t.total_bayar), 0) as total_bersih
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    WHERE t.status != 'Batal' AND t.tanggal BETWEEN ? AND ?
");
$stmtSummary->execute([$tglMulai, $tglSelesai]);
$summary = $stmtSummary->fetch();

// 2. Daftar Transaksi Lengkap dalam Periode
$stmtTrx = $pdo->prepare("
    SELECT t.*, c.nama_lengkap as nama_pelanggan, c.nik, u.nama_lengkap as nama_kasir,
           GROUP_CONCAT(CONCAT(m.nama_motor, ' (', td.jumlah, 'x)') SEPARATOR ', ') as motor_list,
           COALESCE(SUM(td.jumlah), 0) as total_qty
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN motors m ON td.motor_id = m.id
    WHERE t.status != 'Batal' AND t.tanggal BETWEEN ? AND ?
    GROUP BY t.id
    ORDER BY t.tanggal DESC, t.id DESC
");
$stmtTrx->execute([$tglMulai, $tglSelesai]);
$transactions = $stmtTrx->fetchAll();

// 3. Motor Paling Banyak Terjual Periode Terpilih
$stmtTop = $pdo->prepare("
    SELECT m.nama_motor, b.nama_merek, m.foto,
           COALESCE(SUM(td.jumlah), 0) as terjual,
           COALESCE(SUM(td.subtotal), 0) as total_omset
    FROM transaction_details td
    JOIN transactions t ON td.transaction_id = t.id
    JOIN motors m ON td.motor_id = m.id
    JOIN brands b ON m.brand_id = b.id
    WHERE t.status != 'Batal' AND t.tanggal BETWEEN ? AND ?
    GROUP BY m.id
    ORDER BY terjual DESC
    LIMIT 5
");
$stmtTop->execute([$tglMulai, $tglSelesai]);
$topMotors = $stmtTop->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
  <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
  <div class="page-body">
    <?php renderFlash(); ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h4 class="fw-black text-white mb-1">Laporan Penjualan Motor</h4>
        <p class="text-muted small mb-0">Analisis pendapatan harian, bulanan, tahunan dan ekspor data pembukuan</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('admin/laporan/export_excel.php?periode=' . urlencode($periode) . '&tgl_mulai=' . urlencode($tglMulai) . '&tgl_selesai=' . urlencode($tglSelesai)) ?>" class="btn btn-outline-ms text-success">
          <i data-lucide="file-spreadsheet" style="width: 16px; height: 16px;"></i> Ekspor Excel (.xls)
        </a>
        <a href="<?= base_url('admin/laporan/cetak.php?periode=' . urlencode($periode) . '&tgl_mulai=' . urlencode($tglMulai) . '&tgl_selesai=' . urlencode($tglSelesai)) ?>" target="_blank" class="btn btn-primary-ms">
          <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Cetak Laporan PDF
        </a>
      </div>
    </div>

    <!-- Filter Periode Bar -->
    <div class="ms-card mb-4">
      <div class="ms-card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
          <div class="col-md-3">
            <select name="periode" id="periodeSelect" class="form-select form-select-ms" onchange="toggleCustomDate(this.value)">
              <option value="hari_ini" <?= $periode === 'hari_ini' ? 'selected' : '' ?>>Laporan Harian (Hari Ini)</option>
              <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : '' ?>>Laporan Bulanan (Bulan Ini)</option>
              <option value="tahun_ini" <?= $periode === 'tahun_ini' ? 'selected' : '' ?>>Laporan Tahunan (Tahun Ini)</option>
              <option value="custom" <?= $periode === 'custom' ? 'selected' : '' ?>>Kustom Rentang Tanggal</option>
            </select>
          </div>
          <div class="col-md-3" id="wrapMulai" style="<?= $periode === 'custom' ? '' : 'display:none;' ?>">
            <input type="date" name="tgl_mulai" class="form-control form-control-ms" value="<?= htmlspecialchars($tglMulai) ?>">
          </div>
          <div class="col-md-3" id="wrapSelesai" style="<?= $periode === 'custom' ? '' : 'display:none;' ?>">
            <input type="date" name="tgl_selesai" class="form-control form-control-ms" value="<?= htmlspecialchars($tglSelesai) ?>">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary-ms w-100 justify-content-center">
              <i data-lucide="filter" style="width: 16px; height: 16px;"></i> Tampilkan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Total Pendapatan Bersih</div>
          <div class="stat-value text-danger fs-4"><?= formatRupiah($summary['total_bersih']) ?></div>
          <span class="text-white-50 small"><?= $labelPeriode ?></span>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Jumlah Transaksi</div>
          <div class="stat-value text-white"><?= number_format($summary['total_trx']) ?> <span class="fs-6 text-muted fw-normal">Trx</span></div>
          <span class="text-white-50 small">Transaksi berstatus sah</span>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Unit Motor Terjual</div>
          <div class="stat-value text-success"><?= number_format($summary['total_unit']) ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
          <span class="text-white-50 small">Total unit terkirim</span>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-title">Total Diskon Diberikan</div>
          <div class="stat-value text-warning fs-5"><?= formatRupiah($summary['total_diskon']) ?></div>
          <span class="text-white-50 small">Program promosi showroom</span>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <!-- Motor Paling Laris Pada Periode Ini -->
      <div class="col-lg-4">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0">Motor Paling Banyak Terjual</h6>
          </div>
          <div class="ms-card-body p-0">
            <?php if (empty($topMotors)): ?>
              <div class="p-4 text-center text-muted small">Tidak ada data penjualan pada periode ini.</div>
            <?php else: ?>
              <div class="list-group list-group-flush bg-transparent">
                <?php foreach ($topMotors as $idx => $tm): ?>
                  <div class="list-group-item bg-transparent border-secondary border-opacity-10 py-3 px-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-danger rounded-circle p-2 text-white fw-bold" style="width: 26px; height: 26px; font-size: 0.75rem;">
                        <?= $idx + 1 ?>
                      </span>
                      <div>
                        <div class="text-white fw-semibold small"><?= htmlspecialchars($tm['nama_motor']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($tm['nama_merek']) ?></div>
                      </div>
                    </div>
                    <div class="text-end">
                      <span class="badge bg-success bg-opacity-20 text-success fw-bold"><?= $tm['terjual'] ?> Unit</span>
                      <div class="text-muted small" style="font-size: 0.72rem;"><?= formatRupiah($tm['total_omset']) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Riwayat Transaksi Lengkap -->
      <div class="col-lg-8">
        <div class="ms-card h-100">
          <div class="ms-card-header">
            <h6 class="fw-bold text-white mb-0">Rincian Transaksi (<?= count($transactions) ?> Data)</h6>
          </div>
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
                </tr>
              </thead>
              <tbody>
                <?php if (empty($transactions)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">Tidak ada transaksi pada periode ini.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($transactions as $t): ?>
                    <tr>
                      <td class="font-monospace fw-bold text-white small"><?= htmlspecialchars($t['no_transaksi']) ?></td>
                      <td class="text-muted small"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                      <td>
                        <div class="text-white small fw-semibold"><?= htmlspecialchars($t['nama_pelanggan']) ?></div>
                      </td>
                      <td class="small text-white-50" style="max-width: 180px;">
                        <?= htmlspecialchars($t['motor_list']) ?>
                      </td>
                      <td>
                        <span class="badge bg-dark border border-secondary border-opacity-25 text-white small">
                          <?= htmlspecialchars($t['metode_pembayaran']) ?>
                        </span>
                      </td>
                      <td class="text-end fw-bold text-white"><?= formatRupiah($t['total_bayar']) ?></td>
                      <td>
                        <span class="badge <?= $t['status'] === 'Lunas' ? 'badge-status-lunas' : 'badge-status-cicilan' ?> small">
                          <?= htmlspecialchars($t['status']) ?>
                        </span>
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
  function toggleCustomDate(val) {
    const wrapM = document.getElementById('wrapMulai');
    const wrapS = document.getElementById('wrapSelesai');
    if (val === 'custom') {
      wrapM.style.display = 'block';
      wrapS.style.display = 'block';
    } else {
      wrapM.style.display = 'none';
      wrapS.style.display = 'none';
    }
  }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
