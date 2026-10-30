<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$receiptNo = trim($_GET['receipt_no'] ?? '');
$order = null;
$items = [];

if (!empty($receiptNo)) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT o.*, u.name as staff_name FROM orders o LEFT JOIN users u ON o.staff_id = u.id WHERE o.receipt_no = :no LIMIT 1");
        $stmt->execute([':no' => $receiptNo]);
        $order = $stmt->fetch();

        if ($order) {
            $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :id");
            $itemStmt->execute([':id' => $order['id']]);
            $items = $itemStmt->fetchAll();
        }
    } catch (Exception $e) {
        // handled
    }
}

if (!$order) {
    die("Struk dengan nomor tersebut tidak ditemukan. <a href='index.php'>Kembali</a>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #<?= htmlspecialchars($order['receipt_no']); ?> - AN Grafika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Courier New', Courier, monospace; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .thermal-receipt {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 80mm !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Action Toolbar (Hidden during print) -->
    <div class="no-print max-w-sm w-full mb-6 space-y-3 font-sans">
        <div class="flex items-center justify-between">
            <a href="index.php" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800 font-bold">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Beranda</span>
            </a>
            <span class="text-xs font-bold text-slate-700">Pratinjau Struk Kasir</span>
        </div>

        <div class="flex gap-2">
            <button onclick="window.print()" class="flex-1 py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Struk (Print)</span>
            </button>
            <a href="track.php?receipt_no=<?= urlencode($order['receipt_no']); ?>" class="py-3 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white font-bold text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-1.5">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span>Lacak Pesanan</span>
            </a>
        </div>

        <?php if (isStaff()): ?>
            <a href="staff/pos.php" class="block text-center py-2 px-3 bg-pink-50 text-[#C30064] border border-pink-200 rounded-xl text-xs font-bold hover:bg-pink-100 transition">
                &larr; Transaksi Baru di Kasir POS
            </a>
        <?php endif; ?>
    </div>

    <!-- Thermal Receipt Card -->
    <div id="receipt-print-area" class="thermal-receipt bg-white shadow-xl rounded-xl">
        
        <!-- Receipt Header -->
        <div class="text-center">
            <h1 class="text-lg font-black tracking-tight uppercase">AN GRAFIKA</h1>
            <p class="text-[11px] font-bold">DIGITAL & OFFSET PRINTING</p>
            <p class="text-[10px] text-slate-600 mt-0.5">Studio Desain & Percetakan Profesional</p>
            <p class="text-[10px] text-slate-500">WA: 0857-9999-0111</p>
        </div>

        <div class="double-line"></div>

        <!-- Receipt Meta -->
        <div class="text-[11px] space-y-1">
            <div class="flex justify-between">
                <span>NO. STRUK:</span>
                <span class="font-bold"><?= htmlspecialchars($order['receipt_no']); ?></span>
            </div>
            <div class="flex justify-between">
                <span>TANGGAL:</span>
                <span><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="flex justify-between">
                <span>KASIR/OP:</span>
                <span><?= htmlspecialchars($order['staff_name'] ?? 'Online System'); ?></span>
            </div>
            <div class="flex justify-between">
                <span>PELANGGAN:</span>
                <span class="font-bold"><?= htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="flex justify-between">
                <span>KONTAK:</span>
                <span><?= htmlspecialchars($order['customer_phone']); ?></span>
            </div>
        </div>

        <div class="dashed-line"></div>

        <!-- Items Table -->
        <div class="space-y-2 text-[11px]">
            <?php foreach ($items as $item): ?>
                <div>
                    <div class="font-bold uppercase"><?= htmlspecialchars($item['item_name']); ?></div>
                    <?php if (!empty($item['specifications'])): ?>
                        <div class="text-[10px] text-slate-500 pl-2"><?= htmlspecialchars($item['specifications']); ?></div>
                    <?php endif; ?>
                    <div class="flex justify-between text-[11px] pl-2 mt-0.5">
                        <span><?= $item['quantity']; ?> x <?= number_format($item['unit_price'], 0, ',', '.'); ?></span>
                        <span class="font-bold"><?= number_format($item['subtotal'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dashed-line"></div>

        <!-- Totals & Payment -->
        <div class="text-[11px] space-y-1">
            <div class="flex justify-between">
                <span>SUBTOTAL:</span>
                <span><?= number_format($order['subtotal'], 0, ',', '.'); ?></span>
            </div>
            <?php if ((float)$order['discount_amount'] > 0): ?>
                <div class="flex justify-between font-bold">
                    <span>DISKON (<?= htmlspecialchars($order['voucher_code'] ?? 'VOUCHER'); ?>):</span>
                    <span>-<?= number_format($order['discount_amount'], 0, ',', '.'); ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between text-sm font-black border-t border-dashed border-slate-400 pt-1 my-1">
                <span>TOTAL:</span>
                <span>Rp <?= number_format($order['total_amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="flex justify-between">
                <span>BAYAR (<?= strtoupper($order['payment_method']); ?>):</span>
                <span>Rp <?= number_format($order['paid_amount'] > 0 ? $order['paid_amount'] : $order['total_amount'], 0, ',', '.'); ?></span>
            </div>
            <?php if ((float)$order['change_amount'] > 0): ?>
                <div class="flex justify-between">
                    <span>KEMBALI:</span>
                    <span>Rp <?= number_format($order['change_amount'], 0, ',', '.'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="double-line"></div>

        <!-- Receipt Footer & Order Tracking Notice -->
        <div class="text-center space-y-2 text-[10px]">
            <p class="font-bold uppercase tracking-wider text-[11px]">*** SIMPAN STRUK INI ***</p>
            <p>Gunakan Nomor Struk di atas untuk melacak proses pengerjaan cetak Anda di:</p>
            <p class="font-bold underline text-[11px]">https://angrafika.id/track.php</p>

            <!-- Barcode Graphic Simulation -->
            <div class="pt-2">
                <div class="inline-block bg-slate-900 text-white font-mono px-3 py-1 rounded text-xs tracking-widest font-black">
                    *<?= htmlspecialchars($order['receipt_no']); ?>*
                </div>
            </div>

            <p class="text-slate-500 pt-2 text-[9px]">
                Barang yang sudah dicetak sesuai brief tidak dapat dibatalkan. Komplain maksimal 1x24 jam sejak pesanan diterima dengan membawa struk ini.
            </p>
            <p class="font-bold pt-1">Terima Kasih Atas Kepercayaan Anda!</p>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
