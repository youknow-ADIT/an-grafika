<?php
$adminTitle = "Olah Laporan & Ekspor";
require_once __DIR__ . '/../includes/admin_header.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$orderType = $_GET['order_type'] ?? 'all';
$staffId = $_GET['staff_id'] ?? 'all';
$paymentStatus = $_GET['payment_status'] ?? 'all';

$orders = [];
$totalRevenue = 0;
$totalDiscounts = 0;
$totalSubtotal = 0;
$countTransactions = 0;

$staffList = [];

try {
    $db = getDB();
    $staffList = $db->query("SELECT id, name FROM users WHERE role = 'pegawai' ORDER BY name ASC")->fetchAll();

    $sql = "SELECT o.*, u.name as staff_name 
            FROM orders o 
            LEFT JOIN users u ON o.staff_id = u.id 
            WHERE DATE(o.created_at) >= :sdate AND DATE(o.created_at) <= :edate";
    $params = [
        ':sdate' => $startDate,
        ':edate' => $endDate
    ];

    if ($orderType !== 'all') {
        $sql .= " AND o.order_type = :otype";
        $params[':otype'] = $orderType;
    }

    if ($staffId !== 'all') {
        $sql .= " AND o.staff_id = :sid";
        $params[':sid'] = $staffId;
    }

    if ($paymentStatus !== 'all') {
        $sql .= " AND o.payment_status = :pstatus";
        $params[':pstatus'] = $paymentStatus;
    }

    $sql .= " ORDER BY o.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as $o) {
        if ($o['payment_status'] === 'paid') {
            $totalRevenue += (float)$o['total_amount'];
            $totalDiscounts += (float)$o['discount_amount'];
            $totalSubtotal += (float)$o['subtotal'];
            $countTransactions++;
        }
    }

} catch (Exception $e) {
    // handled
}

$exportQuery = http_build_query([
    'start_date'     => $startDate,
    'end_date'       => $endDate,
    'order_type'     => $orderType,
    'staff_id'       => $staffId,
    'payment_status' => $paymentStatus
]);
?>

<div class="space-y-6">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Olah Laporan Penjualan & Ekspor Data</h1>
            <p class="text-xs text-slate-500 mt-0.5">Filter transaksi AN Grafika berdasarkan periode tanggal, jenis pemesanan, kasir, dan download laporan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="export.php?format=csv&<?= $exportQuery; ?>" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 shadow-sm transition">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                <span>Download Excel (CSV)</span>
            </a>
            <a href="export.php?format=print&<?= $exportQuery; ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-md shadow-[#00388C]/20 transition">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Dokumen Laporan / PDF</span>
            </a>
        </div>
    </div>

    <!-- Filter Form Toolbar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <form action="reports.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Mulai Tanggal:</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate); ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate); ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Tipe Pesanan:</label>
                <select name="order_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                    <option value="all" <?= $orderType === 'all' ? 'selected' : ''; ?>>Semua Tipe Pesanan</option>
                    <option value="pos" <?= $orderType === 'pos' ? 'selected' : ''; ?>>POS Kasir Toko</option>
                    <option value="print" <?= $orderType === 'print' ? 'selected' : ''; ?>>Pemesanan Cetak Online</option>
                    <option value="design" <?= $orderType === 'design' ? 'selected' : ''; ?>>Request Jasa Desain</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Pegawai Kasir:</label>
                <select name="staff_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                    <option value="all" <?= $staffId === 'all' ? 'selected' : ''; ?>>Semua Kasir</option>
                    <?php foreach ($staffList as $st): ?>
                        <option value="<?= $st['id']; ?>" <?= $staffId == $st['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($st['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pendapatan Bersih (Net)</span>
            <p class="text-xl sm:text-2xl font-black text-[#00388C] mt-1"><?= formatRupiah($totalRevenue); ?></p>
            <span class="text-[11px] text-slate-400 mt-1 block">Dari transaksi status Lunas (Paid)</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Diskon Voucher</span>
            <p class="text-xl sm:text-2xl font-black text-[#C30064] mt-1"><?= formatRupiah($totalDiscounts); ?></p>
            <span class="text-[11px] text-slate-400 mt-1 block">Potongan kupon promo voucher</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Omset Kotor (Subtotal)</span>
            <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= formatRupiah($totalSubtotal); ?></p>
            <span class="text-[11px] text-slate-400 mt-1 block">Sebelum dipotong voucher</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Jumlah Transaksi Lunas</span>
            <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($countTransactions, 0, ',', '.'); ?> Struk</p>
            <span class="text-[11px] text-slate-400 mt-1 block">Periode terpilih</span>
        </div>

    </div>

    <!-- Detailed Report Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-sm text-slate-900">Rincian Transaksi Penjualan</h2>
                <p class="text-[11px] text-slate-400">Periode: <?= date('d M Y', strtotime($startDate)); ?> s/d <?= date('d M Y', strtotime($endDate)); ?></p>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-lg">
                <?= count($orders); ?> Data Ditemukan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">Tanggal</th>
                        <th class="p-3.5">No. Struk</th>
                        <th class="p-3.5">Pelanggan</th>
                        <th class="p-3.5">Tipe & Kasir</th>
                        <th class="p-3.5 text-right">Subtotal</th>
                        <th class="p-3.5 text-right">Diskon Voucher</th>
                        <th class="p-3.5 text-right">Total Akhir</th>
                        <th class="p-3.5 text-center">Metode Bayar</th>
                        <th class="p-3.5">Status Cetak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                Tidak ada transaksi penjualan pada rentang tanggal dan filter ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="font-medium text-slate-900"><?= date('d/m/Y', strtotime($o['created_at'])); ?></span>
                                    <p class="text-[10px] text-slate-400"><?= date('H:i', strtotime($o['created_at'])); ?> WIB</p>
                                </td>
                                <td class="p-3.5 font-mono font-bold text-[#00388C] whitespace-nowrap">
                                    <a href="../receipt.php?receipt_no=<?= urlencode($o['receipt_no']); ?>" target="_blank" class="hover:underline">
                                        <?= htmlspecialchars($o['receipt_no']); ?>
                                    </a>
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($o['customer_name']); ?></p>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($o['customer_phone']); ?></p>
                                </td>
                                <td class="p-3.5">
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold uppercase <?= $o['order_type'] === 'pos' ? 'bg-pink-100 text-[#C30064]' : 'bg-blue-100 text-[#00388C]'; ?>">
                                        <?= strtoupper($o['order_type']); ?>
                                    </span>
                                    <p class="text-[10px] text-slate-500 mt-0.5"><?= htmlspecialchars($o['staff_name'] ?? 'Online'); ?></p>
                                </td>
                                <td class="p-3.5 text-right text-slate-700 font-medium">
                                    <?= formatRupiah($o['subtotal']); ?>
                                </td>
                                <td class="p-3.5 text-right text-[#C30064] font-medium">
                                    <?php if ((float)$o['discount_amount'] > 0): ?>
                                        -<?= formatRupiah($o['discount_amount']); ?>
                                        <p class="text-[9px] text-slate-400 font-mono"><?= htmlspecialchars($o['voucher_code']); ?></p>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5 text-right font-black text-slate-900">
                                    <?= formatRupiah($o['total_amount']); ?>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                        <?= strtoupper($o['payment_method']); ?>
                                    </span>
                                </td>
                                <td class="p-3.5">
                                    <?= getStatusBadge($o['production_status'], 'production'); ?>
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
