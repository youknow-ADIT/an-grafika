<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$format = $_GET['format'] ?? 'csv';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$orderType = $_GET['order_type'] ?? 'all';
$staffId = $_GET['staff_id'] ?? 'all';
$paymentStatus = $_GET['payment_status'] ?? 'all';

$orders = [];
$totalRevenue = 0;
$totalDiscounts = 0;
$totalSubtotal = 0;

try {
    $db = getDB();
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

    $sql .= " ORDER BY o.id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as $o) {
        if ($o['payment_status'] === 'paid') {
            $totalRevenue += (float)$o['total_amount'];
            $totalDiscounts += (float)$o['discount_amount'];
            $totalSubtotal += (float)$o['subtotal'];
        }
    }
} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}

if ($format === 'csv') {
    $filename = "Laporan_Penjualan_AN_Grafika_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    
    fputcsv($output, [
        'No',
        'No. Struk',
        'Tanggal & Jam',
        'Nama Pelanggan',
        'No. Telepon/WA',
        'Tipe Pesanan',
        'Kasir / Operator',
        'Subtotal (Rp)',
        'Diskon Voucher (Rp)',
        'Kode Voucher',
        'Total Akhir (Rp)',
        'Metode Pembayaran',
        'Status Bayar',
        'Status Produksi Cetak'
    ]);
    
    $no = 1;
    foreach ($orders as $row) {
        fputcsv($output, [
            $no++,
            $row['receipt_no'],
            $row['created_at'],
            $row['customer_name'],
            $row['customer_phone'],
            strtoupper($row['order_type']),
            $row['staff_name'] ?? 'Online System',
            $row['subtotal'],
            $row['discount_amount'],
            $row['voucher_code'] ?? '-',
            $row['total_amount'],
            strtoupper($row['payment_method']),
            strtoupper($row['payment_status']),
            strtoupper($row['production_status'])
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['', '', '', '', '', '', 'TOTAL OMSET KOTOR:', $totalSubtotal]);
    fputcsv($output, ['', '', '', '', '', '', 'TOTAL DISKON VOUCHER:', $totalDiscounts]);
    fputcsv($output, ['', '', '', '', '', '', 'TOTAL PENDAPATAN BERSIH:', $totalRevenue]);
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - AN Grafika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-6 flex flex-col items-center">

    <!-- Action Bar -->
    <div class="no-print max-w-4xl w-full mb-6 flex items-center justify-between">
        <a href="reports.php" class="inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-slate-900 font-bold bg-white px-3 py-2 rounded-xl border border-slate-200">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Panel Laporan</span>
        </a>

        <div class="flex items-center gap-2">
            <a href="export.php?format=csv&<?= http_build_query($_GET); ?>" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-1.5">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Download Excel (.csv)</span>
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95 transition flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Report Paper Sheet -->
    <div class="bg-white max-w-4xl w-full p-8 sm:p-12 rounded-2xl shadow-xl border border-slate-200 space-y-6">
        
        <!-- Letterhead Kop Surat AN Grafika -->
        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-5">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white border border-slate-200 p-1 flex items-center justify-center">
                    <img src="../assets/img/logo.png" alt="AN Grafika" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">PT AN GRAFIKA INDONESIA</h1>
                    <p class="text-xs text-slate-600 font-medium">Digital Offset Printing &bull; Studio Desain Profesional &bull; POS System</p>
                    <p class="text-[11px] text-slate-500">Workshop AN Grafika &bull; Telp/WA: 0857-9999-0111 &bull; Email: info@angrafika.id</p>
                </div>
            </div>
            <div class="text-right text-xs text-slate-500">
                <p class="font-black text-[#00388C] text-sm uppercase">DOKUMEN RESMI</p>
                <p>Dicetak: <?= date('d F Y, H:i'); ?> WIB</p>
                <p>Oleh: <?= htmlspecialchars($currentUser['name']); ?></p>
            </div>
        </div>

        <!-- Title -->
        <div class="text-center py-2">
            <h2 class="text-lg font-black text-slate-900 tracking-tight uppercase">LAPORAN REKAPITULASI PENJUALAN PERCETAKAN</h2>
            <p class="text-xs text-slate-500 mt-1">
                Periode: <strong class="text-slate-800"><?= date('d F Y', strtotime($startDate)); ?></strong> s/d <strong class="text-slate-800"><?= date('d F Y', strtotime($endDate)); ?></strong>
            </p>
        </div>

        <!-- Financial Summary Grid -->
        <div class="grid grid-cols-3 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
            <div>
                <span class="text-slate-500 block">Total Omset Kotor:</span>
                <strong class="text-base text-slate-900"><?= formatRupiah($totalSubtotal); ?></strong>
            </div>
            <div>
                <span class="text-slate-500 block">Total Diskon Diberikan:</span>
                <strong class="text-base text-[#C30064]"><?= formatRupiah($totalDiscounts); ?></strong>
            </div>
            <div>
                <span class="text-slate-500 block">Pendapatan Bersih (Net):</span>
                <strong class="text-base text-[#00388C] font-black"><?= formatRupiah($totalRevenue); ?></strong>
            </div>
        </div>

        <!-- Table Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-2.5 border-r border-slate-200 text-center w-10">No</th>
                        <th class="p-2.5 border-r border-slate-200">No. Struk</th>
                        <th class="p-2.5 border-r border-slate-200">Tanggal</th>
                        <th class="p-2.5 border-r border-slate-200">Pelanggan</th>
                        <th class="p-2.5 border-r border-slate-200">Kasir</th>
                        <th class="p-2.5 border-r border-slate-200 text-right">Subtotal</th>
                        <th class="p-2.5 border-r border-slate-200 text-right">Diskon</th>
                        <th class="p-2.5 border-r border-slate-200 text-right">Total Akhir</th>
                        <th class="p-2.5 text-center">Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php 
                    $no = 1;
                    foreach ($orders as $o): 
                    ?>
                        <tr>
                            <td class="p-2 border-r border-slate-200 text-center"><?= $no++; ?></td>
                            <td class="p-2 border-r border-slate-200 font-mono font-bold text-slate-900"><?= htmlspecialchars($o['receipt_no']); ?></td>
                            <td class="p-2 border-r border-slate-200 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($o['created_at'])); ?></td>
                            <td class="p-2 border-r border-slate-200 font-medium"><?= htmlspecialchars($o['customer_name']); ?></td>
                            <td class="p-2 border-r border-slate-200 text-[11px]"><?= htmlspecialchars($o['staff_name'] ?? 'Online'); ?></td>
                            <td class="p-2 border-r border-slate-200 text-right"><?= number_format($o['subtotal'], 0, ',', '.'); ?></td>
                            <td class="p-2 border-r border-slate-200 text-right text-[#C30064] font-medium"><?= $o['discount_amount'] > 0 ? number_format($o['discount_amount'], 0, ',', '.') : '-'; ?></td>
                            <td class="p-2 border-r border-slate-200 text-right font-bold text-slate-900"><?= number_format($o['total_amount'], 0, ',', '.'); ?></td>
                            <td class="p-2 text-center uppercase font-bold text-[10px]"><?= $o['payment_method']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-100 font-black border-t-2 border-slate-300">
                    <tr>
                        <td colspan="5" class="p-2.5 text-right border-r border-slate-200">TOTAL:</td>
                        <td class="p-2.5 text-right border-r border-slate-200"><?= number_format($totalSubtotal, 0, ',', '.'); ?></td>
                        <td class="p-2.5 text-right border-r border-slate-200 text-[#C30064]"><?= number_format($totalDiscounts, 0, ',', '.'); ?></td>
                        <td class="p-2.5 text-right border-r border-slate-200 text-[#00388C] font-black text-sm"><?= number_format($totalRevenue, 0, ',', '.'); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Signature Signoff Blocks -->
        <div class="grid grid-cols-2 gap-8 pt-10 text-xs text-center">
            <div>
                <p class="text-slate-500">Dibuat & Diverifikasi Oleh,</p>
                <p class="font-bold text-slate-800 mt-1">Petugas Kasir / Keuangan</p>
                <div class="h-16"></div>
                <p class="font-bold underline text-slate-900">( ............................................ )</p>
            </div>
            <div>
                <p class="text-slate-500">Tanggal: <?= date('d F Y'); ?></p>
                <p class="font-bold text-slate-800 mt-1">Administrator Utama AN Grafika</p>
                <div class="h-16"></div>
                <p class="font-bold underline text-slate-900"><?= htmlspecialchars($currentUser['name']); ?></p>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
