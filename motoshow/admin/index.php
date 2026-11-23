<?php
/**
 * Entry Point Admin MotoShow
 * Otomatis mengarahkan ke dashboard.php jika sudah login sebagai admin,
 * atau ke auth/login.php jika belum login.
 */
require_once __DIR__ . '/../config/database.php';

// Jika belum login, arahkan ke login
if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

// Jika sudah login tetapi bukan admin, arahkan sesuai rolenya
$userRole = $_SESSION['user_role'] ?? '';
if ($userRole !== 'admin') {
    setFlash('warning', 'Halaman ini khusus untuk Administrator.');
    if ($userRole === 'petugas') {
        header('Location: ' . base_url('petugas/dashboard.php'));
    } else {
        header('Location: ' . base_url('pelanggan/dashboard.php'));
    }
    exit;
}

// Arahkan ke Dashboard Admin
header('Location: ' . base_url('admin/dashboard.php'));
exit;
