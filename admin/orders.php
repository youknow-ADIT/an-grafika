<?php
$adminTitle = "Pesanan Cetak & POS";
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = cleanInput($_POST['production_status'] ?? 'pending');
    $payStatus = cleanInput($_POST['payment_status'] ?? 'paid');

    if ($orderId > 0) {
        try {
            $db = getDB();
            $stmt = $db->prepare("UPDATE orders SET production_status = :ps, payment_status = :pay WHERE id = :id");
            $stmt->execute([':ps' => $newStatus, ':pay' => $payStatus, ':id' => $orderId]);
            $success = "Status pesanan ID #{$orderId} berhasil diperbarui!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$orders = [];
try {
    $db = getDB();
    $sql = "SELECT o.*, u.name as staff_name FROM orders o LEFT JOIN users u ON o.staff_id = u.id WHERE 1=1";
    $params = [];

    if ($statusFilter !== 'all' && !empty($statusFilter)) {
        $sql .= " AND o.production_status = :status";
        $params[':status'] = $statusFilter;
    }

    if (!empty($searchQuery)) {
        $sql .= " AND (o.receipt_no LIKE :q OR o.customer_name LIKE :q OR o.customer_phone LIKE :q)";
        $params[':q'] = "%{$searchQuery}%";
    }

    $sql .= " ORDER BY o.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-6">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Semua Pesanan Cetak & POS</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola antrean pengerjaan mesin cetak AN Grafika, validasi file, dan cetak ulang struk kasir.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../staff/pos.php" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-sm transition">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                <span>+ Transaksi Kasir POS Baru</span>
            </a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            <?php 
            $filterTabs = [
                'all'       => 'Semua Pesanan',
                'pending'   => 'Menunggu Antrean',
                'verified'  => 'File Terverifikasi',
                'printing'  => 'Proses Cetak',
                'finishing' => 'Finishing',
                'ready'     => 'Siap Ambil/Kirim',
                'completed' => 'Selesai'
            ];
            foreach ($filterTabs as $fKey => $fLabel):
            ?>
                <a href="orders.php?status=<?= $fKey; ?>" class="px-3 py-1.5 rounded-lg shrink-0 transition font-semibold <?= $statusFilter === $fKey ? 'bg-[#00388C] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">
                    <?= $fLabel; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search Form -->
        <form action="orders.php" method="GET" class="w-full md:w-64">
            <?php if ($statusFilter !== 'all'): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter); ?>">
            <?php endif; ?>
            <div class="relative">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Cari No. Struk / Nama..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>
        </form>
    </div>

    <!-- Orders Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">No. Struk</th>
                        <th class="p-3.5">Pelanggan</th>
                        <th class="p-3.5">Tipe & Kasir</th>
                        <th class="p-3.5">Total & Diskon</th>
                        <th class="p-3.5">Status Cetak & Bayar</th>
                        <th class="p-3.5 text-center">Update Progress</th>
                        <th class="p-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                Tidak ada data pesanan pada kriteria filter ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-3.5 font-mono font-bold text-slate-900">
                                    <a href="../receipt.php?receipt_no=<?= urlencode($o['receipt_no']); ?>" target="_blank" class="hover:text-[#00388C] hover:underline">
                                        <?= htmlspecialchars($o['receipt_no']); ?>
                                    </a>
                                    <p class="text-[10px] text-slate-400 font-sans mt-0.5"><?= date('d/m/Y H:i', strtotime($o['created_at'])); ?></p>
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($o['customer_name']); ?></p>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($o['customer_phone']); ?></p>
                                </td>
                                <td class="p-3.5">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $o['order_type'] === 'pos' ? 'bg-pink-100 text-[#C30064]' : ($o['order_type'] === 'design' ? 'bg-indigo-100 text-[#00388C]' : 'bg-blue-100 text-[#00388C]'); ?>">
                                        <?= strtoupper($o['order_type']); ?>
                                    </span>
                                    <p class="text-[10px] text-slate-400 mt-1"><?= htmlspecialchars($o['staff_name'] ?? 'Online'); ?></p>
                                </td>
                                <td class="p-3.5 font-bold text-slate-900">
                                    <?= formatRupiah($o['total_amount']); ?>
                                    <?php if ((float)$o['discount_amount'] > 0): ?>
                                        <p class="text-[10px] text-[#C30064] font-medium">Hemat <?= formatRupiah($o['discount_amount']); ?> (<?= htmlspecialchars($o['voucher_code']); ?>)</p>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5 space-y-1">
                                    <div><?= getStatusBadge($o['production_status'], 'production'); ?></div>
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold uppercase <?= $o['payment_status'] === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'; ?>">
                                        <?= strtoupper($o['payment_status']); ?> (<?= strtoupper($o['payment_method']); ?>)
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <form method="POST" class="inline-flex items-center gap-1">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                        <select name="production_status" onchange="this.form.submit()" class="px-2 py-1 rounded-lg border border-slate-300 text-[11px] font-bold bg-white focus:outline-none focus:ring-1 focus:ring-[#00388C]">
                                            <option value="pending" <?= $o['production_status'] === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="verified" <?= $o['production_status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                            <option value="printing" <?= $o['production_status'] === 'printing' ? 'selected' : ''; ?>>Cetak</option>
                                            <option value="finishing" <?= $o['production_status'] === 'finishing' ? 'selected' : ''; ?>>Finishing</option>
                                            <option value="ready" <?= $o['production_status'] === 'ready' ? 'selected' : ''; ?>>Siap Ambil</option>
                                            <option value="completed" <?= $o['production_status'] === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="../receipt.php?receipt_no=<?= urlencode($o['receipt_no']); ?>" target="_blank" title="Cetak Struk Kasir" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <a href="../track.php?receipt_no=<?= urlencode($o['receipt_no']); ?>" target="_blank" title="Halaman Pelacakan" class="p-1.5 rounded-lg bg-slate-100 hover:bg-blue-100 text-[#00388C] transition">
                                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
