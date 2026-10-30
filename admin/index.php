<?php
$adminTitle = "Dashboard & Statistik";
require_once __DIR__ . '/../includes/admin_header.php';

$totalRevenue = 0;
$totalOrders = 0;
$totalDesigns = 0;
$totalEmployees = 0;
$recentOrders = [];
$recentDesigns = [];

try {
    $db = getDB();

    $totalRevenue = (float)$db->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
    $totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalDesigns = (int)$db->query("SELECT COUNT(*) FROM design_requests")->fetchColumn();
    $totalEmployees = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'pegawai'")->fetchColumn();

    $oStmt = $db->query("SELECT o.*, u.name as staff_name FROM orders o LEFT JOIN users u ON o.staff_id = u.id ORDER BY o.id DESC LIMIT 6");
    $recentOrders = $oStmt->fetchAll();

    $dStmt = $db->query("SELECT * FROM design_requests ORDER BY id DESC LIMIT 4");
    $recentDesigns = $dStmt->fetchAll();

} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-8">
    
    <!-- Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Ringkasan Operasional AN Grafika</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pantau pendapatan, antrean pengerjaan mesin cetak, pesanan kasir POS, dan request desain.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="vouchers.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-[#00388C] hover:bg-[#002c70] shadow-sm transition">
                <i data-lucide="ticket" class="w-4 h-4"></i>
                <span>Generate Voucher</span>
            </a>
            <a href="../staff/pos.php" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-[#C30064] hover:bg-[#a30053] shadow-sm transition">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                <span>Buka Kasir POS</span>
            </a>
        </div>
    </div>

    <!-- 4 Key Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Pendapatan</span>
                <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= formatRupiah($totalRevenue); ?></p>
                <span class="text-[11px] text-[#00388C] font-bold flex items-center gap-1 mt-1">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5 text-[#C30064]"></i>
                    <span>Transaksi Lunas (Paid)</span>
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#00388C] flex items-center justify-center font-bold">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Transaksi</span>
                <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($totalOrders, 0, ',', '.'); ?></p>
                <span class="text-[11px] text-[#C30064] font-semibold mt-1 block">Pesanan Cetak & Kasir</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-pink-50 text-[#C30064] flex items-center justify-center font-bold">
                <i data-lucide="shopping-bag" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Request Jasa Desain</span>
                <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($totalDesigns, 0, ',', '.'); ?></p>
                <span class="text-[11px] text-[#00388C] font-semibold mt-1 block">Logo, Kemasan, Banner</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#00388C] flex items-center justify-center font-bold">
                <i data-lucide="palette" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Akun Pegawai Kasir</span>
                <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($totalEmployees, 0, ',', '.'); ?></p>
                <a href="employees.php" class="text-[11px] text-[#C30064] hover:underline font-bold mt-1 block">Kelola Pegawai &rarr;</a>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

    </div>

    <!-- Main Grid: Recent Orders & Recent Design Requests -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Recent Orders Table -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-sm text-slate-900">Transaksi & Pesanan Cetak Terbaru</h2>
                    <p class="text-[11px] text-slate-400">Pesanan dari online web-to-print dan kasir toko</p>
                </div>
                <a href="orders.php" class="text-xs font-bold text-[#00388C] hover:text-[#C30064]">Lihat Semua &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                        <tr>
                            <th class="p-3">No. Struk</th>
                            <th class="p-3">Pelanggan</th>
                            <th class="p-3">Tipe & Kasir</th>
                            <th class="p-3">Total Tagihan</th>
                            <th class="p-3">Status Cetak</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-3 font-mono font-bold text-slate-900">
                                    <a href="../receipt.php?receipt_no=<?= urlencode($ro['receipt_no']); ?>" target="_blank" class="hover:text-[#00388C] hover:underline">
                                        <?= htmlspecialchars($ro['receipt_no']); ?>
                                    </a>
                                </td>
                                <td class="p-3">
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($ro['customer_name']); ?></p>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($ro['customer_phone']); ?></p>
                                </td>
                                <td class="p-3">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $ro['order_type'] === 'pos' ? 'bg-pink-100 text-[#C30064]' : 'bg-blue-100 text-[#00388C]'; ?>">
                                        <?= strtoupper($ro['order_type']); ?>
                                    </span>
                                    <p class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($ro['staff_name'] ?? 'Online'); ?></p>
                                </td>
                                <td class="p-3 font-bold text-slate-900">
                                    <?= formatRupiah($ro['total_amount']); ?>
                                </td>
                                <td class="p-3">
                                    <?= getStatusBadge($ro['production_status'], 'production'); ?>
                                </td>
                                <td class="p-3 text-center">
                                    <a href="../receipt.php?receipt_no=<?= urlencode($ro['receipt_no']); ?>" target="_blank" title="Lihat Struk Kasir" class="inline-flex p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-slate-50 border-t border-slate-100 text-right">
                <a href="reports.php" class="text-xs text-slate-600 font-semibold hover:text-[#00388C]">
                    Olah & Ekspor Laporan Lengkap &rarr;
                </a>
            </div>
        </div>

        <!-- Recent Design Requests -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-sm text-slate-900">Request Desain Masuk</h3>
                    <p class="text-[11px] text-slate-400">Logo, kemasan & branding</p>
                </div>
                <a href="designs.php" class="text-xs font-bold text-[#C30064] hover:underline">Lihat Semua</a>
            </div>

            <div class="space-y-3">
                <?php foreach ($recentDesigns as $rd): ?>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono font-bold text-slate-500"><?= htmlspecialchars($rd['ticket_no']); ?></span>
                            <?= getStatusBadge($rd['status'], 'design'); ?>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900"><?= htmlspecialchars($rd['brand_name']); ?></p>
                            <p class="text-[11px] text-slate-500"><?= htmlspecialchars($rd['design_type']); ?> (<?= htmlspecialchars($rd['package_type']); ?>)</p>
                        </div>
                        <div class="pt-1 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Klien: <?= htmlspecialchars($rd['customer_name']); ?></span>
                            <a href="designs.php" class="text-[#C30064] font-bold hover:underline">Kelola &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
