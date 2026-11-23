<?php
/**
 * Konfigurasi Database dan Helper Global MotoShow
 * Menggunakan PHP Data Objects (PDO)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_motoshow');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// URL Dasar Aplikasi
if (!defined('BASE_URL')) {
    // Deteksi sub-folder /motoshow/
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . $host . '/motoshow/');
}

// Inisialisasi Koneksi PDO
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Koneksi database MotoShow gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Helper BASE_URL
function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

// Helper Format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Helper Format Tanggal Indonesia
function formatTanggal($tanggal, $denganWaktu = false) {
    if (!$tanggal || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    
    $hasil = "$hari $bln $thn";
    if ($denganWaktu) {
        $hasil .= ' ' . date('H:i', $timestamp) . ' WIB';
    }
    return $hasil;
}

// Helper Flash Message
function setFlash($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // success, danger, warning, info
        'text' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash_msg'])) {
        $flash = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $text = htmlspecialchars($flash['text']);
        $icon = 'info';
        if ($type === 'success') $icon = 'check-circle';
        if ($type === 'danger') $icon = 'alert-triangle';
        if ($type === 'warning') $icon = 'alert-circle';

        echo "
        <div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm border-0 mb-4' role='alert'>
            <i data-lucide='{$icon}' class='me-2 flex-shrink-0' style='width: 20px; height: 20px;'></i>
            <div>{$text}</div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

// Helper Autentikasi & Otorisasi
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function checkAuth($allowedRoles = []) {
    if (!isLoggedIn()) {
        setFlash('danger', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
    
    if (!empty($allowedRoles)) {
        $userRole = $_SESSION['user_role'] ?? '';
        if (!in_array($userRole, (array)$allowedRoles)) {
            setFlash('danger', 'Akses ditolak. Anda tidak memiliki izin untuk membuka halaman tersebut.');
            if ($userRole === 'admin') {
                header('Location: ' . base_url('admin/dashboard.php'));
            } elseif ($userRole === 'petugas') {
                header('Location: ' . base_url('petugas/dashboard.php'));
            } else {
                header('Location: ' . base_url('pelanggan/dashboard.php'));
            }
            exit;
        }
    }
}

// Sanitasi Input
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Generator Nomor Transaksi
function generateNoTransaksi($pdo) {
    $dateCode = 'TRX-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT no_transaksi FROM transactions WHERE no_transaksi LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$dateCode . '%']);
    $last = $stmt->fetch();
    if ($last) {
        $lastNum = (int)substr($last['no_transaksi'], -4);
        $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNum = '0001';
    }
    return $dateCode . $nextNum;
}

// Generator Nomor Pembayaran
function generateNoPembayaran($pdo) {
    $dateCode = 'PAY-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT no_pembayaran FROM payments WHERE no_pembayaran LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$dateCode . '%']);
    $last = $stmt->fetch();
    if ($last) {
        $lastNum = (int)substr($last['no_pembayaran'], -4);
        $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNum = '0001';
    }
    return $dateCode . $nextNum;
}

// Generator Nomor Booking
function generateNoBooking($pdo) {
    $dateCode = 'BKG-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT no_booking FROM bookings WHERE no_booking LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$dateCode . '%']);
    $last = $stmt->fetch();
    if ($last) {
        $lastNum = (int)substr($last['no_booking'], -4);
        $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNum = '0001';
    }
    return $dateCode . $nextNum;
}

// Helper URL Gambar Motor
function getMotorImage($filename) {
    if (!empty($filename) && file_exists(dirname(__DIR__) . '/assets/uploads/motors/' . $filename)) {
        return base_url('assets/uploads/motors/' . $filename);
    }
    return base_url('assets/img/default-motor.svg');
}

// Upload Foto Handler
function handleUploadFoto($file, $targetSubDir = 'motors') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Tidak ada file yang diunggah atau terjadi kesalahan upload.'];
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize      = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar. Maksimal 5 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($mimeType, $allowedMimes) || !in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Format file tidak didukung. Harap unggah format JPG, PNG, atau WEBP.'];
    }

    $uploadDir = dirname(__DIR__) . '/assets/uploads/' . trim($targetSubDir, '/') . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newFileName = $targetSubDir . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'error' => 'Gagal memindahkan file yang diunggah.'];
    }
}
