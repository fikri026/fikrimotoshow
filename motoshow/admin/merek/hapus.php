<?php
/**
 * Hapus Merek Motor
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch();

if (!$brand) {
    setFlash('danger', 'Data merek tidak ditemukan.');
    header('Location: ' . base_url('admin/merek/index.php'));
    exit;
}

// Cek apakah ada unit motor di merek ini
$stmtCheck = $pdo->prepare("SELECT COUNT(*) as jml FROM motors WHERE brand_id = ?");
$stmtCheck->execute([$id]);
if ($stmtCheck->fetch()['jml'] > 0) {
    setFlash('danger', 'Merek ' . htmlspecialchars($brand['nama_merek']) . ' tidak dapat dihapus karena masih digunakan pada data unit motor yang ada.');
    header('Location: ' . base_url('admin/merek/index.php'));
    exit;
}

$stmtDel = $pdo->prepare("DELETE FROM brands WHERE id = ?");
$stmtDel->execute([$id]);

setFlash('success', 'Merek motor ' . htmlspecialchars($brand['nama_merek']) . ' berhasil dihapus.');
header('Location: ' . base_url('admin/merek/index.php'));
exit;
