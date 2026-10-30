-- Database Schema for AN Grafika Web-to-Print & Design POS System
-- Compatible with MySQL / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS `angrafika_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `angrafika_db`;

-- --------------------------------------------------------
-- Table: users (Admin & Pegawai / Staff Kasir)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'pegawai') NOT NULL DEFAULT 'pegawai',
  `phone` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: categories (Kategori Produk Percetakan)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'printer',
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: products (Katalog Produk & Harga)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `base_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
  `min_order` INT NOT NULL DEFAULT 1,
  `image` VARCHAR(255) DEFAULT NULL,
  `options_json` TEXT DEFAULT NULL,
  `estimated_days` INT NOT NULL DEFAULT 1,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: vouchers (Kode Voucher Diskon)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE `vouchers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(100) NOT NULL,
  `discount_type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(12,2) NOT NULL,
  `min_purchase` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(12,2) DEFAULT NULL,
  `usage_limit` INT NOT NULL DEFAULT 100,
  `used_count` INT NOT NULL DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: orders (Pesanan Cetak & Transaksi POS)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_no` VARCHAR(60) NOT NULL UNIQUE,
  `order_type` ENUM('print', 'design', 'pos') NOT NULL DEFAULT 'print',
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(25) NOT NULL,
  `customer_email` VARCHAR(100) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `voucher_code` VARCHAR(50) DEFAULT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `change_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'qris', 'transfer', 'debit') NOT NULL DEFAULT 'cash',
  `payment_status` ENUM('unpaid', 'paid', 'dp') NOT NULL DEFAULT 'paid',
  `production_status` ENUM('pending', 'verified', 'printing', 'finishing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `delivery_type` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
  `delivery_address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `staff_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: order_items (Detail Item Pesanan)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT DEFAULT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `specifications` TEXT DEFAULT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: design_requests (Permintaan Layanan Desain Logo dll)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `design_requests`;
CREATE TABLE `design_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_no` VARCHAR(60) NOT NULL UNIQUE,
  `receipt_no` VARCHAR(60) DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(25) NOT NULL,
  `customer_email` VARCHAR(100) DEFAULT NULL,
  `design_type` VARCHAR(100) NOT NULL,
  `brand_name` VARCHAR(150) NOT NULL,
  `tagline` VARCHAR(200) DEFAULT NULL,
  `preferred_colors` VARCHAR(150) DEFAULT NULL,
  `design_style` VARCHAR(100) DEFAULT NULL,
  `design_brief` TEXT NOT NULL,
  `reference_file` VARCHAR(255) DEFAULT NULL,
  `package_type` VARCHAR(50) NOT NULL DEFAULT 'Standar',
  `estimated_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('submitted', 'in_progress', 'review_ready', 'revision', 'completed') NOT NULL DEFAULT 'submitted',
  `preview_file` VARCHAR(255) DEFAULT NULL,
  `designer_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Insert Users (Password: admin123 & kasir123)
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `phone`, `status`) VALUES
(1, 'Administrator Utama', 'admin', 'admin@angrafika.id', '$2y$10$FEBz9i73T4/DoTWrBAo1DOGkbrKs4/PQ9Bk8XACse4yqT65EACd5e', 'admin', '0857-9999-0111', 'active'),
(2, 'Budi Santoso (Kasir)', 'kasir1', 'budi@angrafika.id', '$2y$10$OfnZAaMVqG9kn714p6lJTOreSS7XQXLEbfdaXhthYcm3ZmIZ.nw..', 'pegawai', '0812-3456-7890', 'active'),
(3, 'Siti Rahma (Kasir)', 'kasir2', 'siti@angrafika.id', '$2y$10$OfnZAaMVqG9kn714p6lJTOreSS7XQXLEbfdaXhthYcm3ZmIZ.nw..', 'pegawai', '0813-9876-5432', 'active');

-- Insert Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Kertas & Dokumen', 'kertas-dokumen', 'Brosur, kartu nama, flyer, sertifikat, kalender meja/dinding, buku nota & kop surat berkualitas tinggi', 'file-text'),
(2, 'Stiker & Label Kemasan', 'stiker-label', 'Stiker cromo, vinyl susu waterproof, transparan bening dengan cutting presisi pola custom', 'tag'),
(3, 'Spanduk & Banner', 'spanduk-banner', 'Banner outdoor flexi tebal, roll-up banner aluminium mewah, dan X-banner indoor tajam', 'image'),
(4, 'Kemasan & Box', 'kemasan-box', 'Food pail kemasan kuliner anti-bocor, kardus kue custom, paper bag, dan packaging UMKM', 'box'),
(5, 'Merchandise & Custom', 'merchandise-custom', 'Mug keramik sablon full colour, ID Card & tali lanyard, pin bros, dan merchandise promosi', 'gift');

-- Insert Products
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `base_price`, `unit`, `min_order`, `image`, `options_json`, `estimated_days`, `status`) VALUES
(1, 1, 'Kartu Nama Premium (Box/100 pcs)', 'kartu-nama-premium', 'Cetak kartu nama profesional dengan bahan tebal Art Carton 260g/310g, gratis kotak plastik eksklusif.', 25000.00, 'box', 1, NULL, '{"bahan":[{"name":"Art Carton 260g","price":0},{"name":"Art Carton 310g","price":5000},{"name":"Linen Jepang Mewah","price":15000}],"finishing":[{"name":"Tanpa Laminasi","price":0},{"name":"Laminasi Doff 2 Sisi","price":7500},{"name":"Laminasi Glossy 2 Sisi","price":7500},{"name":"Spot UV Elegan","price":18000}]}', 1, 'active'),
(2, 1, 'Brosur & Flyer Promosi A4 / A5', 'brosur-flyer-promosi', 'Solusi promosi bisnis paling efektif. Warna cetak tajam HD dengan mesin digital offset berkecepatan tinggi.', 850.00, 'lembar', 50, NULL, '{"bahan":[{"name":"Art Paper 120g","price":0},{"name":"Art Paper 150g","price":200},{"name":"Matte Paper 150g","price":250}],"ukuran":[{"name":"A5 (14.8 x 21 cm)","price":0},{"name":"A4 (21 x 29.7 cm)","price":450}],"finishing":[{"name":"Tanpa Laminasi","price":0},{"name":"Lipat 2 / Lipat 3","price":100}]}', 1, 'active'),
(3, 2, 'Stiker Vinyl A3+ (Waterproof + Cutting)', 'stiker-vinyl-a3', 'Stiker tahan air, tidak mudah sobek, cocok untuk label botol, frozen food, dan outdoor sticker. Sudah termasuk potong pola.', 12500.00, 'lembar', 3, NULL, '{"bahan":[{"name":"Vinyl Susu Glossy","price":0},{"name":"Vinyl Doff / Matte","price":1500},{"name":"Vinyl Transparan Bening","price":2000}],"cutting":[{"name":"Kiss-Cut (Tinggal Cabut)","price":0},{"name":"Die-Cut (Putus Sesuai Pola)","price":3000}]}', 1, 'active'),
(4, 2, 'Stiker Cromo Ekonomis A3+', 'stiker-cromo-a3', 'Pilihan stiker paling ekonomis untuk kemasan toples kue kering, snack, dan label packaging kering.', 8000.00, 'lembar', 5, NULL, '{"finishing":[{"name":"Tanpa Laminasi","price":0},{"name":"Laminasi Glossy Anti Gores","price":3000},{"name":"Laminasi Doff Anti Pantul","price":3000}]}', 1, 'active'),
(5, 3, 'Spanduk / Banner Flexi Outdoor (per m²)', 'spanduk-flexi-outdoor', 'Spanduk tahan panas & hujan dengan mata ayam logam di tiap sudut. Cocok untuk warung, event, spanduk pecel lele, dan promo toko.', 22000.00, 'meter', 1, NULL, '{"bahan":[{"name":"Flexi Standard 280g","price":0},{"name":"Flexi Higres 340g","price":5000},{"name":"Flexi Korea Tebal 440g","price":12000}],"finishing":[{"name":"Mata Ayam (Ring Pojok)","price":0},{"name":"Lipat Saja (Tanpa Ring)","price":0},{"name":"Kolong Bambu / Kayu","price":2000}]}', 1, 'active'),
(6, 3, 'Roll-Up Banner Aluminium 60x160 cm', 'roll-up-banner-60x160', 'Display portabel elegan untuk pameran, booth mall, atau kantor. Bahan Albatros anti lengkung plus standing aluminium kokoh.', 145000.00, 'pcs', 1, NULL, '{"finishing":[{"name":"Laminasi Doff (Bebas Pantulan Lampu)","price":0},{"name":"Laminasi Glossy (Berkilau)","price":0}]}', 1, 'active'),
(7, 3, 'X-Banner Fiber 60x160 cm', 'x-banner-fiber-60x160', 'Standing banner hemat dan praktis dengan kerangka fiber hitam dan sarung tas kain.', 65000.00, 'pcs', 1, NULL, '{"bahan":[{"name":"Bahan Flexi 280g","price":0},{"name":"Bahan Albatros + Doff","price":25000}]}', 1, 'active'),
(8, 4, 'Kemasan Food Pail Eco Foodgrade (M)', 'food-pail-eco-m', 'Kemasan makanan anti bocor, anti minyak (leakproof), ramah lingkungan untuk nasi goreng, mie, snack, dan take away.', 1800.00, 'pcs', 100, NULL, '{"cetak":[{"name":"Polos Tanpa Cetak","price":-400},{"name":"Cetak 1 Warna Custom","price":0},{"name":"Cetak Full Colour HD","price":600}]}', 3, 'active'),
(9, 4, 'Box Kemasan Kue / Craft Cake Box', 'box-kemasan-kue', 'Kardus kue estetik dengan bahan Ivory / Duplex tebal higienis yang mempercantik tampilan produk kue UMKM Anda.', 3200.00, 'pcs', 50, NULL, '{"bahan":[{"name":"Ivory 300g Bersih","price":0},{"name":"Duplex 350g Kokoh","price":-300}],"finishing":[{"name":"Window Plastik Bening","price":500},{"name":"Laminasi Dalam Anti Minyak","price":400}]}', 3, 'active'),
(10, 5, 'Mug Keramik Custom Sablon Full Colour', 'mug-keramik-custom', 'Mug keramik putih standar SNI dengan cetak foto atau logo awet tidak luntur dicuci berkali-kali. Sudah termasuk box satuan.', 22000.00, 'pcs', 6, NULL, '{"varian":[{"name":"Mug Standar Putih SNI","price":0},{"name":"Mug Gagang Warna / Dalam Berwarna","price":4000},{"name":"Mug Bunglon (Ajaib Berubah Warna)","price":12000}]}', 2, 'active'),
(11, 5, 'ID Card PVC & Tali Lanyard 2cm Tisu', 'id-card-lanyard-set', 'Paket identitas karyawan / panitia event. Kartu PVC tebal mirip kartu ATM + tali lanyard cetak 2 sisi stopper & pengait besi.', 18500.00, 'set', 10, NULL, '{"aksesoris":[{"name":"Pengait Standar Besi","price":0},{"name":"Stopper Buka-Pasang + Card Holder Kulit","price":4500}]}', 2, 'active'),
(12, 1, 'Kalender Meja / Dinding Custom', 'kalender-custom', 'Kalender promosi akhir tahun dengan spiral kawat kokoh dan dudukan hardboard linen elegan.', 28000.00, 'pcs', 25, NULL, '{"tipe":[{"name":"Kalender Dinding Klip Seng (6 Lembar)","price":-8000},{"name":"Kalender Meja Dudukan Linen (7 Lembar)","price":0},{"name":"Kalender Meja Mewah (13 Lembar)","price":7000}]}', 4, 'active');

-- Insert Vouchers
INSERT INTO `vouchers` (`id`, `code`, `title`, `discount_type`, `discount_value`, `min_purchase`, `max_discount`, `usage_limit`, `used_count`, `start_date`, `end_date`, `status`) VALUES
(1, 'DISKON10', 'Diskon Spesial 10% Pengguna Baru', 'percent', 10.00, 50000.00, 25000.00, 200, 14, '2026-01-01', '2026-12-31', 'active'),
(2, 'HEMAT25RB', 'Potongan Rp 25.000 Minimal Belanja 100rb', 'fixed', 25000.00, 100000.00, 25000.00, 100, 8, '2026-01-01', '2026-12-31', 'active'),
(3, 'GRAFIKAPROMO', 'Promo Cetak AN Grafika Diskon 15%', 'percent', 15.00, 75000.00, 35000.00, 150, 19, '2026-01-01', '2026-12-31', 'active'),
(4, 'ANGRAFIKA20', 'Potongan Rp 20.000 Khusus Desain & Cetak', 'fixed', 20000.00, 80000.00, 20000.00, 100, 6, '2026-01-01', '2026-12-31', 'active');

-- Insert Sample Orders
INSERT INTO `orders` (`id`, `receipt_no`, `order_type`, `customer_name`, `customer_phone`, `customer_email`, `subtotal`, `voucher_code`, `discount_amount`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `payment_status`, `production_status`, `delivery_type`, `delivery_address`, `notes`, `staff_id`, `created_at`) VALUES
(1, 'AN-20261029-0012', 'pos', 'Rian Hidayat (Cafe Kopi Sore)', '081299887766', 'rian@kopisore.com', 125000.00, 'GRAFIKAPROMO', 18750.00, 106250.00, 110000.00, 3750.00, 'cash', 'paid', 'completed', 'pickup', NULL, 'Pesanan POS Kasir Toko', 2, '2026-10-29 10:15:00'),
(2, 'AN-20261030-0045', 'print', 'drg. Amanda Putri', '081344556677', 'amanda.putri@klinikgigi.id', 75000.00, 'DISKON10', 7500.00, 67500.00, 67500.00, 0.00, 'qris', 'paid', 'printing', 'delivery', 'Jl. Merdeka No. 22', 'Cetak kartu nama klinik gigi mohon warna hijau pastel jangan pudar', NULL, '2026-10-30 09:30:00'),
(3, 'AN-20261030-0089', 'pos', 'Hendra Wijaya', '085611223344', 'hendra@tokoberkah.com', 145000.00, NULL, 0.00, 145000.00, 150000.00, 5000.00, 'cash', 'paid', 'finishing', 'pickup', NULL, 'Roll up banner untuk pameran besok pagi', 2, '2026-10-30 14:10:00');

-- Insert Sample Order Items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `item_name`, `specifications`, `unit_price`, `quantity`, `subtotal`) VALUES
(1, 1, 3, 'Stiker Vinyl A3+ (Waterproof + Cutting)', 'Bahan: Vinyl Susu Glossy | Cutting: Kiss-Cut', 12500.00, 10, 125000.00),
(2, 2, 1, 'Kartu Nama Premium (Box/100 pcs)', 'Bahan: Art Carton 260g | Finishing: Laminasi Doff 2 Sisi', 25000.00, 3, 75000.00),
(3, 3, 6, 'Roll-Up Banner Aluminium 60x160 cm', 'Bahan: Albatros | Finishing: Laminasi Doff', 145000.00, 1, 145000.00);

-- Insert Sample Design Requests
INSERT INTO `design_requests` (`id`, `ticket_no`, `receipt_no`, `customer_name`, `customer_phone`, `customer_email`, `design_type`, `brand_name`, `tagline`, `preferred_colors`, `design_style`, `design_brief`, `package_type`, `estimated_price`, `status`, `designer_notes`, `created_at`) VALUES
(1, 'DSG-20261028-001', 'AN-20261028-0005', 'Dimas Prasetyo', '081288990011', 'dimas@rotienak.com', 'Desain Logo UMKM', 'Dapur Roti Nenek', 'Kehangatan Rasa Dari Dapur Tradisional', 'Cokelat Kayu, Emas, Krem Hangat', 'Vintage & Elegant Modern', 'Logo untuk bakery roti jadul artisanal. Butuh simbol gandum / oven klasik dengan font serif estetik yang mudah dibaca.', 'Paket Premium (3 Konsep + File Master AI/Vector)', 250000.00, 'in_progress', 'Draft konsep ke-2 sedang dirampungkan oleh desainer.', '2026-10-28 11:20:00'),
(2, 'DSG-20261030-002', NULL, 'Sisca Novita', '087811224455', 'sisca@glowskincare.id', 'Desain Kemasan / Label Box Skincare', 'Aura Glow Cosmetics', 'Radiance within You', 'Navy Blue, Magenta, Putih Bersih', 'Minimalis Modern Clean', 'Desain kemasan tube cleanser dan box luar 50ml. Ingin terlihat mewah ala brand modern.', 'Paket Standar (2 Konsep)', 150000.00, 'submitted', 'Menunggu review desainer dan konfirmasi file acuan.', '2026-10-30 13:45:00');
