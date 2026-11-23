<?php
/**
 * Hapus Data Motor
 */
require_once __DIR__ . '/../../config/database.php';
checkAuth(['admin']);

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM motors WHERE id = ?");
$stmt->execute([$id]);
$motor = $stmt->fetch();

if (!$motor) {
    setFlash('danger', 'Data motor tidak ditemukan.');
    header('Location: ' . base_url('admin/motor/index.php'));
    exit;
}

// Cek keterkaitan dengan transaksi
$stmtCheck = $pdo->prepare("SELECT COUNT(*) as jml FROM transaction_details WHERE motor_id = ?");
$stmtCheck->execute([$id]);
if ($stmtCheck->fetch()['jml'] > 0) {
    setFlash('danger', 'Motor ' . htmlspecialchars($motor['nama_motor']) . ' tidak dapat dihapus karena sudah memiliki riwayat transaksi penjualan. Anda dapat mengubah statusnya menjadi Habis.');
    header('Location: ' . base_url('admin/motor/index.php'));
    exit;
}

// Cek keterkaitan dengan booking
$stmtCheckBooking = $pdo->prepare("SELECT COUNT(*) as jml FROM bookings WHERE motor_id = ?");
$stmtCheckBooking->execute([$id]);
if ($stmtCheckBooking->fetch()['jml'] > 0) {
    setFlash('danger', 'Motor tidak dapat dihapus karena terdapat data pemesanan (booking) aktif untuk unit ini.');
    header('Location: ' . base_url('admin/motor/index.php'));
    exit;
}

// Hapus foto jika ada dan bukan sample default
if (!empty($motor['foto'])) {
    $filePath = dirname(dirname(__DIR__)) . '/assets/uploads/motors/' . $motor['foto'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// Hapus dari database
$stmtDel = $pdo->prepare("DELETE FROM motors WHERE id = ?");
$stmtDel->execute([$id]);

setFlash('success', 'Data motor ' . htmlspecialchars($motor['nama_motor']) . ' berhasil dihapus.');
header('Location: ' . base_url('admin/motor/index.php'));
exit;
