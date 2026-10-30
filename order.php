<?php
$pageTitle = "Form Pemesanan & Kalkulator Cetak - AN Grafika";
require_once __DIR__ . '/includes/header.php';

$selectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 1;
$products = [];
$activeProduct = null;
$error = '';
$success = '';

try {
    $db = getDB();
    $prodStmt = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.name ASC");
    $products = $prodStmt->fetchAll();

    foreach ($products as $p) {
        if ($p['id'] == $selectedProductId) {
            $activeProduct = $p;
            break;
        }
    }
    if (!$activeProduct && !empty($products)) {
        $activeProduct = $products[0];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $customerName = cleanInput($_POST['customer_name'] ?? '');
    $customerPhone = cleanInput($_POST['customer_phone'] ?? '');
    $customerEmail = cleanInput($_POST['customer_email'] ?? '');
    $deliveryType = cleanInput($_POST['delivery_type'] ?? 'pickup');
    $deliveryAddress = cleanInput($_POST['delivery_address'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    $voucherCode = strtoupper(trim(cleanInput($_POST['voucher_code'] ?? '')));
    $selectedOptions = $_POST['options'] ?? [];

    if (empty($customerName) || empty($customerPhone)) {
        $error = "Mohon lengkapi Nama Pemesan dan No. WhatsApp aktif Anda.";
    } else {
        try {
            $db = getDB();
            $pStmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'active'");
            $pStmt->execute([':id' => $productId]);
            $prod = $pStmt->fetch();

            if (!$prod) {
                throw new Exception("Produk tidak ditemukan.");
            }

            $unitPrice = (float)$prod['base_price'];
            $specArr = [];

            $optionsConfig = json_decode($prod['options_json'] ?? '{}', true);
            foreach ($selectedOptions as $optKey => $optVal) {
                if (isset($optionsConfig[$optKey])) {
                    foreach ($optionsConfig[$optKey] as $item) {
                        if ($item['name'] === $optVal) {
                            $unitPrice += (float)($item['price'] ?? 0);
                            $specArr[] = ucfirst($optKey) . ": " . $item['name'];
                            break;
                        }
                    }
                }
            }

            $specsString = implode(" | ", $specArr);
            $subtotal = $unitPrice * $qty;
            $discountAmount = 0.00;
            $appliedVoucher = null;

            if (!empty($voucherCode)) {
                $vStmt = $db->prepare("SELECT * FROM vouchers WHERE code = :code AND status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE() AND (usage_limit = 0 OR used_count < usage_limit)");
                $vStmt->execute([':code' => $voucherCode]);
                $v = $vStmt->fetch();

                if ($v && $subtotal >= (float)$v['min_purchase']) {
                    if ($v['discount_type'] === 'percent') {
                        $calcDisc = ($subtotal * (float)$v['discount_value']) / 100;
                        if (!empty($v['max_discount']) && (float)$v['max_discount'] > 0) {
                            $calcDisc = min($calcDisc, (float)$v['max_discount']);
                        }
                        $discountAmount = $calcDisc;
                    } else {
                        $discountAmount = min($subtotal, (float)$v['discount_value']);
                    }
                    $appliedVoucher = $v['code'];
                    $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = :id")->execute([':id' => $v['id']]);
                }
            }

            $totalAmount = max(0, $subtotal - $discountAmount);
            $receiptNo = generateReceiptNo('AN');

            // Handle file upload
            $uploadedFilePath = null;
            $uploadedFileName = null;
            if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('design_file', 'designs');
                if (isset($uploadResult['success'])) {
                    $uploadedFilePath = $uploadResult['file_path'];
                    $uploadedFileName = $uploadResult['file_name'];
                } elseif (isset($uploadResult['error'])) {
                    throw new Exception($uploadResult['error']);
                }
            }

            // Insert into orders
            $orderSql = "INSERT INTO orders (receipt_no, order_type, customer_name, customer_phone, customer_email, subtotal, voucher_code, discount_amount, total_amount, paid_amount, change_amount, payment_method, payment_status, production_status, delivery_type, delivery_address, notes) 
                         VALUES (:receipt_no, 'print', :customer_name, :customer_phone, :customer_email, :subtotal, :voucher_code, :discount_amount, :total_amount, :total_amount, 0.00, 'transfer', 'paid', 'verified', :delivery_type, :delivery_address, :notes)";
            $orderStmt = $db->prepare($orderSql);
            $orderStmt->execute([
                ':receipt_no'       => $receiptNo,
                ':customer_name'    => $customerName,
                ':customer_phone'   => $customerPhone,
                ':customer_email'   => $customerEmail,
                ':subtotal'         => $subtotal,
                ':voucher_code'     => $appliedVoucher,
                ':discount_amount'  => $discountAmount,
                ':total_amount'     => $totalAmount,
                ':delivery_type'    => $deliveryType,
                ':delivery_address' => $deliveryAddress,
                ':notes'            => $notes
            ]);

            $orderId = $db->lastInsertId();

            // Insert into order_items
            $itemSql = "INSERT INTO order_items (order_id, product_id, item_name, specifications, unit_price, quantity, subtotal, file_path, file_name)
                        VALUES (:order_id, :product_id, :item_name, :specifications, :unit_price, :quantity, :subtotal, :file_path, :file_name)";
            $itemStmt = $db->prepare($itemSql);
            $itemStmt->execute([
                ':order_id'       => $orderId,
                ':product_id'     => $prod['id'],
                ':item_name'      => $prod['name'],
                ':specifications' => $specsString,
                ':unit_price'     => $unitPrice,
                ':quantity'       => $qty,
                ':subtotal'       => $subtotal,
                ':file_path'      => $uploadedFilePath,
                ':file_name'      => $uploadedFileName
            ]);

            header("Location: receipt.php?receipt_no=" . urlencode($receiptNo) . "&status=new");
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="bg-gradient-to-r from-[#002052] via-[#00388C] to-[#800042] text-white py-8 border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Kalkulator & Form Order Cetak Online</h1>
        <p class="text-xs sm:text-sm text-slate-200 mt-1">
            Hitung biaya pesanan cetak secara otomatis, unggah file siap cetak, dan dapatkan Nomor Struk resmi pelacakan.
        </p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0"></i>
            <span><?= htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="order-form">
        <input type="hidden" name="submit_order" value="1">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Product Selection & Config -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Product Selector Card -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-full bg-[#00388C]/10 text-[#00388C] font-black flex items-center justify-center text-xs">1</span>
                        <h2 class="text-base font-bold text-slate-900">Pilih Produk & Spesifikasi Cetak</h2>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Produk Percetakan:</label>
                        <select name="product_id" id="product_id" onchange="window.location.href='order.php?product_id=' + this.value" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id']; ?>" <?= ($activeProduct && $activeProduct['id'] == $p['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($p['name']); ?> — (Mulai <?= formatRupiah($p['base_price']); ?> / <?= $p['unit']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($activeProduct): 
                        $optionsConfig = json_decode($activeProduct['options_json'] ?? '{}', true);
                    ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row gap-4 items-center">
                            <div class="w-20 h-20 rounded-xl bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                <?php if (!empty($activeProduct['image']) && file_exists(__DIR__ . '/' . $activeProduct['image'])): ?>
                                    <img src="<?= htmlspecialchars($activeProduct['image']); ?>" alt="<?= htmlspecialchars($activeProduct['name']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i data-lucide="printer" class="w-8 h-8 text-[#00388C]"></i>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-700 flex-1">
                                <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($activeProduct['name']); ?></p>
                                <p class="text-slate-500 mt-0.5"><?= htmlspecialchars($activeProduct['description']); ?></p>
                                <p class="mt-2 font-semibold text-[#00388C]">
                                    Satuan: <span class="capitalize"><?= htmlspecialchars($activeProduct['unit']); ?></span> &bull; 
                                    Min. Order: <?= $activeProduct['min_order']; ?> <?= $activeProduct['unit']; ?> &bull; 
                                    Estimasi: <?= $activeProduct['estimated_days']; ?> Hari Jadi
                                </p>
                            </div>
                        </div>

                        <!-- Dynamic Options -->
                        <?php if (!empty($optionsConfig)): ?>
                            <div class="space-y-4 pt-2">
                                <?php foreach ($optionsConfig as $optName => $optList): ?>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5 capitalize">Pilih <?= htmlspecialchars($optName); ?>:</label>
                                        <select name="options[<?= htmlspecialchars($optName); ?>]" class="price-modifier w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium" onchange="calculateLiveTotal()">
                                            <?php foreach ($optList as $item): ?>
                                                <option value="<?= htmlspecialchars($item['name']); ?>" data-extra="<?= (float)($item['price'] ?? 0); ?>">
                                                    <?= htmlspecialchars($item['name']); ?> 
                                                    <?= !empty($item['price']) ? ' (+' . formatRupiah($item['price']) . ')' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Quantity Selector -->
                        <div class="pt-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah / Kuantitas (<?= htmlspecialchars($activeProduct['unit']); ?>):</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="quantity" id="quantity" value="<?= max(1, $activeProduct['min_order']); ?>" min="<?= max(1, $activeProduct['min_order']); ?>" class="w-32 px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-black text-center focus:ring-2 focus:ring-[#00388C] focus:outline-none" oninput="calculateLiveTotal()">
                                <span class="text-xs text-slate-500 font-medium">Minimal pemesanan: <?= $activeProduct['min_order']; ?> <?= $activeProduct['unit']; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- File Upload & Notes -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-full bg-[#00388C]/10 text-[#00388C] font-black flex items-center justify-center text-xs">2</span>
                        <h2 class="text-base font-bold text-slate-900">Upload File Siap Cetak & Catatan</h2>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unggah File Desain (Opsional / Siap Cetak):</label>
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center hover:border-[#00388C] transition bg-slate-50/50">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 mx-auto mb-1.5"></i>
                            <input type="file" name="design_file" id="design_file" class="text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#00388C] hover:file:bg-blue-100 cursor-pointer">
                            <p class="text-[11px] text-slate-400 mt-2">Format: PDF, PNG, JPG, AI, PSD, ZIP (Maks. 50MB)</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Tambahan untuk Operator Mesin Cetak:</label>
                        <textarea name="notes" rows="2" placeholder="Contoh: Tolong potong pas garis batas, warna jangan terlalu gelap..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
                    </div>
                </div>

            </div>

            <!-- Right Column: Customer Info & Summary -->
            <div class="lg:col-span-5 space-y-6">
                
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-full bg-[#00388C]/10 text-[#00388C] font-black flex items-center justify-center text-xs">3</span>
                        <h2 class="text-base font-bold text-slate-900">Informasi Pemesan & Pengiriman</h2>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap / Brand: *</label>
                            <input type="text" name="customer_name" required placeholder="Contoh: Budi Santoso" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp Aktif: *</label>
                            <input type="tel" name="customer_phone" required placeholder="Contoh: 08123456789" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                            <span class="text-[10px] text-slate-400">Notifikasi status cetak & No. Struk akan dikirim ke nomor ini.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email: (Opsional)</label>
                            <input type="email" name="customer_email" placeholder="email@anda.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Opsi Pengambilan:</label>
                            <select name="delivery_type" id="delivery_type" onchange="toggleAddressField(this.value)" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                                <option value="pickup">Ambil Langsung di Workshop AN Grafika (Gratis)</option>
                                <option value="delivery">Kirim ke Alamat (Kurir Ekspedisi / Instant GoSend)</option>
                            </select>
                        </div>

                        <div id="address-container" class="hidden">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Pengiriman:</label>
                            <textarea name="delivery_address" rows="2" placeholder="Nama Jalan, No. Rumah, RT/RW, Kecamatan, Kota..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Voucher & Order Summary Card -->
                <div class="bg-gradient-to-br from-[#002052] via-[#00388C] to-[#800042] text-white p-6 rounded-3xl border border-white/20 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <h3 class="text-base font-bold">Ringkasan Biaya Cetak</h3>
                        <span class="text-xs text-pink-200 font-mono">AN Grafika</span>
                    </div>

                    <!-- Voucher Input -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-200 mb-1.5 flex items-center justify-between">
                            <span>Punya Kode Voucher Diskon?</span>
                            <span class="text-[10px] text-pink-300 font-mono">Coba: DISKON10</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="text" name="voucher_code" id="voucher_code" placeholder="KODE VOUCHER" class="uppercase w-full px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-300 text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-pink-400">
                            <button type="button" onclick="applyVoucher()" class="px-4 py-2 bg-[#C30064] hover:bg-[#a30053] text-white font-bold text-xs rounded-xl shrink-0 transition">
                                Terapkan
                            </button>
                        </div>
                        <p id="voucher-msg" class="text-[11px] mt-1.5 hidden"></p>
                    </div>

                    <!-- Price Calculations -->
                    <div class="space-y-2 text-xs text-slate-200 pt-2 border-t border-white/10">
                        <div class="flex justify-between">
                            <span>Harga Satuan:</span>
                            <span id="display-unit-price" class="font-bold text-white">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Jumlah Pesanan:</span>
                            <span id="display-qty" class="font-bold text-white">1</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span id="display-subtotal" class="font-bold text-white">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-pink-300 font-bold">
                            <span>Potongan Diskon Voucher:</span>
                            <span id="display-discount">- Rp 0</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-white/15 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-300 block">Total Pembayaran</span>
                            <span id="display-total" class="text-2xl font-black text-white">Rp 0</span>
                        </div>
                        <span class="text-[10px] px-2.5 py-1 bg-white/20 text-white border border-white/30 rounded-lg font-bold">Harga Final</span>
                    </div>

                    <button type="submit" class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-black text-sm shadow-xl shadow-black/20 transition flex items-center justify-center gap-2 border border-white/20">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span>Proses Pesanan & Buat No. Struk</span>
                    </button>
                    <p class="text-[10px] text-center text-slate-300">
                        🔒 Sistem akan menerbitkan No. Struk resmi untuk memantau proses cetak.
                    </p>
                </div>

            </div>

        </div>
    </form>
</div>

<script>
    const basePrice = <?= (float)($activeProduct['base_price'] ?? 0); ?>;
    let appliedDiscountVal = 0;

    function toggleAddressField(val) {
        const container = document.getElementById('address-container');
        if (val === 'delivery') {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    function calculateLiveTotal() {
        let currentUnitPrice = basePrice;
        const modifiers = document.querySelectorAll('.price-modifier');
        modifiers.forEach(sel => {
            const selectedOpt = sel.options[sel.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.extra) {
                currentUnitPrice += parseFloat(selectedOpt.dataset.extra);
            }
        });

        const qtyInput = document.getElementById('quantity');
        const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
        const subtotal = currentUnitPrice * qty;
        const finalTotal = Math.max(0, subtotal - appliedDiscountVal);

        document.getElementById('display-unit-price').innerText = formatRupiahJs(currentUnitPrice);
        document.getElementById('display-qty').innerText = qty;
        document.getElementById('display-subtotal').innerText = formatRupiahJs(subtotal);
        document.getElementById('display-discount').innerText = '- ' + formatRupiahJs(appliedDiscountVal);
        document.getElementById('display-total').innerText = formatRupiahJs(finalTotal);
    }

    function formatRupiahJs(amount) {
        return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    async function applyVoucher() {
        const code = document.getElementById('voucher_code').value.trim().toUpperCase();
        const msgEl = document.getElementById('voucher-msg');
        msgEl.classList.remove('hidden', 'text-emerald-300', 'text-rose-300');

        if (!code) {
            msgEl.classList.add('text-rose-300');
            msgEl.innerText = 'Silakan masukkan kode voucher terlebih dahulu.';
            appliedDiscountVal = 0;
            calculateLiveTotal();
            return;
        }

        const subtotalText = document.getElementById('display-subtotal').innerText.replace(/[^0-9]/g, '');
        const subtotal = parseInt(subtotalText) || 0;

        try {
            const resp = await fetch(`api/check_voucher.php?code=${encodeURIComponent(code)}&subtotal=${subtotal}`);
            const data = await resp.json();

            if (data.valid) {
                appliedDiscountVal = parseFloat(data.discount_amount) || 0;
                msgEl.classList.add('text-emerald-300');
                msgEl.innerText = data.message;
            } else {
                appliedDiscountVal = 0;
                msgEl.classList.add('text-rose-300');
                msgEl.innerText = data.message;
            }
        } catch (err) {
            msgEl.classList.add('text-rose-300');
            msgEl.innerText = 'Gagal memvalidasi voucher dari server.';
            appliedDiscountVal = 0;
        }

        calculateLiveTotal();
    }

    document.addEventListener('DOMContentLoaded', calculateLiveTotal);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
