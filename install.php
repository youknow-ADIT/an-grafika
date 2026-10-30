<?php
/**
 * One-Click Installer & Seeder for AN Grafika
 */
$host = 'localhost';
$port = '3306';
$user = 'root';
$pass = '';
$dbname = 'angrafika_db';

$message = '';
$status = 'idle';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbname}`");

        $sqlPath = __DIR__ . '/database.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("File database.sql tidak ditemukan di {$sqlPath}");
        }

        $sql = file_get_contents($sqlPath);
        $pdo->exec($sql);

        $status = 'success';
        $message = 'Database angrafika_db berhasil dibuat dan seluruh data sampel (katalog produk, kategori, voucher promo, akun) berhasil diimport!';
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Gagal melakukan instalasi: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Database AN Grafika - XAMPP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-xl w-full bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-[#002052] via-[#00388C] to-[#C30064] p-8 text-white text-center">
            <div class="w-16 h-16 bg-white rounded-2xl p-1.5 flex items-center justify-center mx-auto mb-4 shadow-xl">
                <img src="assets/img/logo.png" alt="AN Grafika" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-black">AN Grafika - Setup Database</h1>
            <p class="text-pink-100 text-xs mt-1">Inisialisasi Database MySQL & Data Demo Percetakan untuk XAMPP</p>
        </div>

        <div class="p-8">
            <?php if ($status === 'success'): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-sm mb-6">
                    <p class="font-bold flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Instalasi Berhasil!
                    </p>
                    <p class="mt-1 text-xs"><?= htmlspecialchars($message); ?></p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 mb-6 text-xs text-slate-700 space-y-2">
                    <p class="font-bold text-slate-900 text-sm">Akun Bawaan Siap Pakai:</p>
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-[#00388C] uppercase">Role Admin</span>
                            <p class="mt-1">Username: <code class="font-bold text-[#00388C]">admin</code></p>
                            <p>Password: <code class="font-bold text-[#00388C]">admin123</code></p>
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-pink-100 text-[#C30064] uppercase">Role Pegawai / POS</span>
                            <p class="mt-1">Username: <code class="font-bold text-[#C30064]">kasir1</code></p>
                            <p>Password: <code class="font-bold text-[#C30064]">kasir123</code></p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="index.php" class="flex-1 text-center py-3 px-4 bg-slate-900 text-white rounded-xl font-bold text-xs hover:bg-slate-800 transition">
                        Buka Website Utama
                    </a>
                    <a href="login.php" class="flex-1 text-center py-3 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl font-bold text-xs hover:opacity-95 transition">
                        Login Admin / Pegawai
                    </a>
                </div>

            <?php elseif ($status === 'error'): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-sm mb-6">
                    <p class="font-bold">Gagal Menginstal!</p>
                    <p class="mt-1 text-xs"><?= htmlspecialchars($message); ?></p>
                    <p class="mt-2 text-xs text-rose-600">Pastikan modul <strong>MySQL</strong> di XAMPP Control Panel sudah berstatus <strong>Start (Running)</strong>.</p>
                </div>
            <?php endif; ?>

            <?php if ($status !== 'success'): ?>
                <div class="space-y-4 mb-6 text-sm text-slate-600">
                    <p>Halaman ini akan membuat database <code class="bg-slate-100 px-1.5 py-0.5 rounded text-[#00388C] font-bold">angrafika_db</code> di MySQL XAMPP Anda dan mengisi seluruh data awal:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs text-slate-500 pl-2">
                        <li>Akun Administrator dan Pegawai Kasir</li>
                        <li>Katalog Produk & Harga (Kartu Nama, Brosur, Stiker, Banner, Mug, dll)</li>
                        <li>Sistem Kode Voucher Diskon (DISKON10, HEMAT25RB, dll)</li>
                        <li>Penerimaan Request Jasa Desain (Logo, Kemasan, Banner)</li>
                        <li>Data Pelacakan Pesanan & No. Struk Kasir</li>
                    </ul>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="install">
                    <button type="submit" class="w-full py-3.5 px-6 bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-black rounded-xl shadow-lg shadow-[#00388C]/25 transition">
                        🚀 Mulai Instalasi Database Sekarang
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100 text-center text-xs text-slate-400">
            AN Grafika &bull; Digital & Offset Printing POS Management
        </div>
    </div>
</body>
</html>
