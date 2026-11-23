<?php
/**
 * Hapus Akun Pengguna
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id === (int)$_SESSION['user_id']) {
    setFlash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
    header('Location: ' . base_url('admin/pengguna/index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$usr = $stmt->fetch();

if (!$usr) {
    setFlash('danger', 'Akun pengguna tidak ditemukan.');
    header('Location: ' . base_url('admin/pengguna/index.php'));
    exit;
}

$stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmtDel->execute([$id]);

setFlash('success', 'Akun ' . htmlspecialchars($usr['username']) . ' berhasil dihapus.');
header('Location: ' . base_url('admin/pengguna/index.php'));
exit;
