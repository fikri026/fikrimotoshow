<?php
/**
 * Global Sidebar MotoShow
 */
require_once __DIR__ . '/../config/database.php';

$role = $_SESSION['user_role'] ?? 'guest';
$activeMenu = $activeMenu ?? '';
?>

<!-- Mobile Sidebar Backdrop -->
<div class="sidebar-backdrop"></div>

<aside class="app-sidebar">
  <!-- Sidebar Header -->
  <div class="sidebar-header">
    <a href="<?= base_url() ?>" class="sidebar-brand">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="MotoShow" height="36">
    </a>
  </div>

  <!-- Sidebar Menu -->
  <div class="sidebar-menu">
    <?php if ($role === 'admin'): ?>
      <div class="menu-title">UTAMA</div>
      <a href="<?= base_url('admin/dashboard.php') ?>" class="sidebar-link <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i>
        <span>Dashboard</span>
      </a>

      <div class="menu-title">MASTER DATA</div>
      <a href="<?= base_url('admin/motor/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'motor') ? 'active' : '' ?>">
        <i data-lucide="bike"></i>
        <span>Data Motor</span>
      </a>
      <a href="<?= base_url('admin/merek/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'merek') ? 'active' : '' ?>">
        <i data-lucide="tag"></i>
        <span>Merek Motor</span>
      </a>
      <a href="<?= base_url('admin/pelanggan/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'pelanggan') ? 'active' : '' ?>">
        <i data-lucide="users"></i>
        <span>Data Pelanggan</span>
      </a>
      <a href="<?= base_url('admin/karyawan/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'karyawan') ? 'active' : '' ?>">
        <i data-lucide="badge-check"></i>
        <span>Data Karyawan</span>
      </a>

      <div class="menu-title">TRANSAKSI & ORDER</div>
      <a href="<?= base_url('admin/transaksi/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'transaksi') ? 'active' : '' ?>">
        <i data-lucide="shopping-cart"></i>
        <span>Penjualan</span>
      </a>
      <a href="<?= base_url('admin/pembayaran/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'pembayaran') ? 'active' : '' ?>">
        <i data-lucide="credit-card"></i>
        <span>Pembayaran</span>
      </a>
      <a href="<?= base_url('admin/booking/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'booking') ? 'active' : '' ?>">
        <i data-lucide="calendar-check"></i>
        <span>Pemesanan / Booking</span>
      </a>

      <div class="menu-title">LAPORAN & SISTEM</div>
      <a href="<?= base_url('admin/laporan/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'laporan') ? 'active' : '' ?>">
        <i data-lucide="file-bar-chart"></i>
        <span>Laporan Penjualan</span>
      </a>
      <a href="<?= base_url('admin/pengguna/index.php') ?>" class="sidebar-link <?= ($activeMenu === 'pengguna') ? 'active' : '' ?>">
        <i data-lucide="user-cog"></i>
        <span>Manajemen Akun</span>
      </a>

    <?php elseif ($role === 'petugas'): ?>
      <div class="menu-title">UTAMA</div>
      <a href="<?= base_url('petugas/dashboard.php') ?>" class="sidebar-link <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i>
        <span>Dashboard Petugas</span>
      </a>

      <div class="menu-title">OPERASIONAL</div>
      <a href="<?= base_url('petugas/motor.php') ?>" class="sidebar-link <?= ($activeMenu === 'motor') ? 'active' : '' ?>">
        <i data-lucide="bike"></i>
        <span>Daftar & Stok Motor</span>
      </a>
      <a href="<?= base_url('petugas/pelanggan.php') ?>" class="sidebar-link <?= ($activeMenu === 'pelanggan') ? 'active' : '' ?>">
        <i data-lucide="users"></i>
        <span>Data Pelanggan</span>
      </a>
      <a href="<?= base_url('petugas/transaksi.php') ?>" class="sidebar-link <?= ($activeMenu === 'transaksi') ? 'active' : '' ?>">
        <i data-lucide="shopping-cart"></i>
        <span>Transaksi Penjualan</span>
      </a>
      <a href="<?= base_url('petugas/pembayaran.php') ?>" class="sidebar-link <?= ($activeMenu === 'pembayaran') ? 'active' : '' ?>">
        <i data-lucide="credit-card"></i>
        <span>Kelola Pembayaran</span>
      </a>
      <a href="<?= base_url('petugas/riwayat.php') ?>" class="sidebar-link <?= ($activeMenu === 'riwayat') ? 'active' : '' ?>">
        <i data-lucide="history"></i>
        <span>Riwayat Transaksi</span>
      </a>

    <?php elseif ($role === 'pelanggan'): ?>
      <div class="menu-title">UTAMA</div>
      <a href="<?= base_url('pelanggan/dashboard.php') ?>" class="sidebar-link <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i>
        <span>Dashboard Saya</span>
      </a>
      <a href="<?= base_url('pelanggan/katalog.php') ?>" class="sidebar-link <?= ($activeMenu === 'katalog') ? 'active' : '' ?>">
        <i data-lucide="bike"></i>
        <span>Katalog Motor</span>
      </a>

      <div class="menu-title">AKTIVITAS</div>
      <a href="<?= base_url('pelanggan/pemesanan.php') ?>" class="sidebar-link <?= ($activeMenu === 'pemesanan') ? 'active' : '' ?>">
        <i data-lucide="calendar-check"></i>
        <span>Status Pemesanan</span>
      </a>
      <a href="<?= base_url('pelanggan/riwayat.php') ?>" class="sidebar-link <?= ($activeMenu === 'riwayat') ? 'active' : '' ?>">
        <i data-lucide="receipt"></i>
        <span>Riwayat Pembelian</span>
      </a>
    <?php endif; ?>

    <div class="menu-title mt-4">LAINNYA</div>
    <a href="<?= base_url('index.php') ?>" target="_blank" class="sidebar-link">
      <i data-lucide="external-link"></i>
      <span>Lihat Website</span>
    </a>
  </div>

  <!-- Sidebar Footer User Profile -->
  <div class="sidebar-footer">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div class="user-avatar-circle flex-shrink-0">
          <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="overflow-hidden">
          <div class="text-white text-truncate fw-semibold small"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
          <div class="text-muted small text-capitalize"><?= htmlspecialchars($role) ?></div>
        </div>
      </div>
      <a href="<?= base_url('auth/logout.php') ?>" class="text-danger p-1" title="Logout">
        <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
      </a>
    </div>
  </div>
</aside>
