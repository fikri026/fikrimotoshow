<?php
/**
 * Entry Point Pelanggan MotoShow
 * Otomatis mengarahkan ke dashboard.php jika sudah login sebagai pelanggan,
 * atau ke auth/login.php jika belum login.
 */
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

header('Location: ' . base_url('pelanggan/dashboard.php'));
exit;
