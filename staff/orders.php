<?php
$staffTitle = "Antrean & Status Cetak Workshop";
require_once __DIR__ . '/../includes/staff_header.php';

$error = '';
$success = '';

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = cleanInput($_POST['production_status'] ?? 'printing');

    if ($orderId > 0) {
        try {
            $db = getDB();
            $db->prepare("UPDATE orders SET production_status = :ps WHERE id = :id")->execute([':ps' => $newStatus, ':id' => $orderId]);
            $success = "Status pengerjaan cetak berhasil diupdate ke " . strtoupper($newStatus);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch orders
$statusFilter = $_GET['status'] ?? 'all';
$orders = [];
try {
    $db = getDB();
    $sql = "SELECT o.*, (SELECT GROUP_CONCAT(item_name SEPARATOR ', ') FROM order_items WHERE order_id = o.id) as items_summary 
            FROM orders o 
            WHERE 1=1";
    $params = [];

    if ($statusFilter !== 'all') {
        $sql .= " AND o.production_status = :status";
        $params[':status'] = $statusFilter;
    }

    $sql .= " ORDER BY o.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="p-4 sm:p-6 space-y-6 flex-1 overflow-y-auto">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Antrean Pengerjaan Mesin Cetak AN Grafika</h1>
            <p class="text-xs text-slate-500 mt-0.5">Ubah status pesanan saat proses cetak berjalan agar pelanggan dapat memantau realtime via No. Struk.</p>
        </div>
        <a href="pos.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-90 shadow-sm transition">
            <i data-lucide="calculator" class="w-4 h-4"></i>
            <span>Buka Kasir POS</span>
        </a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <!-- Status Filter Buttons -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
        <a href="orders.php" class="px-3.5 py-2 rounded-xl font-bold shrink-0 transition <?= $statusFilter === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'; ?>">
            Semua Antrean (<?= count($orders); ?>)
        </a>
        <a href="orders.php?status=printing" class="px-3.5 py-2 rounded-xl font-bold shrink-0 transition <?= $statusFilter === 'printing' ? 'bg-[#00388C] text-white shadow-sm' : 'bg-white text-blue-700 border border-blue-200 hover:bg-blue-50'; ?>">
            🖨️ Sedang Dicetak Mesin
        </a>
        <a href="orders.php?status=finishing" class="px-3.5 py-2 rounded-xl font-bold shrink-0 transition <?= $statusFilter === 'finishing' ? 'bg-[#C30064] text-white shadow-sm' : 'bg-white text-pink-700 border border-pink-200 hover:bg-pink-50'; ?>">
            ✂️ Tahap Finishing
        </a>
        <a href="orders.php?status=ready" class="px-3.5 py-2 rounded-xl font-bold shrink-0 transition <?= $statusFilter === 'ready' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-white text-cyan-700 border border-cyan-200 hover:bg-cyan-50'; ?>">
            📦 Siap Diambil / Kirim
        </a>
        <a href="orders.php?status=completed" class="px-3.5 py-2 rounded-xl font-bold shrink-0 transition <?= $statusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50'; ?>">
            ✅ Selesai
        </a>
    </div>

    <!-- Orders Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($orders as $o): ?>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <span class="font-mono font-bold text-xs text-[#00388C] block"><?= htmlspecialchars($o['receipt_no']); ?></span>
                            <span class="text-[10px] text-slate-400"><?= date('d/m/Y H:i', strtotime($o['created_at'])); ?></span>
                        </div>
                        <div>
                            <?= getStatusBadge($o['production_status'], 'production'); ?>
                        </div>
                    </div>

                    <div class="py-3 space-y-1.5 text-xs">
                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($o['customer_name']); ?></p>
                        <p class="text-slate-500 font-medium line-clamp-2">📦 <?= htmlspecialchars($o['items_summary'] ?: 'Item pesanan percetakan'); ?></p>
                        <p class="text-[11px] text-slate-400">Total Tagihan: <strong class="text-slate-800"><?= formatRupiah($o['total_amount']); ?></strong></p>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <a href="../receipt.php?receipt_no=<?= urlencode($o['receipt_no']); ?>" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Cetak Struk</span>
                    </a>

                    <!-- Fast update select -->
                    <form method="POST" class="flex-1 text-right">
                        <input type="hidden" name="action" value="quick_update_status">
                        <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                        <select name="production_status" onchange="this.form.submit()" class="px-2 py-1.5 rounded-xl border border-slate-300 text-[11px] font-bold bg-slate-50 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                            <option value="pending" <?= $o['production_status'] === 'pending' ? 'selected' : ''; ?>>Menunggu Antrean</option>
                            <option value="verified" <?= $o['production_status'] === 'verified' ? 'selected' : ''; ?>>File Terverifikasi</option>
                            <option value="printing" <?= $o['production_status'] === 'printing' ? 'selected' : ''; ?>>Proses Cetak Mesin</option>
                            <option value="finishing" <?= $o['production_status'] === 'finishing' ? 'selected' : ''; ?>>Tahap Finishing</option>
                            <option value="ready" <?= $o['production_status'] === 'ready' ? 'selected' : ''; ?>>Siap Diambil Klien</option>
                            <option value="completed" <?= $o['production_status'] === 'completed' ? 'selected' : ''; ?>>Selesai Transaksi</option>
                        </select>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
