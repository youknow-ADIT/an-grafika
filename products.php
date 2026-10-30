<?php
$pageTitle = "Katalog Produk Percetakan Lengkap & Harga - AN Grafika";
require_once __DIR__ . '/includes/header.php';

$selectedCatSlug = $_GET['cat'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$categories = [];
$products = [];
$vouchers = [];
$broadenedToAll = false;

try {
    $db = getDB();
    $catStmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $catStmt->fetchAll();

    $vouchStmt = $db->query("SELECT * FROM vouchers WHERE status = 'active' AND end_date >= CURDATE() LIMIT 4");
    $vouchers = $vouchStmt->fetchAll();

    // Base query
    if (!empty($searchQuery)) {
        // Broad search across name, description, and category name
        $searchSql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.status = 'active' 
                      AND (p.name LIKE :q OR p.description LIKE :q OR c.name LIKE :q)";
        $searchParam = [':q' => "%{$searchQuery}%"];

        if ($selectedCatSlug !== 'all' && !empty($selectedCatSlug)) {
            $catSql = $searchSql . " AND c.slug = :cat ORDER BY p.id ASC";
            $stmt = $db->prepare($catSql);
            $stmt->execute(array_merge($searchParam, [':cat' => $selectedCatSlug]));
            $products = $stmt->fetchAll();

            // Jika dalam kategori yang dipilih tidak ada hasil, cari otomatis di seluruh kategori agar produk tetap muncul!
            if (empty($products)) {
                $stmt = $db->prepare($searchSql . " ORDER BY p.id ASC");
                $stmt->execute($searchParam);
                $products = $stmt->fetchAll();
                if (!empty($products)) {
                    $broadenedToAll = true;
                }
            }
        } else {
            $stmt = $db->prepare($searchSql . " ORDER BY p.id ASC");
            $stmt->execute($searchParam);
            $products = $stmt->fetchAll();
        }
    } else {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'active'";
        $params = [];

        if ($selectedCatSlug !== 'all' && !empty($selectedCatSlug)) {
            $sql .= " AND c.slug = :cat";
            $params[':cat'] = $selectedCatSlug;
        }

        $sql .= " ORDER BY p.category_id ASC, p.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    }

} catch (Exception $e) {
    // handled
}
?>

<!-- Header Banner -->
<div class="bg-gradient-to-r from-[#002052] via-[#00388C] to-[#8c0047] text-white py-12 border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-pink-200 text-xs font-semibold mb-2">
                    <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                    <span>Katalog Harga AN Grafika</span>
                </div>
                <h1 class="text-3xl font-black tracking-tight">Katalog Produk Percetakan Digital & Offset</h1>
                <p class="text-slate-200 text-xs sm:text-sm mt-1 max-w-xl">
                    Semua harga terstandarisasi. Anda dapat memilih spesifikasi bahan, finishing, dan menerapkan kode voucher diskon saat checkout.
                </p>
            </div>

            <!-- Search form with Instant Live Filter & Submit -->
            <form action="products.php" method="GET" class="w-full md:w-96 flex items-center gap-2">
                <div class="relative w-full">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" id="catalog-search" value="<?= htmlspecialchars($searchQuery); ?>" oninput="liveFilterCatalog(this.value)" placeholder="Cari nama produk (stiker, brosur, banner)..." class="w-full pl-10 pr-9 py-2.5 rounded-xl bg-white text-slate-900 placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-pink-400 shadow-md font-medium">
                    <button type="button" id="catalog-clear-btn" onclick="clearCatalogSearch()" class="<?= empty($searchQuery) ? 'hidden' : ''; ?> absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-1">
                        ✕
                    </button>
                </div>
                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-[#C30064] to-[#a30053] hover:opacity-95 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shrink-0 flex items-center gap-1.5 transition">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Cari</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Promo Voucher Ticker -->
<?php if (!empty($vouchers)): ?>
<div class="bg-pink-50 border-b border-pink-200/60 py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-[#C30064] font-bold">
            <span class="p-1 rounded bg-[#C30064] text-white"><i data-lucide="percent" class="w-3.5 h-3.5"></i></span>
            <span>Voucher Diskon Aktif:</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php foreach ($vouchers as $v): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-pink-200 text-slate-800 font-medium shadow-sm">
                    <strong class="font-mono text-[#C30064] font-bold"><?= htmlspecialchars($v['code']); ?></strong>
                    <span class="text-slate-500 text-[11px]">(Potongan <?= $v['discount_type'] === 'percent' ? $v['discount_value'] . '%' : formatRupiah($v['discount_value']); ?>)</span>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Catalog Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Active Search Breadcrumb / Notification -->
    <?php if (!empty($searchQuery)): ?>
        <div class="mb-6 p-3.5 rounded-2xl bg-blue-50 border border-blue-200 text-[#00388C] text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-sm">
            <div class="flex items-center gap-2 flex-wrap">
                <i data-lucide="search" class="w-4 h-4 text-[#00388C] shrink-0"></i>
                <span>Menampilkan hasil pencarian untuk: <strong class="text-slate-900 font-bold">"<?= htmlspecialchars($searchQuery); ?>"</strong> (Ditemukan <?= count($products); ?> produk)</span>
                <?php if ($broadenedToAll): ?>
                    <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-[10px] font-bold">Ditampilkan dari seluruh kategori</span>
                <?php endif; ?>
            </div>
            <a href="products.php" class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-800 font-bold hover:underline shrink-0">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                <span>Reset Pencarian</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Category Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-8 no-scrollbar">
        <a href="products.php<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : ''; ?>" class="px-4 py-2 rounded-xl text-xs font-bold shrink-0 transition <?= $selectedCatSlug === 'all' ? 'bg-[#00388C] text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'; ?>">
            Semua Produk
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?cat=<?= htmlspecialchars($cat['slug']); ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold shrink-0 transition flex items-center gap-2 <?= $selectedCatSlug === $cat['slug'] ? 'bg-[#00388C] text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'; ?>">
                <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/' . $cat['image'])): ?>
                    <img src="<?= htmlspecialchars($cat['image']); ?>" class="w-4 h-4 rounded-md object-cover">
                <?php else: ?>
                    <i data-lucide="<?= htmlspecialchars($cat['icon'] ?: 'printer'); ?>" class="w-3.5 h-3.5"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($cat['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
        <div id="catalog-no-results" class="bg-white rounded-2xl p-12 text-center border border-slate-200 max-w-md mx-auto my-12">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="package-search" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Produk Tidak Ditemukan</h3>
            <p class="text-xs text-slate-500 mt-1">Coba gunakan kata kunci lain atau tampilkan seluruh produk.</p>
            <a href="products.php" class="inline-block mt-4 px-4 py-2.5 bg-[#00388C] text-white text-xs font-bold rounded-xl hover:bg-[#002c70] transition shadow-md">
                Tampilkan Semua Produk
            </a>
        </div>
    <?php else: ?>
        <div id="catalog-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $prod): 
                $options = json_decode($prod['options_json'] ?? '{}', true);
            ?>
                <div class="catalog-item bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col justify-between group"
                     data-name="<?= htmlspecialchars(strtolower($prod['name'])); ?>"
                     data-catname="<?= htmlspecialchars(strtolower($prod['category_name'])); ?>"
                     data-desc="<?= htmlspecialchars(strtolower($prod['description'] ?? '')); ?>">
                    <div>
                        <!-- Header Preview Foto Produk -->
                        <div class="h-44 bg-slate-100 relative overflow-hidden flex items-center justify-center">
                            <?php if (!empty($prod['image']) && file_exists(__DIR__ . '/' . $prod['image'])): ?>
                                <img src="<?= htmlspecialchars($prod['image']); ?>" alt="<?= htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-2xl bg-white shadow-sm text-[#00388C] flex items-center justify-center group-hover:scale-110 transition transform">
                                    <i data-lucide="printer" class="w-7 h-7"></i>
                                </div>
                            <?php endif; ?>
                            <span class="absolute top-3 left-3 bg-white/95 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-700 border border-slate-200 shadow-sm">
                                <?= htmlspecialchars($prod['category_name']); ?>
                            </span>
                            <span class="absolute top-3 right-3 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-[10px] font-bold shadow-sm">
                                <?= $prod['estimated_days']; ?> Hari Jadi
                            </span>
                        </div>

                        <!-- Content info -->
                        <div class="p-5">
                            <h3 class="font-bold text-slate-900 text-sm hover:text-[#00388C] transition leading-snug">
                                <a href="order.php?product_id=<?= $prod['id']; ?>"><?= htmlspecialchars($prod['name']); ?></a>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($prod['description']); ?>
                            </p>

                            <!-- Mini Options badges -->
                            <?php if (!empty($options)): ?>
                                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1">
                                    <?php 
                                    foreach ($options as $optKey => $optItems): 
                                        if (is_array($optItems)):
                                    ?>
                                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded capitalize">
                                            <?= htmlspecialchars($optKey); ?>: <?= count($optItems); ?> Opsi
                                        </span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Price & Action Footer -->
                    <div class="p-5 pt-0">
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 block">Mulai Dari</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-base font-black text-[#00388C]"><?= formatRupiah($prod['base_price']); ?></span>
                                    <span class="text-[10px] text-slate-500">/ <?= htmlspecialchars($prod['unit']); ?></span>
                                </div>
                            </div>
                            <a href="order.php?product_id=<?= $prod['id']; ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 group-hover:bg-[#00388C] transition flex items-center gap-1 shadow-sm">
                                <span>Pesan</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    function liveFilterCatalog(val) {
        const q = (val || '').toLowerCase().trim();
        const clearBtn = document.getElementById('catalog-clear-btn');
        if (q.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        const items = document.querySelectorAll('.catalog-item');
        let visibleCount = 0;

        items.forEach(el => {
            const name = (el.dataset.name || '');
            const cat = (el.dataset.catname || '');
            const desc = (el.dataset.desc || '');

            if (q === '' || name.includes(q) || cat.includes(q) || desc.includes(q)) {
                el.classList.remove('hidden');
                visibleCount++;
            } else {
                el.classList.add('hidden');
            }
        });

        let emptyMsg = document.getElementById('catalog-live-empty');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                const grid = document.getElementById('catalog-grid');
                if (grid) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.id = 'catalog-live-empty';
                    emptyMsg.className = 'col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200 p-8';
                    emptyMsg.innerHTML = `
                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="package-search" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Produk Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 mt-1">Tidak ada produk cetak yang cocok dengan kata kunci "${q}".</p>
                        <button type="button" onclick="clearCatalogSearch()" class="inline-block mt-4 px-4 py-2.5 bg-[#00388C] text-white text-xs font-bold rounded-xl hover:bg-[#002c70] transition shadow-md">
                            Tampilkan Semua Produk
                        </button>
                    `;
                    grid.appendChild(emptyMsg);
                    if (window.lucide) lucide.createIcons();
                }
            }
            if (emptyMsg) emptyMsg.classList.remove('hidden');
        } else if (emptyMsg) {
            emptyMsg.classList.add('hidden');
        }
    }

    function clearCatalogSearch() {
        const input = document.getElementById('catalog-search');
        if (input) input.value = '';
        const clearBtn = document.getElementById('catalog-clear-btn');
        if (clearBtn) clearBtn.classList.add('hidden');
        liveFilterCatalog('');
        if (window.location.search.includes('q=')) {
            window.location.href = 'products.php';
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
