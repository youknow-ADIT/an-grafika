<?php
$pageTitle = "AN Grafika - Digital Printing & Studio Desain Profesional";
require_once __DIR__ . '/includes/header.php';

$categories = [];
$topProducts = [];
$activeVouchers = [];

try {
    $db = getDB();
    $catStmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $catStmt->fetchAll();

    $prodStmt = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.id ASC LIMIT 6");
    $topProducts = $prodStmt->fetchAll();

    $vouchStmt = $db->query("SELECT * FROM vouchers WHERE status = 'active' AND end_date >= CURDATE() LIMIT 3");
    $activeVouchers = $vouchStmt->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<!-- Hero Section (Harmonis Navy Blue & Magenta) -->
<section class="relative bg-gradient-to-br from-[#002052] via-[#00388C] to-[#800042] text-white overflow-hidden py-16 lg:py-24">
    <!-- Decorative background glow -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-[#0055d4]/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 right-10 w-96 h-96 bg-[#C30064]/25 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 text-pink-200 text-xs font-semibold backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-[#C30064] animate-ping"></span>
                    <span>Digital & Offset Printing &bull; Studio Desain Kreatif</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight text-white">
                    Cetak Cepat Presisi Tinggi & <br class="hidden sm:inline">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 via-pink-200 to-rose-300">
                        Jasa Desain Murah
                    </span>
                </h1>

                <p class="text-slate-200 text-xs sm:text-base leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Solusi lengkap kebutuhan branding dan promosi bisnis Anda: cetak dokumen, stiker label kemasan waterproof, spanduk, merchandise, hingga pembuatan desain logo dan packaging kustom era modern.
                </p>

                <!-- Quick Track Struk Form in Hero -->
                <div class="bg-white/10 backdrop-blur-md p-2.5 sm:p-3 rounded-2xl border border-white/20 max-w-xl mx-auto lg:mx-0 shadow-2xl">
                    <form action="track.php" method="GET" class="flex flex-col sm:flex-row items-center gap-2">
                        <div class="relative w-full">
                            <i data-lucide="receipt" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="receipt_no" placeholder="Lacak No. Struk (contoh: AN-20261030-0045)..." required class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white text-slate-900 placeholder-slate-400 text-xs sm:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-pink-400">
                        </div>
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-bold text-xs sm:text-sm rounded-xl shrink-0 transition flex items-center justify-center gap-2 shadow-lg shadow-black/20">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            <span>Lacak Pesanan</span>
                        </button>
                    </form>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                    <a href="products.php" class="px-6 py-3.5 rounded-xl bg-white hover:bg-slate-100 text-[#00388C] font-extrabold text-xs sm:text-sm shadow-xl transition flex items-center gap-2">
                        <i data-lucide="grid" class="w-4 h-4 text-[#00388C]"></i>
                        <span>Lihat Katalog Produk & Harga</span>
                    </a>
                    <a href="design-request.php" class="px-6 py-3.5 rounded-xl bg-[#C30064] hover:bg-[#a30053] text-white font-extrabold text-xs sm:text-sm shadow-xl transition flex items-center gap-2">
                        <i data-lucide="palette" class="w-4 h-4"></i>
                        <span>Request Jasa Desain (Logo dll)</span>
                    </a>
                </div>

                <!-- Key stats -->
                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-white/10 text-center lg:text-left">
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-white">1 Hari</p>
                        <p class="text-[11px] text-blue-200">Cetak Kilat Cepat</p>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-white">1000+</p>
                        <p class="text-[11px] text-pink-200">Customer</p>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-white">24/7</p>
                        <p class="text-[11px] text-slate-300">Lacak Struk Online</p>
                    </div>
                </div>
            </div>

            <!-- Right Hero Card / Visual Preview -->
            <div class="lg:col-span-5">
                <div class="bg-gradient-to-b from-white/15 to-white/5 backdrop-blur-xl p-6 sm:p-7 rounded-3xl border border-white/20 shadow-2xl relative">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-xs text-white/90 font-mono ml-2">angrafika.id</span>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded bg-pink-500/20 text-pink-200 border border-pink-400/30 font-bold">Fast Respon</span>
                    </div>

                    <!-- Hero Visual Branding -->
                    <div class="text-center py-5">
                        <div class="w-24 h-24 mx-auto bg-white rounded-2xl p-2.5 shadow-2xl flex items-center justify-center">
                            <img src="assets/img/logo.png" alt="AN Grafika" class="w-full h-full object-contain">
                        </div>
                        <h3 class="text-xl font-black text-white mt-3">AN Grafika Studio</h3>
                        <p class="text-xs text-pink-200">Percetakan Digital Berkualitas & Desain Kustom</p>
                    </div>

                    <!-- Highlight Badges -->
                    <div class="space-y-2.5 mt-2">
                        <div class="bg-white/10 p-3 rounded-xl border border-white/10 flex items-center justify-between">
                            <span class="text-xs font-bold text-white flex items-center gap-2">
                                <span>🖨️</span> Cetak Kartu Nama, Brosur, Stiker
                            </span>
                            <span class="text-xs font-bold text-blue-200">Mulai Rp 750</span>
                        </div>
                        <div class="bg-white/10 p-3 rounded-xl border border-white/10 flex items-center justify-between">
                            <span class="text-xs font-bold text-white flex items-center gap-2">
                                <span>🎨</span> Jasa Desain Logo & Kemasan Box
                            </span>
                            <a href="design-request.php" class="text-xs font-bold text-pink-300 hover:text-white underline">Pesan &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Active Voucher Ticker -->
<?php if (!empty($activeVouchers)): ?>
<section class="bg-gradient-to-r from-[#00388C] via-[#63004a] to-[#C30064] py-4 text-white shadow-inner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs sm:text-sm font-bold text-center md:text-left">
                <i data-lucide="ticket" class="w-5 h-5 text-yellow-300"></i>
                <span>Klaim Voucher Promo AN Grafika Hari Ini:</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <?php foreach ($activeVouchers as $v): ?>
                    <span class="bg-white/15 backdrop-blur px-3 py-1 rounded-xl text-xs font-mono font-bold border border-white/20">
                        <span class="text-yellow-300"><?= htmlspecialchars($v['code']); ?></span>
                        <span class="text-white/80 font-sans font-normal text-[11px] ml-1">(Potongan <?= $v['discount_type'] === 'percent' ? $v['discount_value'] . '%' : formatRupiah($v['discount_value']); ?>)</span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Category Navigation Grid -->
<section class="py-14 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Kategori Produk Percetakan</h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-2">Pilih kategori produk cetak untuk melihat spesifikasi bahan, ukuran, dan simulasi harga.</p>
            
            <!-- Search Box to Catalog -->
            <form action="products.php" method="GET" class="mt-5 max-w-md mx-auto flex items-center gap-2">
                <div class="relative w-full">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" placeholder="Cari produk cetak (stiker, banner, kartu nama, brosur)..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none shadow-sm font-medium">
                </div>
                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-bold text-xs rounded-xl shadow-md shrink-0 transition flex items-center gap-1">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Cari</span>
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="products.php?cat=<?= htmlspecialchars($cat['slug']); ?>" class="group p-5 rounded-2xl bg-slate-50 hover:bg-blue-50/60 border border-slate-200 hover:border-[#00388C]/40 transition duration-200 text-center flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-white group-hover:bg-[#00388C] group-hover:text-white text-[#00388C] flex items-center justify-center shadow-sm border border-slate-200 group-hover:border-[#00388C] transition duration-200 mb-3 overflow-hidden">
                        <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/' . $cat['image'])): ?>
                            <img src="<?= htmlspecialchars($cat['image']); ?>" alt="<?= htmlspecialchars($cat['name']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i data-lucide="<?= htmlspecialchars($cat['icon']); ?>" class="w-8 h-8"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-bold text-sm text-slate-900 group-hover:text-[#00388C] transition"><?= htmlspecialchars($cat['name']); ?></h3>
                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($cat['description']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products with Pricing & Images -->
<section class="py-14 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
            <div>
                <span class="text-xs font-bold text-[#C30064] uppercase tracking-wider">Katalog Unggulan</span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">Produk Cetak Terpopuler</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Harga transparan, bahan berkualitas tinggi, dan pengerjaan cepat siap kirim.</p>
            </div>
            <a href="products.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#00388C] hover:text-[#C30064] bg-white px-4 py-2.5 rounded-xl border border-slate-200 transition">
                <span>Lihat Semua Produk</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($topProducts as $prod): ?>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col justify-between group">
                    <div>
                        <!-- Product Photo / Graphic Header -->
                        <div class="h-44 bg-slate-100 relative overflow-hidden flex items-center justify-center">
                            <?php if (!empty($prod['image']) && file_exists(__DIR__ . '/' . $prod['image'])): ?>
                                <img src="<?= htmlspecialchars($prod['image']); ?>" alt="<?= htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-2xl bg-white shadow-sm text-[#00388C] flex items-center justify-center">
                                    <i data-lucide="printer" class="w-7 h-7"></i>
                                </div>
                            <?php endif; ?>
                            <span class="absolute top-3 left-3 bg-white/95 backdrop-blur px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-700 border border-slate-200">
                                <?= htmlspecialchars($prod['category_name']); ?>
                            </span>
                            <span class="absolute bottom-3 right-3 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-[10px] font-semibold">
                                Estimasi <?= $prod['estimated_days']; ?> Hari
                            </span>
                        </div>

                        <div class="p-5">
                            <h3 class="font-bold text-slate-900 text-base leading-snug hover:text-[#00388C] transition">
                                <a href="order.php?product_id=<?= $prod['id']; ?>"><?= htmlspecialchars($prod['name']); ?></a>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($prod['description']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-5 pt-0">
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 block">Harga Mulai</span>
                                <span class="text-base font-black text-[#00388C]">
                                    <?= formatRupiah($prod['base_price']); ?>
                                </span>
                                <span class="text-[11px] text-slate-500">/ <?= htmlspecialchars($prod['unit']); ?></span>
                            </div>

                            <a href="order.php?product_id=<?= $prod['id']; ?>" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 group-hover:bg-[#00388C] transition">
                                <span>Pesan</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Studio Desain (Logo, Branding, Packaging) -->
<section class="py-16 bg-gradient-to-br from-[#001b44] via-[#00388C] to-[#8c0047] text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-6 space-y-5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-pink-200 border border-white/20 text-xs font-semibold">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#C30064]"></i>
                    <span>In-House Graphic Design Studio</span>
                </div>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight leading-tight">
                    Belum Punya File Siap Cetak? <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 via-pink-200 to-rose-200">
                        AN Grafika Siap Bantu Desainkan!
                    </span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">
                    Kami menerima pesanan jasa desain khusus dari nol. Desainer profesional kami siap membuatkan desain logo usaha, kemasan produk kuliner, spanduk event, hingga identitas brand lengkap dengan file master siap cetak (*vector AI/PDF HD*).
                </p>

                <div class="grid grid-cols-2 gap-3 text-xs text-slate-200">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                        <span>Desain Logo UMKM & Toko</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                        <span>Desain Kemasan / Box Kue</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                        <span>Desain Spanduk & Baliho</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                        <span>Revisi Sampai Sesuai</span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="design-request.php" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-[#C30064] hover:bg-[#a30053] text-white font-black text-sm shadow-xl transition">
                        <i data-lucide="palette" class="w-4 h-4"></i>
                        <span>Kirim Brief Desain Anda Sekarang</span>
                    </a>
                </div>
            </div>

            <!-- Right Visual -->
            <div class="lg:col-span-6">
                <div class="bg-white/10 backdrop-blur-xl p-6 sm:p-7 rounded-3xl border border-white/20 shadow-2xl space-y-4">
                    <h4 class="font-bold text-sm text-white pb-3 border-b border-white/10">3 Langkah Mudah Request Desain</h4>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3 bg-white/5 p-3 rounded-xl border border-white/10">
                            <span class="w-7 h-7 rounded-lg bg-[#00388C] text-white font-bold flex items-center justify-center text-xs shrink-0">1</span>
                            <div>
                                <p class="text-xs font-bold text-white">Isi Formulir Brief & Contoh Referensi</p>
                                <p class="text-[11px] text-slate-300">Tulis nama brand, gaya desain yang diinginkan (minimalis, elegan, ceria), warna favorit, dan lampirkan contoh sketsa jika ada.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 bg-white/5 p-3 rounded-xl border border-white/10">
                            <span class="w-7 h-7 rounded-lg bg-[#C30064] text-white font-bold flex items-center justify-center text-xs shrink-0">2</span>
                            <div>
                                <p class="text-xs font-bold text-white">Dikerjakan Desainer & Pratinjau Draft</p>
                                <p class="text-[11px] text-slate-300">Desainer kami mulai mengeksekusi konsep dan mengunggah draft preview untuk Anda evaluasi.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 bg-white/5 p-3 rounded-xl border border-white/10">
                            <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-xs shrink-0">3</span>
                            <div>
                                <p class="text-xs font-bold text-white">Persetujuan & Langsung Masuk Cetak</p>
                                <p class="text-[11px] text-slate-300">Setelah desain disetujui, file master siap diunduh atau langsung dimasukkan antrean mesin cetak AN Grafika.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
