<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

requireStaff();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $staffTitle ?? 'Sistem Kasir POS'; ?> - AN Grafika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-800 min-h-screen flex flex-col">

    <!-- Top POS Navigation Bar -->
    <header class="bg-slate-950 text-white px-4 sm:px-6 py-3 border-b border-slate-800 flex items-center justify-between shadow-md">
        <!-- Brand & Staff Info -->
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-white p-1 flex items-center justify-center shadow-md">
                    <img src="../assets/img/logo.png" alt="AN Grafika" class="h-full w-full object-contain">
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-base font-black tracking-tight text-white">AN <span class="text-[#C30064]">Grafika</span></span>
                        <span class="text-[9px] font-bold bg-[#C30064]/20 text-pink-300 border border-[#C30064]/40 px-1.5 py-0.2 rounded uppercase">POS KASIR</span>
                    </div>
                    <p class="text-[10px] text-slate-400">Studio Desain & Percetakan Digital</p>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-1 pl-4 border-l border-slate-800 text-xs">
                <a href="pos.php" class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 font-bold <?= $currentPage === 'pos.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800'; ?>">
                    <i data-lucide="calculator" class="w-4 h-4"></i>
                    <span>Kasir Kasir POS</span>
                </a>
                <a href="orders.php" class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 font-bold <?= $currentPage === 'orders.php' ? 'bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800'; ?>">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span>Antrean & Status Cetak</span>
                </a>
                <?php if (isAdmin()): ?>
                    <a href="../admin/index.php" class="px-3 py-1.5 rounded-xl text-blue-400 hover:bg-slate-800 transition flex items-center gap-1 font-semibold ml-2">
                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                        <span>Panel Admin</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cashier Status & Actions -->
        <div class="flex items-center gap-4 text-xs">
            <div class="hidden sm:flex flex-col text-right">
                <span class="text-slate-400 text-[10px]">Petugas Kasir:</span>
                <span class="font-bold text-white"><?= htmlspecialchars($currentUser['name']); ?></span>
            </div>

            <a href="../index.php" target="_blank" title="Buka Website Publik" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                <i data-lucide="external-link" class="w-4 h-4"></i>
            </a>

            <a href="../logout.php" title="Keluar Akun" class="px-3 py-2 rounded-xl bg-rose-950 text-rose-300 hover:bg-rose-900 border border-rose-800/50 font-bold transition flex items-center gap-1">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 bg-slate-100 flex flex-col overflow-hidden">
