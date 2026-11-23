<?php
/**
 * Entry Point Petugas MotoShow
 * Otomatis mengarahkan ke dashboard.php jika sudah login sebagai petugas atau admin,
 * atau ke auth/login.php jika belum login.
 */
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';
if ($userRole !== 'petugas' && $userRole !== 'admin') {
    setFlash('warning', 'Halaman ini khusus untuk Petugas Showroom.');
    header('Location: ' . base_url('pelanggan/dashboard.php'));
    exit;
}

header('Location: ' . base_url('petugas/dashboard.php'));
exit;
