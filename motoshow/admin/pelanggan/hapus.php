<?php
/**
 * Hapus Data Pelanggan
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$cust = $stmt->fetch();

if (!$cust) {
    setFlash('danger', 'Data pelanggan tidak ditemukan.');
    header('Location: ' . base_url('admin/pelanggan/index.php'));
    exit;
}

// Cek relasi transaksi
$stmtTrx = $pdo->prepare("SELECT COUNT(*) as jml FROM transactions WHERE customer_id = ?");
$stmtTrx->execute([$id]);
if ($stmtTrx->fetch()['jml'] > 0) {
    setFlash('danger', 'Pelanggan ' . htmlspecialchars($cust['nama_lengkap']) . ' tidak dapat dihapus karena memiliki riwayat transaksi.');
    header('Location: ' . base_url('admin/pelanggan/index.php'));
    exit;
}

// Cek relasi booking
$stmtBkg = $pdo->prepare("SELECT COUNT(*) as jml FROM bookings WHERE customer_id = ?");
$stmtBkg->execute([$id]);
if ($stmtBkg->fetch()['jml'] > 0) {
    setFlash('danger', 'Pelanggan memiliki data pemesanan (booking) aktif dan tidak dapat dihapus.');
    header('Location: ' . base_url('admin/pelanggan/index.php'));
    exit;
}

$stmtDel = $pdo->prepare("DELETE FROM customers WHERE id = ?");
$stmtDel->execute([$id]);

setFlash('success', 'Data pelanggan ' . htmlspecialchars($cust['nama_lengkap']) . ' berhasil dihapus.');
header('Location: ' . base_url('admin/pelanggan/index.php'));
exit;
