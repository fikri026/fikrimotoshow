/**
 * MotoShow - Main JavaScript Application Logic
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Inisialisasi Lucide Icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // 2. Mobile Sidebar Toggle
  const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
  const appSidebar = document.querySelector('.app-sidebar');
  const sidebarBackdrop = document.querySelector('.sidebar-backdrop');

  if (sidebarToggleBtn && appSidebar) {
    sidebarToggleBtn.addEventListener('click', function () {
      appSidebar.classList.toggle('show');
      if (sidebarBackdrop) {
        sidebarBackdrop.classList.toggle('show');
      }
    });
  }

  if (sidebarBackdrop && appSidebar) {
    sidebarBackdrop.addEventListener('click', function () {
      appSidebar.classList.remove('show');
      sidebarBackdrop.classList.remove('show');
    });
  }

  // 3. Image Upload Preview
  const imageInput = document.querySelector('.ms-image-upload-input');
  const imagePreview = document.querySelector('.ms-image-preview');

  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          imagePreview.src = e.target.result;
          imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 4. Kalkulator Cicilan Kredit Dinamis
  const inputTotal = document.getElementById('transaksiTotal');
  const inputDiskon = document.getElementById('transaksiDiskon');
  const inputDP = document.getElementById('transaksiDP');
  const selectTenor = document.getElementById('transaksiTenor');
  const selectMetode = document.getElementById('metodePembayaran');
  const kreditSection = document.getElementById('kreditSection');

  function hitungKredit() {
    if (!inputTotal) return;

    const hargaSatuan = parseFloat(inputTotal.getAttribute('data-price') || inputTotal.value || 0);
    const diskon = parseFloat(inputDiskon ? inputDiskon.value : 0) || 0;
    const totalBersih = Math.max(0, hargaSatuan - diskon);

    const bayarLabel = document.getElementById('totalBayarDisplay');
    if (bayarLabel) {
      bayarLabel.innerText = formatRupiahJs(totalBersih);
    }
    const totalBayarInput = document.getElementById('totalBayarInput');
    if (totalBayarInput) {
      totalBayarInput.value = totalBersih;
    }

    if (selectMetode && selectMetode.value === 'Kredit') {
      if (kreditSection) kreditSection.style.display = 'block';

      const dp = parseFloat(inputDP ? inputDP.value : 0) || 0;
      const tenor = parseInt(selectTenor ? selectTenor.value : 12) || 12;

      const sisaBayar = Math.max(0, totalBersih - dp);
      // Flat bunga 0% untuk simulasi sederhana atau pembagian langsung
      const cicilan = tenor > 0 ? Math.round(sisaBayar / tenor) : 0;

      const sisaDisplay = document.getElementById('sisaBayarDisplay');
      const cicilanDisplay = document.getElementById('cicilanDisplay');
      const cicilanInput = document.getElementById('cicilanInput');
      const sisaInput = document.getElementById('sisaInput');

      if (sisaDisplay) sisaDisplay.innerText = formatRupiahJs(sisaBayar);
      if (cicilanDisplay) cicilanDisplay.innerText = formatRupiahJs(cicilan) + ' / bulan';
      if (cicilanInput) cicilanInput.value = cicilan;
      if (sisaInput) sisaInput.value = sisaBayar;
    } else {
      if (kreditSection) kreditSection.style.display = 'none';
    }
  }

  if (selectMetode) {
    selectMetode.addEventListener('change', hitungKredit);
  }
  if (inputDiskon) {
    inputDiskon.addEventListener('input', hitungKredit);
  }
  if (inputDP) {
    inputDP.addEventListener('input', hitungKredit);
  }
  if (selectTenor) {
    selectTenor.addEventListener('change', hitungKredit);
  }

  // Jalankan perhitungan awal jika form sedang dibuka
  if (selectMetode) {
    hitungKredit();
  }

  // 5. Konfirmasi Hapus Data
  window.confirmDelete = function (event, url, message = 'Apakah Anda yakin ingin menghapus data ini?') {
    event.preventDefault();
    if (confirm(message)) {
      window.location.href = url;
    }
  };
});

// Helper Format Rupiah di JS
function formatRupiahJs(angka) {
  return 'Rp ' + Number(angka).toLocaleString('id-ID');
}
