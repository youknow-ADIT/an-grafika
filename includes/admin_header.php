<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $adminTitle ?? 'Admin Panel'; ?> - AN Grafika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col md:flex-row">

    <!-- Mobile Top Header Bar -->
    <div class="md:hidden bg-slate-950 text-white p-4 flex items-center justify-between border-b border-slate-800 sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-white p-0.5 flex items-center justify-center">
                <img src="../assets/img/logo.png" alt="AN" class="h-full w-full object-contain">
            </div>
            <span class="font-black text-sm">AN <span class="text-[#C30064]">Grafika</span> Admin</span>
        </div>
        <button onclick="document.getElementById('admin-sidebar').classList.toggle('hidden')" class="p-1.5 rounded-lg bg-slate-800 text-slate-200">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Admin Sidebar -->
    <aside id="admin-sidebar" class="hidden md:flex flex-col w-64 bg-slate-950 text-slate-300 min-h-screen border-r border-slate-800 shrink-0 select-none">
        
        <!-- Brand Header (Menggunakan Logo AN Grafika) -->
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-white p-1 flex items-center justify-center shadow-md">
                <img src="../assets/img/logo.png" alt="AN Grafika" class="h-full w-full object-contain">
            </div>
            <div>
                <h1 class="font-black text-white text-base tracking-tight leading-none">AN <span class="text-[#C30064]">Grafika</span></h1>
                <span class="text-[10px] text-pink-400 font-mono tracking-wider uppercase font-semibold">Admin Panel</span>
            </div>
        </div>

        <!-- Current User Info -->
        <div class="px-6 py-4 border-b border-slate-800/60 bg-slate-900/40 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#00388C] to-[#C30064] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                <?= strtoupper(substr($currentUser['name'], 0, 2)); ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($currentUser['name']); ?></p>
                <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#C30064]/20 text-pink-300 uppercase">Administrator</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-5 space-y-1 text-xs font-medium overflow-y-auto">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-1">Manajemen Utama</div>

            <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'index.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>Dashboard & Statistik</span>
            </a>

            <a href="employees.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'employees.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Kelola Akun Pegawai</span>
            </a>

            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 pt-4 pb-1">Katalog & Promo</div>

            <a href="products.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'products.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="package" class="w-4 h-4"></i>
                <span>Katalog Produk & Harga</span>
            </a>

            <a href="categories.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'categories.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="folder-tree" class="w-4 h-4"></i>
                <span>Kategori Percetakan</span>
            </a>

            <a href="vouchers.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'vouchers.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="ticket" class="w-4 h-4"></i>
                <span>Kode Voucher Diskon</span>
            </a>

            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 pt-4 pb-1">Operasional & Laporan</div>

            <a href="orders.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'orders.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>Pesanan Cetak & POS</span>
            </a>

            <a href="designs.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'designs.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="palette" class="w-4 h-4"></i>
                <span>Request Jasa Desain</span>
            </a>

            <a href="reports.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage === 'reports.php' || $currentPage === 'export.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-md font-bold' : 'hover:bg-slate-900 hover:text-white'; ?>">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                <span>Laporan & Ekspor Data</span>
            </a>

            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 pt-4 pb-1">Akses Cepat</div>

            <a href="../staff/pos.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-pink-950/40 text-pink-300 hover:bg-pink-900/60 border border-pink-800/40 transition font-bold">
                <i data-lucide="shopping-cart" class="w-4 h-4 text-[#C30064]"></i>
                <span>Buka Kasir POS Toko</span>
            </a>

            <a href="../index.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-900 hover:text-white transition">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                <span>Buka Website Publik</span>
            </a>
        </nav>

        <!-- Logout Bottom -->
        <div class="p-4 border-t border-slate-800">
            <a href="../logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Logout Keluar</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
        
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-slate-300">|</span>
                <span class="text-xs text-slate-500 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#C30064]"></i>
                    <span><?= date('l, d F Y'); ?></span>
                </span>
            </div>

            <div class="flex items-center gap-3">
                <a href="reports.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <i data-lucide="download" class="w-3.5 h-3.5 text-[#00388C]"></i>
                    <span>Ekspor Laporan</span>
                </a>
            </div>
        </header>

        <!-- Page Inner Content Wrapper -->
        <main class="flex-1 p-6 lg:p-8">
