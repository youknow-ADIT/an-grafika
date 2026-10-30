# AN Grafika - Sistem Percetakan Digital, Jasa Desain & POS Kasir Modern

<p align="center">
  <img src="assets/img/logo.png" width="120" alt="AN Grafika Logo">
</p>

Aplikasi Web Percetakan Digital (*Web-to-Print*), Portofolio Jasa Desain Kreatif, Sistem Kasir POS (*Point of Sale*), dan Manajemen Workshop Cetak berbasis PHP & MySQL yang siap dijalankan di XAMPP.

---

## ✨ Fitur Utama

### 🌐 1. Area Publik & Pelanggan
- **Katalog Produk & Harga Transparan:** Menampilkan daftar produk percetakan lengkap dengan foto, spesifikasi bahan, satuan unit, dan estimasi waktu pengerjaan.
- **Pencarian Realtime & Filter Kategori:** Cari produk secara instan saat mengetik tanpa reload halaman.
- **Kalkulator Order Cetak Online:** Simulasi hitung harga otomatis sesuai jumlah pesanan dan varian bahan/finishing.
- **Layanan Request Jasa Desain:** Formulir pemesanan jasa desain logo, kemasan produk, dan spanduk dengan unggahan referensi.
- **Pelacakan Status Pesanan (No. Struk):** Pelanggan dapat memantau tahapan pengerjaan (Antrean, File Diverifikasi, Cetak Mesin, Finishing, Siap Diambil) secara realtime.
- **Integrasi WhatsApp CS:** Tombol WhatsApp mengambang (*floating button*) hijau resmi untuk konsultasi langsung.

### 💳 2. Sistem Kasir POS & Antrean Cetak Staf (`/staff/`)
- **Kasir POS Cepat:** Antarmuka khusus kasir toko dengan pencarian produk, grid kategori visual, keranjang belanja, dan kalkulasi uang kembalian.
- **Voucher Diskon Realtime Database:** Terhubung langsung ke database untuk validasi voucher, minimal belanja, dan potongan harga otomatis.
- **Cetak Struk Kasir Thermal:** Format cetak struk 58mm / 80mm berlogo AN Grafika dengan kode barcode unik (`AN-YYYYMMDD-XXXX`).
- **Antrean Mesin Cetak Workshop (`staff/orders.php`):** Dashboard antrean produksi untuk mengubah status cetak pesanan.

### 🛡️ 3. Panel Administrator Lengkap (`/admin/`)
- **Dashboard Statistik:** Ringkasan total pendapatan, total transaksi, pesanan cetak aktif, dan tiket jasa desain baru.
- **Kelola Produk & Foto:** Tambah produk baru dengan upload foto, edit data produk via modal interaktif, atur harga, dan varian JSON.
- **Kelola Kategori & Foto Visual:** Tambah dan edit kategori lengkap dengan foto representatif untuk memudahkan identifikasi jenis percetakan.
- **Generator Kode Voucher:** Buat voucher diskon persentase (`%`) atau nominal tetap (`Rp`), batas kuota pemakaian, dan minimal belanja.
- **Kelola Akun Pegawai:** Tambah kasir/staf baru, ubah status aktif/nonaktif, dan reset password.
- **Laporan Penjualan & Export:** Filter laporan berdasarkan rentang tanggal, jenis order, dan kasir, serta ekspor ke format Excel (CSV) dan PDF siap cetak.

---

## 🛠️ Teknologi yang Digunakan
- **Backend:** PHP 8+ (PDO MySQL Database Connector)
- **Database:** MySQL / MariaDB (Auto Self-Healing Schema)
- **Frontend:** HTML5, Tailwind CSS, Lucide Icons, Plus Jakarta Sans Font
- **Environment:** Apache & MySQL (XAMPP Compatible)

---

## 🚀 Panduan Instalasi di XAMPP

1. **Clone Repository:**
   Letakkan project ini ke dalam direktori `htdocs` XAMPP Anda:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/youknow-ADIT/an-grafika.git angrafika
   ```

2. **Jalankan XAMPP:**
   Buka **XAMPP Control Panel** lalu klik **Start** pada modul **Apache** dan **MySQL**.

3. **Inisialisasi Database:**
   - Database `angrafika_db` akan terbuat secara otomatis saat pertama kali dibuka, ATAU
   - Buka `http://localhost/angrafika/install.php` di browser untuk setup satu kali klik, ATAU
   - Import file `database.sql` langsung melalui phpMyAdmin (`http://localhost/phpmyadmin`).

4. **Akses Aplikasi:**
   - **Website Publik:** `http://localhost/angrafika/`
   - **Login Admin & Kasir:** `http://localhost/angrafika/login.php`

---

## 🔑 Akun Demo Default

| Role | Username | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` |
| **Kasir 1** | `kasir1` | `kasir123` |
| **Kasir 2** | `kasir2` | `kasir123` |

---

## 📄 Lisensi
Dikembangkan untuk **AN Grafika** - Digital Printing & Creative Design Studio.
