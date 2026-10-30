<?php
$staffTitle = "Sistem Kasir POS";
require_once __DIR__ . '/../includes/staff_header.php';

$categories = [];
$products = [];
$activeVouchers = [];
$error = '';
$success = '';

try {
    $db = getDB();
    $categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
    $pStmt = $db->query("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.name ASC");
    $products = $pStmt->fetchAll();

    $vStmt = $db->query("SELECT * FROM vouchers WHERE status = 'active' AND end_date >= CURDATE()");
    $activeVouchers = $vStmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle POS Transaction Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_pos') {
    $customerName = cleanInput($_POST['customer_name'] ?? 'Pelanggan Kasir');
    $customerPhone = cleanInput($_POST['customer_phone'] ?? '081200000000');
    $paymentMethod = cleanInput($_POST['payment_method'] ?? 'cash');
    $paidAmount = (float)($_POST['paid_amount'] ?? 0);
    $voucherCode = strtoupper(trim(cleanInput($_POST['voucher_code'] ?? '')));
    $cartJson = $_POST['cart_data'] ?? '[]';
    $cartItems = json_decode($cartJson, true);

    if (empty($cartItems)) {
        $error = "Keranjang kasir masih kosong. Silakan pilih produk terlebih dahulu.";
    } else {
        try {
            $db = getDB();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($cartItems as $ci) {
                $subtotal += ((float)$ci['price'] * (int)$ci['qty']);
            }

            // Voucher validation
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
                    // Increment usage
                    $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = :id")->execute([':id' => $v['id']]);
                }
            }

            $totalAmount = max(0, $subtotal - $discountAmount);
            if ($paidAmount < $totalAmount && $paymentMethod === 'cash') {
                $paidAmount = $totalAmount; // Fallback
            }
            $changeAmount = max(0, $paidAmount - $totalAmount);

            $receiptNo = generateReceiptNo('AN');
            $activeBranch = 'AN Grafika';

            // Insert into orders
            $orderSql = "INSERT INTO orders (receipt_no, order_type, customer_name, customer_phone, branch, subtotal, voucher_code, discount_amount, total_amount, paid_amount, change_amount, payment_method, payment_status, production_status, delivery_type, staff_id)
                         VALUES (:receipt_no, 'pos', :name, :phone, :branch, :subtotal, :voucher, :discount, :total, :paid, :change, :pmethod, 'paid', 'printing', 'pickup', :staff_id)";
            $oStmt = $db->prepare($orderSql);
            $oStmt->execute([
                ':receipt_no' => $receiptNo,
                ':name'       => $customerName,
                ':phone'      => $customerPhone,
                ':branch'     => $activeBranch,
                ':subtotal'   => $subtotal,
                ':voucher'    => $appliedVoucher,
                ':discount'   => $discountAmount,
                ':total'      => $totalAmount,
                ':paid'       => $paidAmount,
                ':change'     => $changeAmount,
                ':pmethod'    => $paymentMethod,
                ':staff_id'   => $currentUser['id']
            ]);

            $orderId = $db->lastInsertId();

            // Insert items
            $itemSql = "INSERT INTO order_items (order_id, product_id, item_name, specifications, unit_price, quantity, subtotal)
                        VALUES (:order_id, :product_id, :item_name, :specs, :price, :qty, :subtotal)";
            $iStmt = $db->prepare($itemSql);

            foreach ($cartItems as $ci) {
                $itemSubtotal = (float)$ci['price'] * (int)$ci['qty'];
                $iStmt->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $ci['id'] ?? null,
                    ':item_name'  => $ci['name'],
                    ':specs'      => $ci['specs'] ?? 'Standar Kasir AN Grafika',
                    ':price'      => (float)$ci['price'],
                    ':qty'        => (int)$ci['qty'],
                    ':subtotal'   => $itemSubtotal
                ]);
            }

            // Redirect directly to Thermal Receipt page!
            header("Location: ../receipt.php?receipt_no=" . urlencode($receiptNo) . "&status=pos_success");
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="flex-1 flex flex-col lg:flex-row h-full overflow-hidden">
    
    <!-- LEFT PANE: Catalog & Products Grid (Flex-1) -->
    <div class="flex-1 flex flex-col p-4 sm:p-6 overflow-y-auto">
        
        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Controls -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center gap-3 mb-5">
            <div class="relative w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="pos-search" oninput="filterProducts()" placeholder="Ketik nama produk cetak (contoh: stiker, banner, brosur, kartu nama)..." class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#00388C]">
                <button type="button" id="pos-clear-search" onclick="clearPosSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-1">
                    ✕
                </button>
            </div>

            <!-- Category Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto shrink-0 pb-1 sm:pb-0">
                <button type="button" onclick="selectCategory('all')" class="cat-btn px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-sm inline-flex items-center gap-1.5" data-cat="all">
                    Semua
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" onclick="selectCategory('<?= $cat['id']; ?>')" class="cat-btn px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition bg-slate-100 text-slate-700 hover:bg-slate-200 inline-flex items-center gap-1.5" data-cat="<?= $cat['id']; ?>">
                        <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/../' . $cat['image'])): ?>
                            <img src="../<?= htmlspecialchars($cat['image']); ?>" class="w-3.5 h-3.5 rounded object-cover">
                        <?php endif; ?>
                        <span><?= htmlspecialchars($cat['name']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Cards Grid -->
        <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5 pb-6">
            <?php foreach ($products as $prod): ?>
                <div class="pos-item bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm hover:border-[#00388C] hover:shadow-md transition cursor-pointer flex flex-col justify-between group"
                     data-id="<?= $prod['id']; ?>"
                     data-name="<?= htmlspecialchars($prod['name']); ?>"
                     data-catname="<?= htmlspecialchars($prod['category_name']); ?>"
                     data-desc="<?= htmlspecialchars($prod['description'] ?? ''); ?>"
                     data-price="<?= (float)$prod['base_price']; ?>"
                     data-unit="<?= htmlspecialchars($prod['unit']); ?>"
                     data-cat="<?= $prod['category_id']; ?>"
                     onclick="addToCart(<?= $prod['id']; ?>, '<?= addslashes(htmlspecialchars($prod['name'])); ?>', <?= (float)$prod['base_price']; ?>, '<?= htmlspecialchars($prod['unit']); ?>')">
                    
                    <div>
                        <!-- Product Thumbnail Image -->
                        <div class="w-full h-24 rounded-xl bg-slate-50 border border-slate-100 mb-2.5 overflow-hidden flex items-center justify-center">
                            <?php if (!empty($prod['image']) && file_exists(__DIR__ . '/../' . $prod['image'])): ?>
                                <img src="../<?= htmlspecialchars($prod['image']); ?>" alt="<?= htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-400">
                                    <i data-lucide="package" class="w-7 h-7 stroke-1"></i>
                                    <span class="text-[9px] mt-1 font-medium">AN Grafika</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-blue-50 text-[#00388C]">
                                <?= htmlspecialchars($prod['category_name']); ?>
                            </span>
                            <span class="w-5 h-5 rounded-full bg-pink-50 group-hover:bg-[#C30064] text-[#C30064] group-hover:text-white flex items-center justify-center text-xs font-bold transition">
                                +
                            </span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-2 leading-snug group-hover:text-[#00388C] transition">
                            <?= htmlspecialchars($prod['name']); ?>
                        </h4>
                    </div>

                    <div class="pt-2.5 mt-2 border-t border-slate-100 flex items-baseline justify-between">
                        <span class="text-xs sm:text-sm font-black text-[#00388C]">
                            <?= formatRupiah($prod['base_price']); ?>
                        </span>
                        <span class="text-[10px] text-slate-400">/ <?= htmlspecialchars($prod['unit']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- RIGHT PANE: Cart & Checkout (W-96 / 420px fixed) -->
    <div class="w-full lg:w-[420px] bg-white border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col justify-between shadow-xl z-20">
        <!-- Cart Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-pink-50 text-[#C30064] flex items-center justify-center">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
                <h3 class="font-bold text-sm text-slate-900">Pesanan Kasir Aktif</h3>
            </div>
            <button type="button" onclick="clearCart()" class="text-[11px] font-bold text-rose-600 hover:underline">
                Kosongkan
            </button>
        </div>

        <!-- Cart Items Scrollable List -->
        <div id="cart-list" class="flex-1 p-4 overflow-y-auto space-y-3 min-h-[160px] max-h-[35vh] lg:max-h-none">
            <!-- Dynamic Cart Items via JS -->
            <div id="empty-cart-msg" class="text-center py-12 text-slate-400">
                <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-2 stroke-1"></i>
                <p class="text-xs font-medium">Keranjang masih kosong</p>
                <p class="text-[11px] text-slate-400">Klik produk di sebelah kiri untuk menambahkan</p>
            </div>
        </div>

        <!-- Checkout Controls & Calculation Form -->
        <form method="POST" id="pos-checkout-form" class="p-4 border-t border-slate-200 bg-slate-50/70 space-y-3.5">
            <input type="hidden" name="action" value="process_pos">
            <input type="hidden" name="cart_data" id="cart_data_input" value="[]">

            <!-- Customer & WhatsApp -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-0.5">Nama Pelanggan:</label> 
                    <input type="text" name="customer_name" id="pos_customer_name" required value="Walk-in Customer" placeholder="Nama Pelanggan" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-0.5">No. WhatsApp:</label>
                    <input type="tel" name="customer_phone" id="pos_customer_phone" required value="081200000000" placeholder="08xxxxxxxxxx" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                </div>
            </div>

            <!-- Voucher Code Input (Tersambung Realtime Database) -->
            <div>
                <label class="block font-semibold text-slate-700 text-xs mb-0.5 flex justify-between">
                    <span>Kode Voucher Diskon:</span>
                    <span class="text-[10px] text-[#C30064] font-bold">Realtime Database</span>
                </label>
                <div class="flex items-center gap-1.5">
                    <input type="text" name="voucher_code" id="pos_voucher_code" placeholder="KODE VOUCHER" class="uppercase w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                    <button type="button" onclick="checkPosVoucher()" class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-slate-800 shrink-0 flex items-center gap-1 transition">
                        <i data-lucide="tag" class="w-3.5 h-3.5 text-pink-300"></i>
                        <span>Cek</span>
                    </button>
                </div>
                <p id="pos-voucher-msg" class="text-[11px] mt-1 hidden font-medium"></p>
            </div>

            <!-- Calculation Summary -->
            <div class="space-y-1 text-xs text-slate-600 pt-2 border-t border-slate-200">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span id="pos-subtotal" class="font-bold text-slate-900">Rp 0</span>
                </div>
                <div class="flex justify-between text-[#C30064] font-semibold">
                    <span>Diskon Voucher:</span>
                    <span id="pos-discount">- Rp 0</span>
                </div>
                <div class="flex justify-between text-sm font-black text-slate-900 pt-1 border-t border-slate-200">
                    <span>Total Tagihan:</span>
                    <span id="pos-total" class="text-base font-black text-[#00388C]">Rp 0</span>
                </div>
            </div>

            <!-- Payment Method & Cash Received -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-0.5">Metode Bayar:</label>
                    <select name="payment_method" id="pos-pmethod" onchange="toggleCashInput(this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        <option value="cash" selected>Tunai (Cash)</option>
                        <option value="qris">QRIS Instan</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="debit">Kartu Debit</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-0.5">Uang Diterima (Rp):</label>
                    <input type="number" name="paid_amount" id="pos-paid-amount" value="0" min="0" oninput="calculateChange()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
            </div>

            <!-- Quick Cash Buttons -->
            <div class="flex items-center gap-1 text-[10px]">
                <button type="button" onclick="setCashExact()" class="flex-1 py-1 bg-slate-200 hover:bg-slate-300 rounded font-bold text-slate-700">Pas</button>
                <button type="button" onclick="setCash(50000)" class="flex-1 py-1 bg-slate-200 hover:bg-slate-300 rounded font-bold text-slate-700">50rb</button>
                <button type="button" onclick="setCash(100000)" class="flex-1 py-1 bg-slate-200 hover:bg-slate-300 rounded font-bold text-slate-700">100rb</button>
                <button type="button" onclick="setCash(200000)" class="flex-1 py-1 bg-slate-200 hover:bg-slate-300 rounded font-bold text-slate-700">200rb</button>
            </div>

            <!-- Kembalian Display -->
            <div class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-between">
                <span class="text-xs font-semibold text-[#00388C]">Kembalian:</span>
                <span id="pos-change" class="text-sm font-black text-[#00388C]">Rp 0</span>
            </div>

            <!-- Submit Button: Print Struk -->
            <button type="submit" id="pos-submit-btn" disabled class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-20 disabled:pointer-events-none text-white font-black text-sm shadow-lg shadow-pink-500/20 transition flex items-center justify-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>BAYAR & CETAK STRUK</span>
            </button>
        </form>

    </div>

</div>

<script>
    let cart = [];
    let appliedDiscount = 0;

    function addToCart(id, name, price, unit) {
        const existing = cart.find(item => item.id === id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                unit: unit,
                qty: 1,
                specs: 'Standar Bahan & Finishing'
            });
        }
        renderCart();
    }

    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += delta;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.id !== id);
            }
        }
        renderCart();
    }

    function removeItem(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    function clearCart() {
        cart = [];
        appliedDiscount = 0;
        currentActiveVoucherCode = '';
        document.getElementById('pos_voucher_code').value = '';
        document.getElementById('pos-voucher-msg').classList.add('hidden');
        renderCart();
    }

    function renderCart() {
        const listEl = document.getElementById('cart-list');
        const emptyMsg = document.getElementById('empty-cart-msg');
        const submitBtn = document.getElementById('pos-submit-btn');

        if (cart.length === 0) {
            listEl.innerHTML = '';
            listEl.appendChild(emptyMsg);
            submitBtn.disabled = true;
            updateTotals(0);
            return;
        }

        submitBtn.disabled = false;
        let html = '';
        let subtotal = 0;

        cart.forEach(item => {
            const itemSubtotal = item.price * item.qty;
            subtotal += itemSubtotal;
            html += `
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-start justify-between gap-2">
                    <div class="flex-1">
                        <p class="font-bold text-xs text-slate-900 leading-snug">${item.name}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">${formatRupiahJs(item.price)} / ${item.unit}</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <button type="button" onclick="updateQty(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs">-</button>
                            <span class="w-8 text-center text-xs font-bold text-slate-900">${item.qty}</span>
                            <button type="button" onclick="updateQty(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs">+</button>
                        </div>
                    </div>
                    <div class="text-right flex flex-col justify-between h-full">
                        <button type="button" onclick="removeItem(${item.id})" class="text-slate-400 hover:text-rose-600 text-xs self-end">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                        <span class="text-xs font-black text-slate-900 mt-2">${formatRupiahJs(itemSubtotal)}</span>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;
        document.getElementById('cart_data_input').value = JSON.stringify(cart);
        updateTotals(subtotal);
        lucide.createIcons();
    }

    function updateTotals(subtotal) {
        const finalTotal = Math.max(0, subtotal - appliedDiscount);

        document.getElementById('pos-subtotal').innerText = formatRupiahJs(subtotal);
        document.getElementById('pos-discount').innerText = '- ' + formatRupiahJs(appliedDiscount);
        document.getElementById('pos-total').innerText = formatRupiahJs(finalTotal);

        const paidInput = document.getElementById('pos-paid-amount');
        if (parseFloat(paidInput.value) <= 0 || parseFloat(paidInput.value) < finalTotal) {
            paidInput.value = finalTotal;
        }
        calculateChange();
    }

    function calculateChange() {
        const totalText = document.getElementById('pos-total').innerText.replace(/[^0-9]/g, '');
        const total = parseFloat(totalText) || 0;
        const paid = parseFloat(document.getElementById('pos-paid-amount').value) || 0;
        const change = Math.max(0, paid - total);
        document.getElementById('pos-change').innerText = formatRupiahJs(change);
    }

    function setCashExact() {
        const totalText = document.getElementById('pos-total').innerText.replace(/[^0-9]/g, '');
        document.getElementById('pos-paid-amount').value = parseFloat(totalText) || 0;
        calculateChange();
    }

    function setCash(amt) {
        document.getElementById('pos-paid-amount').value = amt;
        calculateChange();
    }

    function toggleCashInput(method) {
        const pInput = document.getElementById('pos-paid-amount');
        if (method !== 'cash') {
            setCashExact();
        }
    }

    let currentActiveVoucherCode = '';

    async function checkPosVoucher(silent = false) {
        const inputEl = document.getElementById('pos_voucher_code');
        const code = (inputEl.value || '').trim().toUpperCase();
        const msgEl = document.getElementById('pos-voucher-msg');

        const subtotalText = document.getElementById('pos-subtotal').innerText.replace(/[^0-9]/g, '');
        const subtotal = parseFloat(subtotalText) || 0;

        if (!code) {
            if (!silent) {
                msgEl.classList.remove('hidden');
                msgEl.className = 'text-[11px] mt-1 text-rose-600 font-semibold';
                msgEl.innerText = 'Masukkan kode voucher terlebih dahulu.';
            }
            appliedDiscount = 0;
            currentActiveVoucherCode = '';
            updateTotals(subtotal);
            return;
        }

        if (subtotal <= 0) {
            if (!silent) {
                msgEl.classList.remove('hidden');
                msgEl.className = 'text-[11px] mt-1 text-rose-600 font-semibold';
                msgEl.innerText = 'Keranjang masih kosong. Tambahkan produk sebelum mengecek voucher.';
            }
            return;
        }

        try {
            const resp = await fetch(`../api/check_voucher.php?code=${encodeURIComponent(code)}&subtotal=${subtotal}`);
            const data = await resp.json();

            msgEl.classList.remove('hidden');
            if (data.valid) {
                appliedDiscount = parseFloat(data.discount_amount) || 0;
                currentActiveVoucherCode = data.code;
                msgEl.className = 'text-[11px] mt-1 text-emerald-600 font-bold';
                msgEl.innerText = data.message;
            } else {
                appliedDiscount = 0;
                currentActiveVoucherCode = '';
                msgEl.className = 'text-[11px] mt-1 text-rose-600 font-semibold';
                msgEl.innerText = data.message;
            }
        } catch (err) {
            if (!silent) {
                msgEl.classList.remove('hidden');
                msgEl.className = 'text-[11px] mt-1 text-rose-600 font-semibold';
                msgEl.innerText = 'Gagal menghubungi server untuk memvalidasi voucher.';
            }
        }

        updateTotals(subtotal);
    }

    function formatRupiahJs(amount) {
        return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Dynamic Filter Products with Category & Search Keywords
    let activeCat = 'all';

    function filterProducts() {
        const q = (document.getElementById('pos-search').value || '').toLowerCase().trim();
        const clearBtn = document.getElementById('pos-clear-search');
        if (q.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
        applyProductFilters(q, activeCat);
    }

    function clearPosSearch() {
        document.getElementById('pos-search').value = '';
        document.getElementById('pos-clear-search').classList.add('hidden');
        applyProductFilters('', activeCat);
    }

    function selectCategory(catId) {
        activeCat = String(catId);
        document.querySelectorAll('.cat-btn').forEach(btn => {
            if (btn.dataset.cat === activeCat) {
                btn.className = 'cat-btn px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition bg-gradient-to-r from-[#00388C] to-[#C30064] text-white shadow-sm inline-flex items-center gap-1.5';
            } else {
                btn.className = 'cat-btn px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition bg-slate-100 text-slate-700 hover:bg-slate-200 inline-flex items-center gap-1.5';
            }
        });
        const q = (document.getElementById('pos-search').value || '').toLowerCase().trim();
        applyProductFilters(q, activeCat);
    }

    function applyProductFilters(q, catId) {
        const items = document.querySelectorAll('.pos-item');
        let visibleCount = 0;

        items.forEach(el => {
            const name = (el.dataset.name || '').toLowerCase();
            const catName = (el.dataset.catname || '').toLowerCase();
            const desc = (el.dataset.desc || '').toLowerCase();
            const itemCat = String(el.dataset.cat || '');

            const matchCat = (catId === 'all' || itemCat === catId);
            const matchQuery = (q === '' || name.includes(q) || catName.includes(q) || desc.includes(q));

            if (matchCat && matchQuery) {
                el.classList.remove('hidden');
                visibleCount++;
            } else {
                el.classList.add('hidden');
            }
        });

        let noRes = document.getElementById('pos-empty-filter');
        if (visibleCount === 0) {
            if (!noRes) {
                const grid = document.getElementById('product-grid');
                noRes = document.createElement('div');
                noRes.id = 'pos-empty-filter';
                noRes.className = 'col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200';
                noRes.innerHTML = `
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                        <i data-lucide="package-search" class="w-6 h-6"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-800">Produk Tidak Ditemukan</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada produk cetak yang cocok dengan kata kunci "${q}".</p>
                `;
                grid.appendChild(noRes);
                if (window.lucide) lucide.createIcons();
            }
            noRes.classList.remove('hidden');
        } else if (noRes) {
            noRes.classList.add('hidden');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
