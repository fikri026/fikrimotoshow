<?php
/**
 * Hapus Data Karyawan
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    setFlash('danger', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url('admin/karyawan/index.php'));
    exit;
}

$stmtDel = $pdo->prepare("DELETE FROM employees WHERE id = ?");
$stmtDel->execute([$id]);

setFlash('success', 'Data karyawan ' . htmlspecialchars($emp['nama_lengkap']) . ' berhasil dihapus.');
header('Location: ' . base_url('admin/karyawan/index.php'));
exit;
