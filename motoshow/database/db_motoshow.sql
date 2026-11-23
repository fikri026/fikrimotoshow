-- Database: db_motoshow
-- Aplikasi Showroom Motor MotoShow
-- Generated for XAMPP (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS `db_motoshow` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_motoshow`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `transaction_details`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `motors`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'petugas', 'pelanggan') NOT NULL DEFAULT 'pelanggan',
  `no_hp` VARCHAR(20) NULL,
  `foto` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: brands (Merek Motor)
-- --------------------------------------------------------
CREATE TABLE `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_merek` VARCHAR(50) NOT NULL UNIQUE,
  `negara_asal` VARCHAR(50) NULL,
  `deskripsi` TEXT NULL,
  `logo` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: motors
-- --------------------------------------------------------
CREATE TABLE `motors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_motor` VARCHAR(20) NOT NULL UNIQUE,
  `nama_motor` VARCHAR(100) NOT NULL,
  `brand_id` INT NOT NULL,
  `tipe_motor` VARCHAR(50) NOT NULL,
  `tahun_produksi` INT(4) NOT NULL,
  `warna` VARCHAR(50) NOT NULL,
  `harga` DECIMAL(15,2) NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `kapasitas_mesin` INT NOT NULL,
  `jenis_transmisi` VARCHAR(50) NOT NULL,
  `jenis_bahan_bakar` VARCHAR(50) NOT NULL DEFAULT 'Bensin',
  `konsumsi_bbm` VARCHAR(50) NULL,
  `kapasitas_tangki` VARCHAR(50) NULL,
  `sistem_pengereman` VARCHAR(100) NULL,
  `suspensi` VARCHAR(100) NULL,
  `dimensi` VARCHAR(100) NULL,
  `berat` VARCHAR(50) NULL,
  `deskripsi` TEXT NULL,
  `foto` VARCHAR(255) NULL,
  `status` ENUM('Tersedia', 'Habis', 'Segera Hadir') NOT NULL DEFAULT 'Tersedia',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_motors_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: customers
-- --------------------------------------------------------
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `nik` VARCHAR(20) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NULL,
  `alamat` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: employees (Karyawan)
-- --------------------------------------------------------
CREATE TABLE `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `nip` VARCHAR(20) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `jabatan` VARCHAR(50) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NULL,
  `alamat` TEXT NULL,
  `status` ENUM('Aktif', 'Cuti', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: transactions (Transaksi Penjualan)
-- --------------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `no_transaksi` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `total_harga` DECIMAL(15,2) NOT NULL,
  `diskon` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_bayar` DECIMAL(15,2) NOT NULL,
  `metode_pembayaran` ENUM('Tunai', 'Transfer', 'Kredit') NOT NULL,
  `status` ENUM('Pending', 'Lunas', 'Cicilan Berjalan', 'Batal') NOT NULL DEFAULT 'Pending',
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_transactions_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: transaction_details
-- --------------------------------------------------------
CREATE TABLE `transaction_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT NOT NULL,
  `motor_id` INT NOT NULL,
  `jumlah` INT NOT NULL DEFAULT 1,
  `harga_satuan` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  CONSTRAINT `fk_td_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_td_motor` FOREIGN KEY (`motor_id`) REFERENCES `motors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: payments (Data Pembayaran)
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT NOT NULL,
  `no_pembayaran` VARCHAR(30) NOT NULL UNIQUE,
  `tanggal_bayar` DATETIME NOT NULL,
  `jumlah_bayar` DECIMAL(15,2) NOT NULL,
  `metode_pembayaran` ENUM('Tunai', 'Transfer', 'Kredit') NOT NULL,
  `dp` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tenor` INT NOT NULL DEFAULT 0,
  `cicilan_per_bulan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sisa_bayar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `bukti_transfer` VARCHAR(255) NULL,
  `status` ENUM('Pending', 'Diverifikasi', 'Lunas') NOT NULL DEFAULT 'Lunas',
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: bookings (Pemesanan Motor)
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `no_booking` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `motor_id` INT NOT NULL,
  `tanggal_booking` DATETIME NOT NULL,
  `rencana_pembayaran` ENUM('Tunai', 'Transfer', 'Kredit') NOT NULL DEFAULT 'Tunai',
  `rencana_dp` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `rencana_tenor` INT NOT NULL DEFAULT 0,
  `catatan` TEXT NULL,
  `status` ENUM('Menunggu Konfirmasi', 'Dikonfirmasi', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_motor` FOREIGN KEY (`motor_id`) REFERENCES `motors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- SEED DATA
-- --------------------------------------------------------

-- Insert Users
-- Default passwords:
-- admin: admin123 ($2y$10$GQcvThz97ULZNypeGTQUbetR2an2JzS8ivb7ihJojEmffRVoBlGWC)
-- petugas: petugas123 ($2y$10$yVV.1.F1v6ZySLCX/bVbL.lGSaANh3a2YxWVb/vlDiy3SYYvkAeM.)
-- pelanggan: pelanggan123 ($2y$10$bjv.xevwwOWS2B70bXDgPuhW7HRXNxdMrTM0lRCZgYNa8QHkYMOL2)

INSERT INTO `users` (`id`, `username`, `password`, `email`, `nama_lengkap`, `role`, `no_hp`, `foto`, `created_at`) VALUES
(1, 'admin', '$2y$10$GQcvThz97ULZNypeGTQUbetR2an2JzS8ivb7ihJojEmffRVoBlGWC', 'admin@motoshow.com', 'Administrator Utama', 'admin', '081234567890', NULL, NOW()),
(2, 'petugas', '$2y$10$yVV.1.F1v6ZySLCX/bVbL.lGSaANh3a2YxWVb/vlDiy3SYYvkAeM.', 'petugas@motoshow.com', 'Ahmad Faisal (Kasir)', 'petugas', '081234567891', NULL, NOW()),
(3, 'pelanggan', '$2y$10$bjv.xevwwOWS2B70bXDgPuhW7HRXNxdMrTM0lRCZgYNa8QHkYMOL2', 'budi@gmail.com', 'Budi Santoso', 'pelanggan', '085712345678', NULL, NOW()),
(4, 'siti', '$2y$10$bjv.xevwwOWS2B70bXDgPuhW7HRXNxdMrTM0lRCZgYNa8QHkYMOL2', 'siti@gmail.com', 'Siti Rahmawati', 'pelanggan', '085887654321', NULL, NOW());

-- Insert Brands
INSERT INTO `brands` (`id`, `nama_merek`, `negara_asal`, `deskripsi`, `logo`, `created_at`) VALUES
(1, 'Honda', 'Jepang', 'Produsen sepeda motor terbesar di dunia dengan teknologi eSP+ dan kenyamanan maksimal.', 'honda.png', NOW()),
(2, 'Yamaha', 'Jepang', 'Dikenal dengan inovasi mesin Blue Core dan teknologi VVA yang bertenaga serta sporty.', 'yamaha.png', NOW()),
(3, 'Kawasaki', 'Jepang', 'Pelopor motor sport performa tinggi, seri Ninja legendaris, dan motor adventure tangguh.', 'kawasaki.png', NOW()),
(4, 'Suzuki', 'Jepang', 'Mengutamakan durabilitas mesin tangguh dengan efisiensi bahan bakar dan handling presisi.', 'suzuki.png', NOW()),
(5, 'Vespa', 'Italia', 'Ikon gaya hidup skuter premium klasik dengan desain abadi dan bodi monocoque baja modern.', 'vespa.png', NOW());

-- Insert Customers
INSERT INTO `customers` (`id`, `user_id`, `nik`, `nama_lengkap`, `no_hp`, `email`, `alamat`, `created_at`) VALUES
(1, 3, '3201011205920001', 'Budi Santoso', '085712345678', 'budi@gmail.com', 'Jl. Sudirman No. 45, Jakarta Selatan', NOW()),
(2, 4, '3201012508950002', 'Siti Rahmawati', '085887654321', 'siti@gmail.com', 'Jl. Merdeka Barat No. 12, Bandung', NOW()),
(3, NULL, '3273011409900003', 'Rian Hidayat', '081399887766', 'rian.hidayat@outlook.com', 'Jl. Diponegoro No. 88, Surabaya', NOW());

-- Insert Employees
INSERT INTO `employees` (`id`, `user_id`, `nip`, `nama_lengkap`, `jabatan`, `no_hp`, `email`, `alamat`, `status`, `created_at`) VALUES
(1, 1, 'EMP-001', 'Administrator Utama', 'General Manager', '081234567890', 'admin@motoshow.com', 'Jl. Showroom Raya No. 1, Jakarta', 'Aktif', NOW()),
(2, 2, 'EMP-002', 'Ahmad Faisal', 'Sales & Kasir', '081234567891', 'petugas@motoshow.com', 'Jl. Veteran No. 20, Jakarta', 'Aktif', NOW()),
(3, NULL, 'EMP-003', 'Dewi Lestari', 'Sales Executive', '081299112233', 'dewi.sales@motoshow.com', 'Jl. Melati Indah No. 5, Bekasi', 'Aktif', NOW());

-- Insert Motors
INSERT INTO `motors` (`id`, `kode_motor`, `nama_motor`, `brand_id`, `tipe_motor`, `tahun_produksi`, `warna`, `harga`, `stok`, `kapasitas_mesin`, `jenis_transmisi`, `jenis_bahan_bakar`, `konsumsi_bbm`, `kapasitas_tangki`, `sistem_pengereman`, `suspensi`, `dimensi`, `berat`, `deskripsi`, `foto`, `status`, `created_at`) VALUES
(1, 'MTR-HND-001', 'Honda PCX 160 ABS', 1, 'Matic', 2024, 'Matte Black', 36500000.00, 8, 160, 'Otomatis / CVT', 'Bensin', '45.0 km/liter', '8.1 Liter', 'Double Disc ABS Depan & Belakang', 'Teleskopik Depan & Ganda Belakang', '1.936 x 742 x 1.108 mm', '132 kg', 'Skuter matik premium dengan mesin eSP+ 4 katup berpendingin cairan, fitur HSTC (Honda Selectable Torque Control), Smart Key System, dan port charger USB.', 'motor_pcx160.jpg', 'Tersedia', NOW()),

(2, 'MTR-YMH-002', 'Yamaha All New NMAX 155 Connected', 2, 'Matic', 2024, 'Prestige Silver', 35750000.00, 6, 155, 'Otomatis / CVT', 'Bensin', '42.5 km/liter', '7.1 Liter', 'Cakram Depan & Belakang ABS', 'Teleskopik Depan & Sub-Tank Belakang', '1.935 x 740 x 1.160 mm', '131 kg', 'Maxi scooter sporty dilengkapi teknologi Y-Connect untuk menghubungkan smartphone dengan motor, TCS (Traction Control System), dan mesin Blue Core 155cc VVA.', 'motor_nmax155.jpg', 'Tersedia', NOW()),

(3, 'MTR-KWS-003', 'Kawasaki Ninja ZX-25R ABS SE', 3, 'Sport', 2024, 'Lime Green / Ebony', 125000000.00, 3, 250, 'Manual 6-Speed', 'Bensin', '22.0 km/liter', '15.0 Liter', 'Radial-Mount Monobloc Caliper ABS', 'Inverted Fork SFF-BP Depan & Horizontal Back-link', '1.980 x 750 x 1.110 mm', '182 kg', 'Satu-satunya motor sport 250cc dengan konfigurasi mesin 4 Silinder segaris di kelasnya, menghasilkan raungan suara balap eksotis hingga 17.000 RPM dengan fitur KQS (Quick Shifter).', 'motor_zx25r.jpg', 'Tersedia', NOW()),

(4, 'MTR-HND-004', 'Honda CBR250RR SP Quick Shifter', 1, 'Sport', 2024, 'Bravery Red Black', 79500000.00, 4, 250, 'Manual 6-Speed', 'Bensin', '25.0 km/liter', '14.5 Liter', 'Hydraulic Disc ABS Dual Channel', 'Inverted Telescopic Front Fork (Upside Down)', '2.060 x 724 x 1.098 mm', '168 kg', 'Super sport 250cc 2-silinder bertenaga 42 PS dengan fitur Throttle by Wire, 3 Riding Mode, Assist & Slipper Clutch, dan Quick Shifter 4 mode operasional.', 'motor_cbr250rr.jpg', 'Tersedia', NOW()),

(5, 'MTR-VSP-005', 'Vespa Primavera 150 i-get ABS', 5, 'Matic', 2024, 'Relax Green', 51500000.00, 5, 154, 'Otomatis / CVT', 'Bensin', '40.0 km/liter', '7.0 Liter', 'Cakram 200 mm ABS Depan / Tromol Belakang', 'Single Arm dengan Spiral Spring', '1.870 x 735 x 1.150 mm', '118 kg', 'Ikon skuter Italia dengan desain klasik modern, bodi baja kokoh, lampu full LED, velg aluminium 12 inci, dan mesin i-get yang halus serta hemat emisi.', 'motor_primavera.jpg', 'Tersedia', NOW()),

(6, 'MTR-YMH-006', 'Yamaha XSR 155 Heritage', 2, 'Naked', 2024, 'Metallic Black', 38000000.00, 7, 155, 'Manual 6-Speed', 'Bensin', '44.0 km/liter', '10.4 Liter', 'Cakram Depan & Belakang', 'Upside Down (USD) Suspension', '2.007 x 804 x 1.080 mm', '134 kg', 'Perpaduan sempurna antara gaya neo-retro warisan klasik legendaris Yamaha dengan teknologi mesin modern 155cc liquid cooled VVA serta speedometer digital bundar retro.', 'motor_xsr155.jpg', 'Tersedia', NOW()),

(7, 'MTR-HND-007', 'Honda Beat Street eSP', 1, 'Matic', 2024, 'Street Black', 18900000.00, 12, 110, 'Otomatis / CVT', 'Bensin', '60.6 km/liter', '4.2 Liter', 'Combi Brake System (CBS)', 'Teleskopik Depan & Lengan Ayun Tunggal', '1.877 x 742 x 1.030 mm', '89 kg', 'Skutik lincah harian berkarakter street style dengan naked handlebar, panel meter digital, rangka eSAF yang ringan dan gesit, serta konsumsi bahan bakar paling irit di kelasnya.', 'motor_beat.jpg', 'Tersedia', NOW()),

(8, 'MTR-YMH-008', 'Yamaha Aerox 155 CyberCity', 2, 'Matic', 2024, 'Cyber City Metallic', 27900000.00, 9, 155, 'Otomatis / CVT', 'Bensin', '43.0 km/liter', '5.5 Liter', 'Disc Brake Depan / Drum Belakang', 'Teleskopik Depan & Twin Shock Belakang', '1.980 x 700 x 1.150 mm', '122 kg', 'Super Sport Scooter dengan DNA balap agresif, desain tajam aerodinamis, rasio tenaga-terhadap-berat (PWR) tertinggi di kelasnya, dan ban tubeless ekstra lebar.', 'motor_aerox155.jpg', 'Tersedia', NOW()),

(9, 'MTR-SZK-009', 'Suzuki GSX-R150 Keyless Ignition', 4, 'Sport', 2023, 'Titan Black', 35000000.00, 5, 150, 'Manual 6-Speed', 'Bensin', '41.0 km/liter', '11.0 Liter', 'Petal Disc Front & Rear', 'Teleskopik Depan & Monoshock Belakang', '2.020 x 700 x 1.075 mm', '131 kg', 'Motor sport full fairing 150cc DOHC 4-valve dengan tenaga 19.1 HP terbaik, sistem kunci pintar keyless ignition system, dan lampu depan full LED agresif.', 'motor_gsxr150.jpg', 'Tersedia', NOW()),

(10, 'MTR-KWS-010', 'Kawasaki KLX 150 SE Dual Purpose', 3, 'Trail', 2024, 'Battle Gray', 39900000.00, 6, 144, 'Manual 5-Speed', 'Bensin', '38.0 km/liter', '6.9 Liter', 'Petal Disc Depan 240mm & Belakang 190mm', 'Inverted Fork 35mm & Uni-Trak 5-way preload', '2.050 x 830 x 1.115 mm', '118 kg', 'Pilihan utama pecinta off-road dan adventure, dilengkapi suspensi depan upside down warna emas, hand guard, skid plate pelindung mesin, dan velg depan 21 inci belakang 18 inci.', 'motor_klx150.jpg', 'Tersedia', NOW());

-- Insert Transactions
INSERT INTO `transactions` (`id`, `no_transaksi`, `customer_id`, `user_id`, `tanggal`, `total_harga`, `diskon`, `total_bayar`, `metode_pembayaran`, `status`, `catatan`, `created_at`) VALUES
(1, 'TRX-20261101-0001', 1, 2, '2026-11-01', 36500000.00, 500000.00, 36000000.00, 'Tunai', 'Lunas', 'Pembelian unit Honda PCX 160 ABS Warna Matte Black secara tunai. Helm dan jaket bonus telah diserahkan.', '2026-11-01 10:15:00'),
(2, 'TRX-20261105-0002', 2, 2, '2026-11-05', 35750000.00, 0.00, 35750000.00, 'Transfer', 'Lunas', 'Pembelian Yamaha All New NMAX 155 via transfer Bank BCA. Telah diverifikasi lunas.', '2026-11-05 14:30:00'),
(3, 'TRX-20261110-0003', 3, 2, '2026-11-10', 125000000.00, 2000000.00, 123000000.00, 'Kredit', 'Cicilan Berjalan', 'Pembelian Kawasaki Ninja ZX-25R kredit 24 bulan, DP Rp 35.000.000.', '2026-11-10 11:00:00');

-- Insert Transaction Details
INSERT INTO `transaction_details` (`id`, `transaction_id`, `motor_id`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 1, 1, 36500000.00, 36500000.00),
(2, 2, 2, 1, 35750000.00, 35750000.00),
(3, 3, 3, 1, 125000000.00, 125000000.00);

-- Insert Payments
INSERT INTO `payments` (`id`, `transaction_id`, `no_pembayaran`, `tanggal_bayar`, `jumlah_bayar`, `metode_pembayaran`, `dp`, `tenor`, `cicilan_per_bulan`, `sisa_bayar`, `bukti_transfer`, `status`, `keterangan`, `created_at`) VALUES
(1, 1, 'PAY-20261101-0001', '2026-11-01 10:20:00', 36000000.00, 'Tunai', 0.00, 0, 0.00, 0.00, NULL, 'Lunas', 'Pelunasan tunai kasir showroom', '2026-11-01 10:20:00'),
(2, 2, 'PAY-20261105-0002', '2026-11-05 14:35:00', 35750000.00, 'Transfer', 0.00, 0, 0.00, 0.00, 'sample_receipt_bca.jpg', 'Lunas', 'Transfer rekening BCA Showroom Rek 1234567890 an MotoShow', '2026-11-05 14:35:00'),
(3, 3, 'PAY-20261110-0003', '2026-11-10 11:15:00', 35000000.00, 'Kredit', 35000000.00, 24, 3666667.00, 88000000.00, NULL, 'Lunas', 'Pembayaran Uang Muka (DP) Kredit 24 Bulan', '2026-11-10 11:15:00');

-- Insert Bookings
INSERT INTO `bookings` (`id`, `no_booking`, `customer_id`, `motor_id`, `tanggal_booking`, `rencana_pembayaran`, `rencana_dp`, `rencana_tenor`, `catatan`, `status`, `created_at`) VALUES
(1, 'BKG-20261115-0001', 1, 5, '2026-11-15 09:30:00', 'Tunai', 0.00, 0, 'Booking Vespa Primavera warna Relax Green. Ingin ambil unit di hari Sabtu.', 'Menunggu Konfirmasi', '2026-11-15 09:30:00'),
(2, 'BKG-20261116-0002', 2, 6, '2026-11-16 16:45:00', 'Kredit', 10000000.00, 12, 'Pengajuan kredit Yamaha XSR 155 tenor 1 tahun dengan DP 10jt.', 'Dikonfirmasi', '2026-11-16 16:45:00');
