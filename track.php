<?php
$pageTitle = "Lacak Status Pesanan - AN Grafika";
require_once __DIR__ . '/includes/header.php';

$searchQuery = trim($_GET['receipt_no'] ?? '');
$order = null;
$items = [];
$designReq = null;
$searched = false;
$error = '';

if (!empty($searchQuery)) {
    $searched = true;
    try {
        $db = getDB();
        
        $oStmt = $db->prepare("SELECT o.*, u.name as staff_name FROM orders o LEFT JOIN users u ON o.staff_id = u.id WHERE o.receipt_no = :no LIMIT 1");
        $oStmt->execute([':no' => $searchQuery]);
        $order = $oStmt->fetch();

        if ($order) {
            $iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :id");
            $iStmt->execute([':id' => $order['id']]);
            $items = $iStmt->fetchAll();

            $dStmt = $db->prepare("SELECT * FROM design_requests WHERE receipt_no = :no LIMIT 1");
            $dStmt->execute([':no' => $order['receipt_no']]);
            $designReq = $dStmt->fetch();
        } else {
            $dStmt = $db->prepare("SELECT * FROM design_requests WHERE ticket_no = :ticket LIMIT 1");
            $dStmt->execute([':ticket' => $searchQuery]);
            $designReq = $dStmt->fetch();

            if ($designReq && !empty($designReq['receipt_no'])) {
                $oStmt = $db->prepare("SELECT * FROM orders WHERE receipt_no = :no LIMIT 1");
                $oStmt->execute([':no' => $designReq['receipt_no']]);
                $order = $oStmt->fetch();
                if ($order) {
                    $iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :id");
                    $iStmt->execute([':id' => $order['id']]);
                    $items = $iStmt->fetchAll();
                }
            } elseif (!$designReq) {
                $error = "Nomor Struk atau Tiket '{$searchQuery}' tidak ditemukan. Mohon periksa kembali nomor struk Anda.";
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function getStepIndex($prodStatus) {
    switch ($prodStatus) {
        case 'pending': return 1;
        case 'verified': return 2;
        case 'printing': return 3;
        case 'finishing': return 4;
        case 'ready': return 5;
        case 'completed': return 6;
        default: return 1;
    }
}
?>

<div class="bg-gradient-to-r from-[#002052] via-[#00388C] to-[#8c0047] text-white py-12 border-b border-white/10">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-pink-200 text-xs font-semibold mb-3">
            <i data-lucide="radar" class="w-3.5 h-3.5 animate-spin"></i>
            <span>Sistem Pelacakan Real-Time Workshop AN Grafika</span>
        </div>
        <h1 class="text-3xl font-black tracking-tight">Lacak Status Pesanan via No. Struk</h1>
        <p class="text-slate-200 text-xs sm:text-sm mt-1 max-w-lg mx-auto">
            Masukkan Nomor Struk Transaksi kasir (contoh: <code class="text-pink-300 font-mono font-bold">AN-20261030-0045</code>) untuk memantau progress pengerjaan cetak Anda.
        </p>

        <!-- Search Form -->
        <form action="track.php" method="GET" class="mt-6 max-w-xl mx-auto">
            <div class="flex items-center gap-2 bg-white/15 p-2 rounded-2xl border border-white/20 backdrop-blur-md shadow-2xl">
                <div class="relative w-full">
                    <i data-lucide="receipt" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="receipt_no" value="<?= htmlspecialchars($searchQuery); ?>" required placeholder="Masukkan No. Struk (AN-...) atau No. Tiket (DSG-...)" class="w-full pl-10 pr-4 py-3 rounded-xl bg-white text-slate-900 text-xs sm:text-sm font-bold uppercase placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-pink-400">
                </div>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-black text-xs sm:text-sm rounded-xl shrink-0 transition flex items-center gap-2 shadow-lg shadow-black/20">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Cari</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if (!empty($error)): ?>
        <div class="max-w-xl mx-auto p-5 rounded-2xl bg-rose-50 border border-rose-200 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <h3 class="font-bold text-rose-900 text-sm">Pesanan Tidak Ditemukan</h3>
            <p class="text-xs text-rose-700 mt-1"><?= htmlspecialchars($error); ?></p>
            <p class="text-xs text-slate-500 mt-3">Butuh bantuan? Silakan hubungi Customer Service AN Grafika di WhatsApp 0857-9999-0111.</p>
        </div>
    <?php endif; ?>

    <?php if ($order): 
        $currentStep = getStepIndex($order['production_status']);
    ?>
        <div class="space-y-8">
            
            <!-- Progress Tracker Card -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                    <div>
                        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Detail No. Struk</span>
                        <div class="flex items-center gap-3 mt-1">
                            <h2 class="text-xl sm:text-2xl font-black font-mono text-slate-900"><?= htmlspecialchars($order['receipt_no']); ?></h2>
                            <?= getStatusBadge($order['production_status'], 'production'); ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="receipt.php?receipt_no=<?= urlencode($order['receipt_no']); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            <span>Cetak Struk Resmi</span>
                        </a>
                        <a href="https://wa.me/6285799990111?text=Halo%20AN%20Grafika,%20saya%20ingin%20cek%20status%20pesanan%20dengan%20No%20Struk%20<?= urlencode($order['receipt_no']); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#25D366] hover:bg-[#20ba59] shadow-sm transition">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                            <span>Tanya CS WhatsApp</span>
                        </a>
                    </div>
                </div>

                <!-- Stepper Visual -->
                <div class="py-8">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-6">Tahapan Pengerjaan Pesanan:</p>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                        <?php 
                        $steps = [
                            1 => ['name' => '1. Masuk Antrean', 'desc' => 'Pesanan tercatat'],
                            2 => ['name' => '2. Verifikasi File', 'desc' => 'Desain siap cetak'],
                            3 => ['name' => '3. Mesin Cetak', 'desc' => 'Sedang dicetak'],
                            4 => ['name' => '4. Finishing', 'desc' => 'Laminasi/potong'],
                            5 => ['name' => '5. Siap Diambil', 'desc' => 'Siap di toko/kirim'],
                            6 => ['name' => '6. Selesai', 'desc' => 'Pesanan diterima'],
                        ];
                        foreach ($steps as $sIndex => $sData): 
                            $isActive = ($sIndex <= $currentStep);
                            $isCurrent = ($sIndex == $currentStep);
                        ?>
                            <div class="p-3 rounded-2xl border transition <?= $isCurrent ? 'bg-pink-50 border-[#C30064] ring-2 ring-[#C30064]/20' : ($isActive ? 'bg-blue-50/70 border-blue-300' : 'bg-slate-50 border-slate-200 opacity-60'); ?>">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center font-bold text-xs mb-2 <?= $isCurrent ? 'bg-[#C30064] text-white' : ($isActive ? 'bg-[#00388C] text-white' : 'bg-slate-200 text-slate-600'); ?>">
                                    <?php if ($isActive && !$isCurrent): ?>
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    <?php else: ?>
                                        <?= $sIndex; ?>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs font-bold text-slate-900"><?= $sData['name']; ?></p>
                                <p class="text-[10px] text-slate-500 mt-0.5"><?= $sData['desc']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Meta Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-600">
                    <div>
                        <span class="text-slate-400 block">Pemesan:</span>
                        <strong class="text-slate-900 text-sm"><?= htmlspecialchars($order['customer_name']); ?></strong>
                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($order['customer_phone']); ?></p>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Opsi Pengambilan:</span>
                        <strong class="text-slate-900 capitalize"><?= $order['delivery_type'] === 'pickup' ? 'Ambil di Toko AN Grafika' : 'Kirim Ekspedisi/Kurir'; ?></strong>
                        <?php if (!empty($order['delivery_address'])): ?>
                            <p class="text-[11px] text-slate-500 truncate" title="<?= htmlspecialchars($order['delivery_address']); ?>">
                                <?= htmlspecialchars($order['delivery_address']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Status Pembayaran:</span>
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold <?= $order['payment_status'] === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'; ?>">
                            <?= strtoupper($order['payment_status']); ?> (<?= strtoupper($order['payment_method']); ?>)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Ordered Items & Financial Details -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">Rincian Item yang Dipesan</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3">Nama Produk / Layanan</th>
                                <th class="p-3">Spesifikasi & File</th>
                                <th class="p-3 text-right">Harga Satuan</th>
                                <th class="p-3 text-center">Jumlah</th>
                                <th class="p-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="p-3 font-bold text-slate-900"><?= htmlspecialchars($item['item_name']); ?></td>
                                    <td class="p-3">
                                        <p class="text-slate-600"><?= htmlspecialchars($item['specifications'] ?? '-'); ?></p>
                                        <?php if (!empty($item['file_path'])): ?>
                                            <a href="<?= htmlspecialchars($item['file_path']); ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-[#00388C] font-bold hover:underline mt-1">
                                                <i data-lucide="paperclip" class="w-3 h-3"></i>
                                                <span>Lihat File Desain Terunggah</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-right"><?= formatRupiah($item['unit_price']); ?></td>
                                    <td class="p-3 text-center font-bold"><?= $item['quantity']; ?></td>
                                    <td class="p-3 text-right font-bold text-slate-900"><?= formatRupiah($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200">
                            <tr>
                                <td colspan="4" class="p-3 text-right font-medium text-slate-500">Subtotal:</td>
                                <td class="p-3 text-right font-bold text-slate-900"><?= formatRupiah($order['subtotal']); ?></td>
                            </tr>
                            <?php if ((float)$order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="p-3 text-right font-bold text-[#C30064]">
                                        Diskon Voucher (<?= htmlspecialchars($order['voucher_code'] ?? 'Promo'); ?>):
                                    </td>
                                    <td class="p-3 text-right font-bold text-[#C30064]">- <?= formatRupiah($order['discount_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="bg-slate-50 font-bold text-sm">
                                <td colspan="4" class="p-3 text-right text-slate-900">Total Tagihan:</td>
                                <td class="p-3 text-right text-[#00388C] font-black text-base"><?= formatRupiah($order['total_amount']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Design Request Preview Box if linked -->
                <?php if ($designReq): ?>
                    <div class="mt-6 p-5 rounded-2xl bg-pink-50/70 border border-pink-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#C30064] uppercase">Informasi Jasa Desain Terkait</span>
                            <?= getStatusBadge($designReq['status'], 'design'); ?>
                        </div>
                        <p class="text-xs text-slate-800">
                            <strong>Tiket Desain:</strong> <span class="font-mono"><?= htmlspecialchars($designReq['ticket_no']); ?></span> &bull; 
                            <strong>Brand:</strong> <?= htmlspecialchars($designReq['brand_name']); ?> &bull; 
                            <strong>Paket:</strong> <?= htmlspecialchars($designReq['package_type']); ?>
                        </p>
                        <p class="text-xs text-slate-600 italic">"<?= htmlspecialchars($designReq['design_brief']); ?>"</p>
                        
                        <?php if (!empty($designReq['preview_file'])): ?>
                            <div class="pt-2 border-t border-pink-200">
                                <p class="text-xs font-bold text-slate-900 mb-2">Pratinjau Draft Desain Dari Desainer AN Grafika:</p>
                                <a href="<?= htmlspecialchars($designReq['preview_file']); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#C30064] hover:bg-[#a30053] text-white font-bold text-xs rounded-xl shadow-sm transition">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Lihat Pratinjau Desain Final</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    <?php elseif ($searched && !$order): ?>
        <!-- Already handled -->
    <?php else: ?>
        <div class="bg-white rounded-3xl p-10 border border-slate-200 text-center max-w-xl mx-auto shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#00388C] flex items-center justify-center mx-auto mb-4">
                <i data-lucide="receipt" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">Masukkan Nomor Struk Transaksi</h3>
            <p class="text-xs text-slate-500 mt-1">
                Nomor struk tertera di bagian atas kertas struk kasir atau pada invoice pemesanan online Anda.
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-600">
                <span class="text-slate-400">Contoh No. Struk Demo:</span>
                <a href="track.php?receipt_no=AN-20261029-0012" class="px-2.5 py-1 bg-slate-100 rounded-lg hover:bg-blue-50 hover:text-[#00388C] font-mono font-bold">AN-20261029-0xxx</a>
                <a href="track.php?receipt_no=AN-20261030-0045" class="px-2.5 py-1 bg-slate-100 rounded-lg hover:bg-blue-50 hover:text-[#00388C] font-mono font-bold">AN-20261030-xx45</a>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
