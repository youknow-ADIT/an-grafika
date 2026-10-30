<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'AN Grafika - Percetakan Digital & Studio Desain Profesional'; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        anblue: {
                            50: '#eef5fc',
                            100: '#d9e8f8',
                            500: '#0055d4',
                            600: '#0044ab',
                            700: '#00388C', // Warna utama 'A'
                            800: '#002c70',
                            900: '#002052',
                        },
                        anpink: {
                            50: '#fdf2f7',
                            100: '#fbe6f0',
                            500: '#e60076',
                            600: '#C30064', // Warna utama 'N'
                            700: '#a30053',
                            800: '#850044',
                            900: '#690035',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">

    <!-- Top Announcement Bar (Satu Baris Rapi) -->
    <div class="bg-gradient-to-r from-[#002052] via-[#00388C] to-[#a30053] text-white text-[11px] py-2 px-4 shadow-sm border-b border-white/10">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-3 flex-nowrap">
            <div class="flex items-center gap-2 truncate">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                <span class="font-medium truncate">AN Grafika &bull; Solusi Cetak Digital HD, Offset & Jasa Desain Profesional</span>
            </div>
            <div class="flex items-center gap-3 shrink-0 text-white/90">
                <span class="hidden md:inline text-white/80">Voucher Baru: <strong class="bg-white/20 px-1.5 py-0.5 rounded text-white font-mono">DISKON10</strong></span>
                <a href="https://wa.me/6285799990111" target="_blank" class="flex items-center gap-1 font-bold hover:text-white transition">
                    <i data-lucide="phone-call" class="w-3.5 h-3.5 text-pink-300"></i>
                    <span>0857 6009 0065</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header (Rapi, Tidak Wrap 2 Baris) -->
    <header class="bg-white sticky top-0 z-40 border-b border-slate-200/80 shadow-sm backdrop-blur-md bg-white/95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-20 gap-4">
                
                <!-- Brand Logo (Menggunakan Logo Asli AN) -->
                <a href="index.php" class="flex items-center gap-3 shrink-0 group">
                    <div class="h-12 w-12 rounded-xl bg-white flex items-center justify-center p-0.5 border border-slate-200/80 shadow-sm group-hover:scale-105 transition transform">
                        <img src="assets/img/logo.png" alt="AN Grafika" class="h-full w-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5 leading-none">
                            <span class="text-2xl font-black tracking-tight text-[#00388C]">AN <span class="text-[#C30064]">Grafika</span></span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-medium tracking-wide mt-1">Digital Printing & Design Studio</span>
                    </div>
                </a>

                <!-- Desktop Menu Links (Single-Line Rapi) -->
                <nav class="hidden lg:flex items-center gap-1 xl:gap-2 font-semibold text-xs xl:text-sm text-slate-700 whitespace-nowrap">
                    <a href="index.php" class="px-3 py-2 rounded-xl hover:text-[#00388C] hover:bg-slate-100/80 transition">Beranda</a>
                    <a href="products.php" class="px-3 py-2 rounded-xl hover:text-[#00388C] hover:bg-slate-100/80 transition">Katalog & Harga</a>
                    <a href="order.php" class="px-3 py-2 rounded-xl hover:text-[#00388C] hover:bg-blue-50/60 transition flex items-center gap-1 text-[#00388C]">
                        <i data-lucide="calculator" class="w-4 h-4 text-[#00388C]"></i>
                        <span>Order Cetak</span>
                    </a>
                    <a href="design-request.php" class="px-3 py-2 rounded-xl text-[#C30064] bg-pink-50/70 hover:bg-pink-100/70 transition flex items-center gap-1.5 font-bold">
                        <i data-lucide="palette" class="w-4 h-4 text-[#C30064]"></i>
                        <span>Jasa Desain</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C30064] animate-ping"></span>
                    </a>
                    <a href="track.php" class="px-3 py-2 rounded-xl hover:text-[#00388C] hover:bg-slate-100/80 transition flex items-center gap-1 text-slate-700">
                        <i data-lucide="search" class="w-4 h-4 text-emerald-600"></i>
                        <span>Lacak Pesanan</span>
                    </a>
                </nav>

                <!-- Action Buttons (Single-Line Rapi) -->
                <div class="hidden sm:flex items-center gap-2.5 shrink-0 whitespace-nowrap">

                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="admin/index.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-sm transition">
                                <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                                <span>Login</span>
                            </a>
                        <?php else: ?>
                            <a href="staff/pos.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-sm transition">
                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                                <span>Kasir POS</span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-sm transition">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex lg:hidden items-center gap-2">
                    <button onclick="toggleMobileMenu()" class="p-2 rounded-xl text-slate-700 hover:bg-slate-100 focus:outline-none">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-6 space-y-2">
            <a href="index.php" class="block px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-50">Beranda</a>
            <a href="products.php" class="block px-3 py-2 rounded-xl font-medium text-slate-700 hover:bg-slate-50">Katalog Produk & Harga</a>
            <a href="order.php" class="block px-3 py-2 rounded-xl font-medium text-[#00388C] hover:bg-blue-50">Order Cetak Cepat</a>
            <a href="design-request.php" class="block px-3 py-2 rounded-xl font-bold text-[#C30064] bg-pink-50/70">Jasa Desain (Logo dll)</a>
            <a href="track.php" class="block px-3 py-2 rounded-xl font-medium text-emerald-600 hover:bg-emerald-50">Lacak No. Struk</a>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                <a href="track.php" class="text-center py-2.5 px-3 rounded-xl text-xs font-bold text-slate-700 bg-slate-100">Cek Status Pesanan</a>
                <a href="login.php" class="text-center py-2.5 px-3 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064]">Login Pegawai & Admin</a>
            </div>
        </div>
    </header>
