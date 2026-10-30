<?php
$adminTitle = "Kategori Percetakan";
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = cleanInput($_POST['name'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $desc = cleanInput($_POST['description'] ?? '');
    $icon = cleanInput($_POST['icon'] ?? 'printer');

    if (empty($name)) {
        $error = "Nama kategori wajib diisi.";
    } else {
        try {
            $db = getDB();

            // Handle Image Upload
            $imagePath = null;
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handleFileUpload('category_image', 'categories');
                if (isset($uploadRes['success'])) {
                    $imagePath = $uploadRes['file_path'];
                } elseif (isset($uploadRes['error'])) {
                    throw new Exception($uploadRes['error']);
                }
            }

            $stmt = $db->prepare("INSERT INTO categories (name, slug, description, icon, image) VALUES (:name, :slug, :desc, :icon, :image)");
            $stmt->execute([
                ':name'  => $name,
                ':slug'  => $slug . '-' . time(),
                ':desc'  => $desc,
                ':icon'  => $icon,
                ':image' => $imagePath
            ]);
            $success = "Kategori '{$name}' dengan foto berhasil ditambahkan!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    $catId = (int)($_POST['category_id'] ?? 0);
    $name = cleanInput($_POST['name'] ?? '');
    $desc = cleanInput($_POST['description'] ?? '');
    $icon = cleanInput($_POST['icon'] ?? 'printer');

    if ($catId <= 0 || empty($name)) {
        $error = "Data kategori tidak valid.";
    } else {
        try {
            $db = getDB();

            // Handle Image Upload if new image uploaded
            $imagePath = null;
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handleFileUpload('category_image', 'categories');
                if (isset($uploadRes['success'])) {
                    $imagePath = $uploadRes['file_path'];
                } elseif (isset($uploadRes['error'])) {
                    throw new Exception($uploadRes['error']);
                }
            }

            if ($imagePath) {
                $stmt = $db->prepare("UPDATE categories SET name = :name, description = :desc, icon = :icon, image = :img WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':desc' => $desc,
                    ':icon' => $icon,
                    ':img'  => $imagePath,
                    ':id'   => $catId
                ]);
            } else {
                $stmt = $db->prepare("UPDATE categories SET name = :name, description = :desc, icon = :icon WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':desc' => $desc,
                    ':icon' => $icon,
                    ':id'   => $catId
                ]);
            }
            $success = "Kategori '{$name}' berhasil diperbarui!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $catId = (int)$_GET['delete'];
    try {
        $db = getDB();
        $db->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => $catId]);
        $success = "Kategori berhasil dihapus.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$categories = [];
try {
    $db = getDB();
    $categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.id ASC")->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kategori Produk Percetakan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola kategori lengkap dengan foto visual agar pelanggan dan staf mengetahui jenis produk percetakan.</p>
        </div>
        <button onclick="toggleModal('modal-add-category')" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-md shadow-[#00388C]/20 transition">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>+ Tambah Kategori Baru</span>
        </button>
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

    <!-- Category Grid with Photos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php foreach ($categories as $cat): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:shadow-md transition flex flex-col justify-between group">
                <div>
                    <!-- Foto Kategori Visual -->
                    <div class="h-36 w-full rounded-xl bg-slate-100 border border-slate-100 overflow-hidden relative mb-3 flex items-center justify-center">
                        <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/../' . $cat['image'])): ?>
                            <img src="../<?= htmlspecialchars($cat['image']); ?>" alt="<?= htmlspecialchars($cat['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#00388C] flex items-center justify-center mb-1 shadow-sm">
                                    <i data-lucide="<?= htmlspecialchars($cat['icon'] ?: 'printer'); ?>" class="w-6 h-6"></i>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-400">Belum ada foto</span>
                            </div>
                        <?php endif; ?>
                        
                        <span class="absolute top-2.5 right-2.5 text-[11px] font-black text-[#C30064] bg-white/95 backdrop-blur-sm px-2.5 py-0.5 rounded-lg shadow-sm border border-pink-100">
                            <?= $cat['product_count']; ?> Produk
                        </span>
                    </div>

                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-6 h-6 rounded-md bg-blue-50 text-[#00388C] flex items-center justify-center shrink-0">
                            <i data-lucide="<?= htmlspecialchars($cat['icon'] ?: 'printer'); ?>" class="w-3.5 h-3.5"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm leading-snug"><?= htmlspecialchars($cat['name']); ?></h3>
                    </div>

                    <p class="text-xs text-slate-500 line-clamp-2 mt-1 leading-relaxed"><?= htmlspecialchars($cat['description'] ?: 'Tidak ada deskripsi'); ?></p>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="font-mono text-[10px] text-slate-400 truncate max-w-[120px]">slug: <?= htmlspecialchars($cat['slug']); ?></span>
                    
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick='openEditCatModal(<?= json_encode($cat); ?>)' class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs inline-flex items-center gap-1 transition">
                            <i data-lucide="edit" class="w-3 h-3"></i>
                            <span>Edit</span>
                        </button>
                        <a href="categories.php?delete=<?= $cat['id']; ?>" onclick="return confirm('Hapus kategori ini? Semua produk di dalamnya juga akan terhapus.')" class="p-1 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition" title="Hapus Kategori">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Add Category -->
<div id="modal-add-category" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Tambah Kategori Baru</h3>
            <button onclick="toggleModal('modal-add-category')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="add_category">
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori: *</label>
                <input type="text" name="name" required placeholder="Contoh: Sablon Kaos & Kain" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <!-- Upload Foto Kategori -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Kategori (Visual Preview):</label>
                <input type="file" name="category_image" accept="image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-[#00388C] cursor-pointer">
                <span class="text-[10px] text-slate-400 mt-1 block">Format: JPG, PNG, WEBP (Maksimal 5MB).</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Icon (Lucide):</label>
                <input type="text" name="icon" value="printer" placeholder="printer, file-text, tag, image, box, gift..." class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat:</label>
                <textarea name="description" rows="2" placeholder="Penjelasan isi kategori..." class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="button" onclick="toggleModal('modal-add-category')" class="flex-1 py-2 px-3 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Batal</button>
                <button type="submit" class="flex-1 py-2 px-3 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Category -->
<div id="modal-edit-category" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Edit Kategori Percetakan</h3>
            <button onclick="toggleModal('modal-edit-category')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="edit_category">
            <input type="hidden" name="category_id" id="edit_cat_id">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori: *</label>
                <input type="text" name="name" id="edit_cat_name" required class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <!-- Preview Foto Saat Ini & Upload Baru -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Kategori Saat Ini:</label>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200 mb-2">
                    <div id="edit_cat_img_container" class="w-16 h-16 rounded-lg bg-white border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                        <img id="edit_cat_current_img" src="" alt="Preview" class="w-full h-full object-cover hidden">
                        <i id="edit_cat_no_img_icon" data-lucide="image" class="w-6 h-6 text-slate-400"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="category_image" accept="image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-blue-100 file:text-[#00388C] cursor-pointer">
                        <span class="text-[10px] text-slate-400 mt-1 block">Pilih file baru untuk mengganti foto.</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Icon (Lucide):</label>
                <input type="text" name="icon" id="edit_cat_icon" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat:</label>
                <textarea name="description" id="edit_cat_desc" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="button" onclick="toggleModal('modal-edit-category')" class="flex-1 py-2 px-3 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Batal</button>
                <button type="submit" class="flex-1 py-2 px-3 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(id) {
        const m = document.getElementById(id);
        m.classList.toggle('hidden');
        m.classList.toggle('flex');
    }

    function openEditCatModal(cat) {
        document.getElementById('edit_cat_id').value = cat.id;
        document.getElementById('edit_cat_name').value = cat.name;
        document.getElementById('edit_cat_icon').value = cat.icon || 'printer';
        document.getElementById('edit_cat_desc').value = cat.description || '';

        const imgEl = document.getElementById('edit_cat_current_img');
        const iconEl = document.getElementById('edit_cat_no_img_icon');
        if (cat.image) {
            imgEl.src = '../' + cat.image;
            imgEl.classList.remove('hidden');
            iconEl.classList.add('hidden');
        } else {
            imgEl.classList.add('hidden');
            iconEl.classList.remove('hidden');
        }

        toggleModal('modal-edit-category');
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
