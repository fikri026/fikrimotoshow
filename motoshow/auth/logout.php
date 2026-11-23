<?php
/**
 * Logout MotoShow
 */
require_once __DIR__ . '/../config/database.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setFlash('info', 'Anda telah berhasil keluar dari sistem MotoShow.');
header('Location: ' . base_url('auth/login.php'));
exit;
