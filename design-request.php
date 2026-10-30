<?php
$pageTitle = "Studio Jasa Desain Grafis & Logo - AN Grafika";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_design'])) {
    $customerName = cleanInput($_POST['customer_name'] ?? '');
    $customerPhone = cleanInput($_POST['customer_phone'] ?? '');
    $customerEmail = cleanInput($_POST['customer_email'] ?? '');
    $designType = cleanInput($_POST['design_type'] ?? 'Desain Logo UMKM');
    $brandName = cleanInput($_POST['brand_name'] ?? '');
    $tagline = cleanInput($_POST['tagline'] ?? '');
    $preferredColors = cleanInput($_POST['preferred_colors'] ?? '');
    $designStyle = cleanInput($_POST['design_style'] ?? 'Minimalis Modern');
    $designBrief = cleanInput($_POST['design_brief'] ?? '');
    $packageType = cleanInput($_POST['package_type'] ?? 'Standar');

    $prices = [
        'Basic'    => 75000.00,
        'Standar'  => 150000.00,
        'Premium'  => 250000.00,
    ];
    $estPrice = $prices[$packageType] ?? 150000.00;

    if (empty($customerName) || empty($customerPhone) || empty($brandName) || empty($designBrief)) {
        $error = "Mohon lengkapi Nama Anda, No. WhatsApp, Nama Brand/Toko, dan Brief Kebutuhan Desain.";
    } else {
        try {
            $db = getDB();

            $refFilePath = null;
            if (isset($_FILES['reference_file']) && $_FILES['reference_file']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handleFileUpload('reference_file', 'designs');
                if (isset($uploadRes['success'])) {
                    $refFilePath = $uploadRes['file_path'];
                } elseif (isset($uploadRes['error'])) {
                    throw new Exception($uploadRes['error']);
                }
            }

            $ticketNo = generateTicketNo('DSG');
            $receiptNo = generateReceiptNo('AN');

            // 1. Insert into orders table
            $orderSql = "INSERT INTO orders (receipt_no, order_type, customer_name, customer_phone, customer_email, subtotal, discount_amount, total_amount, paid_amount, payment_method, payment_status, production_status, notes)
                         VALUES (:receipt_no, 'design', :name, :phone, :email, :subtotal, 0, :total, :paid, 'transfer', 'paid', 'verified', :notes)";
            $orderStmt = $db->prepare($orderSql);
            $orderStmt->execute([
                ':receipt_no' => $receiptNo,
                ':name'       => $customerName,
                ':phone'      => $customerPhone,
                ':email'      => $customerEmail,
                ':subtotal'   => $estPrice,
                ':total'      => $estPrice,
                ':paid'       => $estPrice,
                ':notes'      => "Jasa Desain: {$designType} ({$brandName}) - Tiket: {$ticketNo}"
            ]);

            $orderId = $db->lastInsertId();

            // 2. Insert into order_items
            $itemSql = "INSERT INTO order_items (order_id, item_name, specifications, unit_price, quantity, subtotal)
                        VALUES (:order_id, :item_name, :specs, :price, 1, :subtotal)";
            $db->prepare($itemSql)->execute([
                ':order_id'  => $orderId,
                ':item_name' => "Jasa {$designType} - {$brandName}",
                ':specs'     => "Paket: {$packageType} | Konsep: {$designStyle} | Warna: {$preferredColors}",
                ':price'     => $estPrice,
                ':subtotal'  => $estPrice
            ]);

            // 3. Insert into design_requests
            $dsgSql = "INSERT INTO design_requests (ticket_no, receipt_no, customer_name, customer_phone, customer_email, design_type, brand_name, tagline, preferred_colors, design_style, design_brief, reference_file, package_type, estimated_price, status)
                       VALUES (:ticket, :receipt, :name, :phone, :email, :type, :brand, :tagline, :colors, :style, :brief, :ref, :pkg, :price, 'submitted')";
            $dsgStmt = $db->prepare($dsgSql);
            $dsgStmt->execute([
                ':ticket'  => $ticketNo,
                ':receipt' => $receiptNo,
                ':name'    => $customerName,
                ':phone'   => $customerPhone,
                ':email'   => $customerEmail,
                ':type'    => $designType,
                ':brand'   => $brandName,
                ':tagline' => $tagline,
                ':colors'  => $preferredColors,
                ':style'   => $designStyle,
                ':brief'   => $designBrief,
                ':ref'     => $refFilePath,
                ':pkg'     => $packageType,
                ':price'   => $estPrice
            ]);

            header("Location: receipt.php?receipt_no=" . urlencode($receiptNo) . "&ticket_no=" . urlencode($ticketNo) . "&status=design_new");
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!-- Header -->
<div class="bg-gradient-to-r from-[#002052] via-[#00388C] to-[#8c0047] text-white py-12 border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-pink-200 text-xs font-semibold mb-2">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#C30064]"></i>
                    <span>In-House Graphic Design Studio AN Grafika</span>
                </div>
                <h1 class="text-3xl font-black tracking-tight">Studio Penerimaan Request Desain (Logo dll)</h1>
                <p class="text-slate-200 text-xs sm:text-sm mt-1 max-w-2xl leading-relaxed">
                    Belum punya logo atau file siap cetak? Serahkan pada tim desainer profesional AN Grafika. Kami melayani pembuatan desain logo usaha, kemasan produk, spanduk, hingga branding lengkap dengan file vector master resolusi tinggi.
                </p>
            </div>
            <div class="bg-white/10 backdrop-blur p-4 rounded-2xl border border-white/20 text-xs text-pink-100">
                <p class="font-bold text-white flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    Garansi Revisi & File Master
                </p>
                <p class="mt-1 text-[11px] text-slate-200">Format file siap cetak & digital: AI, PDF Vector, PNG Transparan, JPG HD.</p>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0"></i>
            <span><?= htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="submit_design" value="1">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Form Questionnaire -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Step 1: Package & Design Category -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-full bg-[#00388C]/10 text-[#00388C] font-black flex items-center justify-center text-xs">1</span>
                        <h2 class="text-base font-bold text-slate-900">Pilih Jenis Desain & Paket Layanan</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Desain yang Dibutuhkan: *</label>
                            <select name="design_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                                <option value="Desain Logo UMKM / Toko">Desain Logo UMKM / Toko / Brand</option>
                                <option value="Desain Kemasan & Label Box">Desain Kemasan & Label Box Makanan</option>
                                <option value="Desain Spanduk & Banner Promo">Desain Spanduk, Baliho & Banner Promo</option>
                                <option value="Desain Brosur & Menu Makanan">Desain Brosur, Flyer & Daftar Menu</option>
                                <option value="Desain Kartu Nama & Kop Surat">Desain Kartu Nama & Perlengkapan Kantor</option>
                                <option value="Desain Kaos & Merchandise">Desain Kaos, Sablon Mug & Merchandise</option>
                                <option value="Desain Kustom Lainnya">Desain Kustom Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Paket Desain: *</label>
                            <select name="package_type" id="package_type" onchange="updatePackagePreview(this.value)" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-[#C30064]">
                                <option value="Basic">Paket Basic (1 Konsep, 1x Revisi) — Rp 75.000</option>
                                <option value="Standar" selected>Paket Standar (2 Konsep, 3x Revisi, File Cetak) — Rp 150.000</option>
                                <option value="Premium">Paket Premium (3 Konsep, Fleksibel, File Master AI/Vector) — Rp 250.000</option>
                            </select>
                        </div>
                    </div>

                    <!-- Package Info Banner -->
                    <div id="package-info-box" class="p-4 rounded-xl bg-pink-50/70 border border-pink-200/60 text-xs text-slate-800">
                        <p class="font-bold flex items-center gap-1.5 text-[#C30064]">
                            <i data-lucide="check-circle" class="w-4 h-4 text-[#C30064]"></i>
                            <span id="pkg-title">Paket Standar — Rp 150.000</span>
                        </p>
                        <p id="pkg-desc" class="text-slate-600 mt-1">
                            Paling banyak dipilih: Dibuatkan 2 alternatif konsep desain awal, 3x kesempatan revisi, serta file master beresolusi tinggi (PDF Siap Cetak & PNG Transparan).
                        </p>
                    </div>
                </div>

                <!-- Step 2: Brand Identity Brief -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-full bg-[#00388C]/10 text-[#00388C] font-black flex items-center justify-center text-xs">2</span>
                        <h2 class="text-base font-bold text-slate-900">Detail Brief & Konsep Desain</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Brand / Tulisan di Desain: *</label>
                            <input type="text" name="brand_name" required placeholder="Contoh: Dapur Kopi Nusantara" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Slogan / Tagline (Jika ada):</label>
                            <input type="text" name="tagline" placeholder="Contoh: Nikmat Tradisi Setiap Hari" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Gaya Desain yang Diinginkan:</label>
                            <select name="design_style" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                                <option value="Minimalis & Elegan Modern">Minimalis & Elegan Modern</option>
                                <option value="Vintage / Retro Klasik">Vintage / Retro Klasik</option>
                                <option value="Ceria / Playful / Friendly">Ceria / Playful / Friendly (Warna-warni)</option>
                                <option value="Mewah / Luxury Gold">Mewah / Luxury Gold & Navy</option>
                                <option value="Karakter / Maskot Unik">Karakter / Maskot Unik</option>
                                <option value="Terserah Desainer (Kreatif)">Terserah Desainer AN Grafika</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Preferensi Warna Dominan:</label>
                            <input type="text" name="preferred_colors" placeholder="Contoh: Navy Blue, Magenta, Monokrom, Emas..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jelaskan Kebutuhan / Ide Desain Anda: *</label>
                        <textarea name="design_brief" required rows="4" placeholder="Ceritakan tentang usaha Anda, target audiens, elemen apa saja yang wajib dimasukkan, dan gambaran umum yang Anda bayangkan..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Contoh Referensi / Sketsa yang Anda Sukai (Opsional):</label>
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center hover:border-[#00388C] transition bg-slate-50/50">
                            <i data-lucide="image" class="w-8 h-8 text-slate-400 mx-auto mb-1.5"></i>
                            <input type="file" name="reference_file" class="text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-[#C30064] hover:file:bg-pink-100 cursor-pointer">
                            <p class="text-[11px] text-slate-400 mt-2">Lampirkan foto sketsa coretan kertas, tangkapan layar contoh logo/warna yang disukai (PNG, JPG, PDF).</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Customer Contact -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-full bg-[#00388C]/10 text-[#00388C] font-black flex items-center justify-center text-xs">3</span>
                        <h2 class="text-base font-bold text-slate-900">Kontak Pemesan untuk Koordinasi Desain</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Anda: *</label>
                            <input type="text" name="customer_name" required placeholder="Contoh: Rina Melati" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp Aktif: *</label>
                            <input type="tel" name="customer_phone" required placeholder="Contoh: 08123456789" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                            <span class="text-[10px] text-slate-400">Desainer AN Grafika akan mengabari dan mengirimkan pratinjau draft ke nomor ini.</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email: (Opsional)</label>
                        <input type="email" name="customer_email" placeholder="email@perusahaan.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                    </div>
                </div>

            </div>

            <!-- Right Column: Summary Card -->
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-gradient-to-br from-[#002052] via-[#00388C] to-[#800042] text-white p-6 rounded-3xl border border-white/20 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <h3 class="text-base font-bold">Ringkasan Biaya Desain</h3>
                        <span class="text-xs text-pink-200">AN Grafika Studio</span>
                    </div>

                    <div class="space-y-3 text-xs text-slate-200">
                        <div class="flex justify-between">
                            <span>Layanan:</span>
                            <span class="font-bold text-white">Jasa Desain Profesional</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pilihan Paket:</span>
                            <span id="summary-pkg-name" class="font-bold text-pink-300">Paket Standar</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Format File Final:</span>
                            <span class="font-bold text-emerald-400">PDF Ready + PNG HD</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-white/15 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-300 block">Total Biaya Desain</span>
                            <span id="summary-pkg-price" class="text-2xl font-black text-white">Rp 150.000</span>
                        </div>
                        <span class="text-[10px] px-2 py-1 bg-white/20 text-white border border-white/30 rounded-lg font-bold">All-In</span>
                    </div>

                    <button type="submit" class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-black text-sm shadow-xl shadow-black/20 transition flex items-center justify-center gap-2 border border-white/20">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim Permintaan & Dapatkan Tiket</span>
                    </button>

                    <div class="p-3 bg-white/10 rounded-xl border border-white/10 text-[11px] text-slate-200 space-y-1">
                        <p class="font-bold text-white flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-pink-300"></i>
                            Alur Setelah Kirim Brief:
                        </p>
                        <p>1. Terbit Nomor Tiket & No. Struk Pelacakan.</p>
                        <p>2. Tim desainer mengonfirmasi brief melalui WhatsApp.</p>
                        <p>3. Draft pratinjau dikirimkan untuk evaluasi Anda.</p>
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>

<script>
    const packageData = {
        'Basic': {
            title: 'Paket Basic — Rp 75.000',
            price: 'Rp 75.000',
            desc: 'Cocok untuk kebutuhan simpel: 1 konsep desain logo/banner, 1x kesempatan revisi, file final JPG & PNG HD.'
        },
        'Standar': {
            title: 'Paket Standar — Rp 150.000',
            price: 'Rp 150.000',
            desc: 'Paling banyak dipilih: Dibuatkan 2 alternatif konsep desain awal, 3x kesempatan revisi, serta file master beresolusi tinggi (PDF Siap Cetak & PNG Transparan).'
        },
        'Premium': {
            title: 'Paket Premium — Rp 250.000',
            price: 'Rp 250.000',
            desc: 'Solusi lengkap brand identity: 3 konsep desain eksklusif, revisi fleksibel, plus File Master Vector mentahan (AI, CDR, PDF, Fonts).'
        }
    };

    function updatePackagePreview(pkg) {
        const data = packageData[pkg] || packageData['Standar'];
        document.getElementById('pkg-title').innerText = data.title;
        document.getElementById('pkg-desc').innerText = data.desc;
        document.getElementById('summary-pkg-name').innerText = 'Paket ' + pkg;
        document.getElementById('summary-pkg-price').innerText = data.price;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
