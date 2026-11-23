<?php
/**
 * Verifikasi Pembayaran Pending
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$id]);
$pay = $stmt->fetch();

if (!$pay) {
    setFlash('danger', 'Data pembayaran tidak ditemukan.');
    header('Location: ' . base_url('admin/pembayaran/index.php'));
    exit;
}

$stmtUpdate = $pdo->prepare("UPDATE payments SET status = 'Lunas' WHERE id = ?");
$stmtUpdate->execute([$id]);

setFlash('success', 'Pembayaran ' . htmlspecialchars($pay['no_pembayaran']) . ' berhasil diverifikasi dan disahkan.');
header('Location: ' . base_url('admin/pembayaran/index.php'));
exit;
