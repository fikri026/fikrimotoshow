<?php
/**
 * Batalkan Transaksi Penjualan & Kembalikan Stok Motor
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    setFlash('danger', 'Transaksi tidak ditemukan.');
    header('Location: ' . base_url('admin/transaksi/index.php'));
    exit;
}

if ($trx['status'] === 'Batal') {
    setFlash('warning', 'Transaksi ini sudah dibatalkan sebelumnya.');
    header('Location: ' . base_url('admin/transaksi/index.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ambil detail unit yang dibeli untuk direstore ke stok
    $stmtDetails = $pdo->prepare("SELECT motor_id, jumlah FROM transaction_details WHERE transaction_id = ?");
    $stmtDetails->execute([$id]);
    $items = $stmtDetails->fetchAll();

    foreach ($items as $it) {
        $stmtRestore = $pdo->prepare("UPDATE motors SET stok = stok + ?, status = 'Tersedia' WHERE id = ?");
        $stmtRestore->execute([$it['jumlah'], $it['motor_id']]);
    }

    // 2. Ubah status transaksi menjadi Batal
    $stmtUpdate = $pdo->prepare("UPDATE transactions SET status = 'Batal' WHERE id = ?");
    $stmtUpdate->execute([$id]);

    $pdo->commit();

    setFlash('success', 'Transaksi ' . htmlspecialchars($trx['no_transaksi']) . ' berhasil dibatalkan. Stok unit motor telah otomatis dikembalikan ke inventaris.');
} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('danger', 'Gagal membatalkan transaksi: ' . $e->getMessage());
}

header('Location: ' . base_url('admin/transaksi/index.php'));
exit;
