<?php
/**
 * Global Header MotoShow
 */
require_once __DIR__ . '/../config/database.php';

$pageTitle = $pageTitle ?? 'MotoShow - Premium Motorcycle Showroom';
$activePage = $activePage ?? '';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom MotoShow CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

  <!-- Lucide Icons -->
  <script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class="app-wrapper">
