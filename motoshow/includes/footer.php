<?php
/**
 * Global Footer MotoShow
 */
require_once __DIR__ . '/../config/database.php';
?>
      <footer class="mt-auto py-3 px-4 text-center border-top border-secondary border-opacity-10 text-white-50 small no-print">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
          <span>&copy; <?= date('Y') ?> <strong class="text-white">MotoShow</strong> &mdash; Modern Motorcycle Showroom System.</span>
          <span class="text-muted">Built with PHP Native & PDO</span>
        </div>
      </footer>
  </div> <!-- End .main-content -->
</div> <!-- End .app-wrapper -->

<!-- Bootstrap 5 Bundle JS -->
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Lucide Icons Rendering -->
<script>
  if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
    lucide.createIcons();
  }
</script>

<!-- Custom Main JS -->
<script src="<?= base_url('assets/js/main.js') ?>"></script>
</body>
</html>
