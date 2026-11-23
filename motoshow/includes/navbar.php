<?php
/**
 * Global Top Navbar MotoShow
 */
require_once __DIR__ . '/../config/database.php';

$role = $_SESSION['user_role'] ?? 'guest';
$userName = $_SESSION['user_name'] ?? 'Pengguna';
?>

<nav class="app-navbar">
  <div class="d-flex align-items-center gap-3">
    <!-- Hamburger button for mobile -->
    <button class="btn btn-outline-ms d-lg-none p-2 border-0" id="sidebarToggleBtn" type="button" aria-label="Toggle menu">
      <i data-lucide="menu" style="width: 22px; height: 22px;"></i>
    </button>
    <div class="d-none d-sm-block">
      <span class="text-white-50 small">Showroom Management</span>
      <h6 class="mb-0 text-white fw-bold">MotoShow System</h6>
    </div>
  </div>

  <div class="d-flex align-items-center gap-3">
    <!-- Role Badge -->
    <span class="badge rounded-pill px-3 py-2 text-uppercase fw-bold <?= $role === 'admin' ? 'bg-danger' : ($role === 'petugas' ? 'bg-primary' : 'bg-success') ?>" style="letter-spacing: 0.5px; font-size: 0.75rem;">
      <i data-lucide="shield" class="me-1" style="width: 14px; height: 14px; vertical-align: -2px;"></i>
      <?= htmlspecialchars($role) ?>
    </span>

    <!-- User Profile Dropdown -->
    <div class="dropdown">
      <button class="navbar-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-avatar-circle">
          <?= strtoupper(substr($userName, 0, 1)) ?>
        </div>
        <span class="d-none d-md-inline fw-semibold small"><?= htmlspecialchars($userName) ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-dark-custom dropdown-menu-end">
        <li>
          <div class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1">
            <div class="fw-bold text-white small"><?= htmlspecialchars($userName) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
          </div>
        </li>
        <li>
          <a class="dropdown-item" href="<?= base_url('index.php') ?>" target="_blank">
            <i data-lucide="globe" style="width: 16px; height: 16px;"></i> Beranda Website
          </a>
        </li>
        <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
        <li>
          <a class="dropdown-item text-danger" href="<?= base_url('auth/logout.php') ?>">
            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i> Keluar (Logout)
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
