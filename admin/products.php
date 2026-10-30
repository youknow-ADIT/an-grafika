<?php
$adminTitle = "Katalog Produk & Harga";
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $catId = (int)($_POST['category_id'] ?? 1);
    $name = cleanInput($_POST['name'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $desc = cleanInput($_POST['description'] ?? '');
    $basePrice = (float)($_POST['base_price'] ?? 0);
    $unit = cleanInput($_POST['unit'] ?? 'pcs');
    $minOrder = max(1, (int)($_POST['min_order'] ?? 1));
    $estDays = max(1, (int)($_POST['estimated_days'] ?? 1));
    $optionsJson = trim($_POST['options_json'] ?? '');

    if (empty($name) || $basePrice <= 0) {
        $error = "Nama produk dan harga dasar wajib diisi dengan benar.";
    } else {
        try {
            $db = getDB();

            // Handle Product Image Upload
            $imagePath = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handleFileUpload('product_image', 'products');
                if (isset($uploadRes['success'])) {
                    $imagePath = $uploadRes['file_path'];
                } elseif (isset($uploadRes['error'])) {
                    throw new Exception($uploadRes['error']);
                }
            }

            $insSql = "INSERT INTO products (category_id, name, slug, description, base_price, unit, min_order, image, options_json, estimated_days, status) 
                       VALUES (:cid, :name, :slug, :desc, :price, :unit, :min, :img, :opts, :days, 'active')";
            $stmt = $db->prepare($insSql);
            $stmt->execute([
                ':cid'   => $catId,
                ':name'  => $name,
                ':slug'  => $slug . '-' . time(),
                ':desc'  => $desc,
                ':price' => $basePrice,
                ':unit'  => $unit,
                ':min'   => $minOrder,
                ':img'   => $imagePath,
                ':opts'  => !empty($optionsJson) ? $optionsJson : null,
                ':days'  => $estDays
            ]);
            $success = "Produk cetak '{$name}' berhasil ditambahkan ke katalog!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $prodId = (int)($_POST['product_id'] ?? 0);
    $catId = (int)($_POST['category_id'] ?? 1);
    $name = cleanInput($_POST['name'] ?? '');
    $desc = cleanInput($_POST['description'] ?? '');
    $basePrice = (float)($_POST['base_price'] ?? 0);
    $unit = cleanInput($_POST['unit'] ?? 'pcs');
    $minOrder = max(1, (int)($_POST['min_order'] ?? 1));
    $estDays = max(1, (int)($_POST['estimated_days'] ?? 1));
    $optionsJson = trim($_POST['options_json'] ?? '');

    if ($prodId <= 0 || empty($name) || $basePrice <= 0) {
        $error = "Data produk tidak lengkap.";
    } else {
        try {
            $db = getDB();

            // Check if new image uploaded
            $imagePath = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handleFileUpload('product_image', 'products');
                if (isset($uploadRes['success'])) {
                    $imagePath = $uploadRes['file_path'];
                }
            }

            if ($imagePath) {
                $upSql = "UPDATE products SET category_id = :cid, name = :name, description = :desc, base_price = :price, unit = :unit, min_order = :min, image = :img, options_json = :opts, estimated_days = :days WHERE id = :id";
                $stmt = $db->prepare($upSql);
                $stmt->execute([
                    ':cid'   => $catId,
                    ':name'  => $name,
                    ':desc'  => $desc,
                    ':price' => $basePrice,
                    ':unit'  => $unit,
                    ':min'   => $minOrder,
                    ':img'   => $imagePath,
                    ':opts'  => !empty($optionsJson) ? $optionsJson : null,
                    ':days'  => $estDays,
                    ':id'    => $prodId
                ]);
            } else {
                $upSql = "UPDATE products SET category_id = :cid, name = :name, description = :desc, base_price = :price, unit = :unit, min_order = :min, options_json = :opts, estimated_days = :days WHERE id = :id";
                $stmt = $db->prepare($upSql);
                $stmt->execute([
                    ':cid'   => $catId,
                    ':name'  => $name,
                    ':desc'  => $desc,
                    ':price' => $basePrice,
                    ':unit'  => $unit,
                    ':min'   => $minOrder,
                    ':opts'  => !empty($optionsJson) ? $optionsJson : null,
                    ':days'  => $estDays,
                    ':id'    => $prodId
                ]);
            }

            $success = "Produk '{$name}' berhasil diperbarui!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $prodId = (int)$_GET['delete'];
    try {
        $db = getDB();
        $db->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $prodId]);
        $success = "Produk berhasil dihapus dari katalog.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status'])) {
    $prodId = (int)$_GET['toggle_status'];
    try {
        $db = getDB();
        $curr = $db->prepare("SELECT status FROM products WHERE id = :id");
        $curr->execute([':id' => $prodId]);
        $p = $curr->fetch();
        if ($p) {
            $newStatus = ($p['status'] === 'active') ? 'inactive' : 'active';
            $db->prepare("UPDATE products SET status = :s WHERE id = :id")->execute([':s' => $newStatus, ':id' => $prodId]);
            $success = "Status produk berhasil diperbarui.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch categories & products
$categories = [];
$products = [];
try {
    $db = getDB();
    $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    
    $pSql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC";
    $products = $db->query($pSql)->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-6">
    
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Katalog Produk Percetakan & Harga</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola produk cetak AN Grafika, unggah foto produk, atur harga, dan edit rincian spesifikasi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../products.php" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">
                <i data-lucide="external-link" class="w-4 h-4 text-[#00388C]"></i>
                <span>Lihat Katalog Publik</span>
            </a>
            <button onclick="toggleModal('modal-add-product')" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-md shadow-[#00388C]/20 transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>+ Tambah Produk Baru</span>
            </button>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <span><?= htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Product Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-900">Semua Produk Cetak (<?= count($products); ?> Item)</h2>
                <span class="text-xs text-slate-400">Pencarian real-time, foto produk & menu edit</span>
            </div>
            
            <!-- Quick Search Bar inside Admin Table Header -->
            <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="admin-product-search" oninput="filterAdminProducts()" placeholder="Cari produk / kata kunci..." class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                    <button type="button" id="admin-clear-search-btn" onclick="clearAdminProductSearch()" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-0.5">
                        ✕
                    </button>
                </div>
                <select id="admin-category-filter" onchange="filterAdminProducts()" class="w-full sm:w-auto px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-[#00388C] focus:outline-none text-slate-700">
                    <option value="all">Semua Kategori</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= htmlspecialchars($c['name']); ?>"><?= htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">Foto & Produk</th>
                        <th class="p-3.5">Kategori</th>
                        <th class="p-3.5 text-right">Harga Dasar</th>
                        <th class="p-3.5 text-center">Satuan & Min. Order</th>
                        <th class="p-3.5 text-center">Estimasi</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-center">Aksi / Menu Edit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($products as $p): ?>
                        <tr class="admin-product-row hover:bg-slate-50/70 transition"
                            data-name="<?= htmlspecialchars(strtolower($p['name'])); ?>"
                            data-cat="<?= htmlspecialchars(strtolower($p['category_name'])); ?>"
                            data-desc="<?= htmlspecialchars(strtolower($p['description'] ?? '')); ?>"
                            data-price="<?= (float)$p['base_price']; ?>">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <!-- Foto Produk / Placeholder -->
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                        <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../' . $p['image'])): ?>
                                            <img src="../<?= htmlspecialchars($p['image']); ?>" alt="<?= htmlspecialchars($p['name']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="text-[#00388C] font-bold">
                                                <i data-lucide="image" class="w-5 h-5 text-slate-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm leading-snug"><?= htmlspecialchars($p['name']); ?></p>
                                        <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5"><?= htmlspecialchars($p['description']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-[#00388C] border border-blue-100">
                                    <?= htmlspecialchars($p['category_name']); ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-right font-black text-slate-900 text-sm">
                                <?= formatRupiah($p['base_price']); ?>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="capitalize font-semibold text-slate-800"><?= htmlspecialchars($p['unit']); ?></span>
                                <p class="text-[10px] text-slate-400">Min. <?= $p['min_order']; ?> <?= $p['unit']; ?></p>
                            </td>
                            <td class="p-3.5 text-center font-medium text-slate-700">
                                <?= $p['estimated_days']; ?> Hari
                            </td>
                            <td class="p-3.5">
                                <a href="products.php?toggle_status=<?= $p['id']; ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition <?= $p['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $p['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-400'; ?>"></span>
                                    <span><?= $p['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?></span>
                                </a>
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Tombol EDIT Lengkap -->
                                    <button type="button" onclick='openEditModal(<?= json_encode($p); ?>)' title="Edit Produk" class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs transition inline-flex items-center gap-1">
                                        <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                        <span>Edit</span>
                                    </button>

                                    <!-- Tombol LIHAT di Web -->
                                    <a href="../order.php?product_id=<?= $p['id']; ?>" target="_blank" title="Pratinjau Halaman Order Publik" class="p-1.5 rounded-xl bg-slate-100 hover:bg-blue-100 text-slate-600 hover:text-[#00388C] transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>

                                    <!-- Tombol HAPUS -->
                                    <a href="products.php?delete=<?= $p['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')" title="Hapus Produk" class="p-1.5 rounded-xl bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 transition">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Produk Baru -->
<div id="modal-add-product" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Tambah Produk Percetakan Baru</h3>
            <button onclick="toggleModal('modal-add-product')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="add_product">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk: *</label>
                <input type="text" name="name" required placeholder="Contoh: Cetak Brosur Lipat 3 A4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
            </div>

            <!-- Upload Foto Produk -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Foto / Gambar Produk: (Opsional)</label>
                <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center bg-slate-50 hover:border-[#00388C] transition">
                    <input type="file" name="product_image" accept="image/*" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#00388C] hover:file:bg-blue-100 cursor-pointer">
                    <span class="text-[10px] text-slate-400 mt-1 block">Format: JPG, PNG, WEBP (Resolusi rekomendasi 800x800px)</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori: *</label>
                    <select name="category_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Dasar (Rp): *</label>
                    <input type="number" name="base_price" required min="0" step="any" placeholder="Contoh: 2000 atau 25000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-slate-900">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Bebas masukkan harga pas (contoh: 2000, 2500, 50000).</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan Unit:</label>
                    <select name="unit" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none capitalize font-medium">
                        <option value="pcs" selected>Pcs</option>
                        <option value="lembar">Lembar</option>
                        <option value="box">Box (100 pcs)</option>
                        <option value="rim">Rim (500 lbr)</option>
                        <option value="meter">Meter (m²)</option>
                        <option value="set">Set / Paket</option>
                        <option value="buku">Buku</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Min. Order:</label>
                    <input type="number" name="min_order" value="1" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-center">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Estimasi Hari:</label>
                    <input type="number" name="estimated_days" value="1" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-center">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat:</label>
                <textarea name="description" rows="2" placeholder="Jelaskan kualitas cetak, bahan standar, dan kegunaan..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    <span>Opsi Varian Bahan & Finishing (Format JSON):</span>
                    <span class="text-[10px] text-slate-400 font-normal ml-1">(Opsional)</span>
                </label>
                <textarea name="options_json" rows="2" placeholder='{"bahan":[{"name":"Art Carton 260g","price":0}],"finishing":[{"name":"Doff","price":5000}]}' class="w-full font-mono text-[11px] px-3.5 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
            </div>

            <div class="pt-3 flex gap-2">
                <button type="button" onclick="toggleModal('modal-add-product')" class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95 shadow-md">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal EDIT Produk (Menu Edit) -->
<div id="modal-edit-product" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900">Edit Produk Percetakan</h3>
                <span class="text-xs text-[#00388C] font-semibold" id="edit-prod-label"></span>
            </div>
            <button onclick="toggleModal('modal-edit-product')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit_product_id" value="">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk: *</label>
                <input type="text" name="name" id="edit_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
            </div>

            <!-- Current Image Preview & Upload Foto Baru -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Produk:</label>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div id="edit_current_img_container" class="w-14 h-14 rounded-lg bg-white border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                        <img id="edit_current_img" src="" alt="Preview" class="w-full h-full object-cover hidden">
                        <i id="edit_no_img_icon" data-lucide="image" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="product_image" accept="image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-blue-100 file:text-[#00388C] cursor-pointer">
                        <span class="text-[10px] text-slate-400 mt-1 block">Biarkan kosong jika tidak ingin mengganti foto saat ini.</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori: *</label>
                    <select name="category_id" id="edit_category_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-medium">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Dasar (Rp): *</label>
                    <input type="number" name="base_price" id="edit_base_price" required min="0" step="any" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-slate-900">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Bebas masukkan nominal pas (contoh: 2000, 2500, 50000).</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan Unit:</label>
                    <select name="unit" id="edit_unit" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none capitalize font-medium">
                        <option value="pcs">Pcs</option>
                        <option value="lembar">Lembar</option>
                        <option value="box">Box (100 pcs)</option>
                        <option value="rim">Rim (500 lbr)</option>
                        <option value="meter">Meter (m²)</option>
                        <option value="set">Set / Paket</option>
                        <option value="buku">Buku</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Min. Order:</label>
                    <input type="number" name="min_order" id="edit_min_order" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-center">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Estimasi Hari:</label>
                    <input type="number" name="estimated_days" id="edit_estimated_days" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-center">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat:</label>
                <textarea name="description" id="edit_description" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Opsi Varian Bahan & Finishing (Format JSON):</label>
                <textarea name="options_json" id="edit_options_json" rows="2" class="w-full font-mono text-[11px] px-3.5 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
            </div>

            <div class="pt-3 flex gap-2">
                <button type="button" onclick="toggleModal('modal-edit-product')" class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95 shadow-md">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(id) {
        const m = document.getElementById(id);
        if (m.classList.contains('hidden')) {
            m.classList.remove('hidden');
            m.classList.add('flex');
        } else {
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
    }

    function openEditModal(prod) {
        document.getElementById('edit_product_id').value = prod.id;
        document.getElementById('edit-prod-label').innerText = 'ID #' + prod.id + ' — ' + prod.name;
        document.getElementById('edit_name').value = prod.name;
        document.getElementById('edit_category_id').value = prod.category_id;
        document.getElementById('edit_base_price').value = prod.base_price;
        document.getElementById('edit_unit').value = prod.unit;
        document.getElementById('edit_min_order').value = prod.min_order;
        document.getElementById('edit_estimated_days').value = prod.estimated_days;
        document.getElementById('edit_description').value = prod.description || '';
        document.getElementById('edit_options_json').value = prod.options_json || '';

        // Image handling
        const imgEl = document.getElementById('edit_current_img');
        const iconEl = document.getElementById('edit_no_img_icon');
        if (prod.image) {
            imgEl.src = '../' + prod.image;
            imgEl.classList.remove('hidden');
            iconEl.classList.add('hidden');
        } else {
            imgEl.classList.add('hidden');
            iconEl.classList.remove('hidden');
        }

        toggleModal('modal-edit-product');
    }

    function filterAdminProducts() {
        const q = (document.getElementById('admin-product-search').value || '').toLowerCase().trim();
        const cat = (document.getElementById('admin-category-filter').value || 'all').toLowerCase().trim();
        const clearBtn = document.getElementById('admin-clear-search-btn');

        if (q.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        const rows = document.querySelectorAll('.admin-product-row');
        let visible = 0;

        rows.forEach(r => {
            const name = (r.dataset.name || '');
            const rCat = (r.dataset.cat || '');
            const desc = (r.dataset.desc || '');

            const matchQ = (q === '' || name.includes(q) || rCat.includes(q) || desc.includes(q));
            const matchCat = (cat === 'all' || rCat === cat);

            if (matchQ && matchCat) {
                r.style.display = '';
                visible++;
            } else {
                r.style.display = 'none';
            }
        });

        let noRow = document.getElementById('admin-no-prod-row');
        if (visible === 0) {
            if (!noRow) {
                const tbody = document.querySelector('tbody');
                noRow = document.createElement('tr');
                noRow.id = 'admin-no-prod-row';
                noRow.innerHTML = '<td colspan="7" class="p-8 text-center text-slate-400 text-xs font-semibold">Tidak ada produk yang cocok dengan pencarian kata kunci tersebut.</td>';
                tbody.appendChild(noRow);
            }
            noRow.style.display = '';
        } else if (noRow) {
            noRow.style.display = 'none';
        }
    }

    function clearAdminProductSearch() {
        document.getElementById('admin-product-search').value = '';
        document.getElementById('admin-clear-search-btn').classList.add('hidden');
        filterAdminProducts();
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
