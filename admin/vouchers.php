<?php
$adminTitle = "Kode Voucher Diskon";
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Handle Create Voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_voucher') {
    $code = strtoupper(trim(cleanInput($_POST['code'] ?? '')));
    $title = cleanInput($_POST['title'] ?? '');
    $type = cleanInput($_POST['discount_type'] ?? 'percent');
    $value = (float)($_POST['discount_value'] ?? 0);
    $minPurchase = (float)($_POST['min_purchase'] ?? 0);
    $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $usageLimit = max(1, (int)($_POST['usage_limit'] ?? 100));
    $startDate = cleanInput($_POST['start_date'] ?? date('Y-m-d'));
    $endDate = cleanInput($_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days')));

    if (empty($code) || $value <= 0) {
        $error = "Kode voucher dan nilai diskon wajib diisi dengan benar.";
    } else {
        try {
            $db = getDB();
            $check = $db->prepare("SELECT id FROM vouchers WHERE code = :c");
            $check->execute([':c' => $code]);
            if ($check->fetch()) {
                $error = "Kode voucher '{$code}' sudah ada. Silakan gunakan kode lain.";
            } else {
                $insSql = "INSERT INTO vouchers (code, title, discount_type, discount_value, min_purchase, max_discount, usage_limit, used_count, start_date, end_date, status)
                           VALUES (:code, :title, :type, :val, :min, :max, :limit, 0, :sdate, :edate, 'active')";
                $stmt = $db->prepare($insSql);
                $stmt->execute([
                    ':code'  => $code,
                    ':title' => $title ?: "Promo Diskon {$code}",
                    ':type'  => $type,
                    ':val'   => $value,
                    ':min'   => $minPurchase,
                    ':max'   => $maxDiscount,
                    ':limit' => $usageLimit,
                    ':sdate' => $startDate,
                    ':edate' => $endDate
                ]);
                $success = "Voucher diskon '{$code}' berhasil digenerate dan aktif!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Delete Voucher
if (isset($_GET['delete'])) {
    $vId = (int)$_GET['delete'];
    try {
        $db = getDB();
        $db->prepare("DELETE FROM vouchers WHERE id = :id")->execute([':id' => $vId]);
        $success = "Kode voucher berhasil dihapus.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status'])) {
    $vId = (int)$_GET['toggle_status'];
    try {
        $db = getDB();
        $curr = $db->prepare("SELECT status FROM vouchers WHERE id = :id");
        $curr->execute([':id' => $vId]);
        $v = $curr->fetch();
        if ($v) {
            $newStatus = ($v['status'] === 'active') ? 'inactive' : 'active';
            $db->prepare("UPDATE vouchers SET status = :s WHERE id = :id")->execute([':s' => $newStatus, ':id' => $vId]);
            $success = "Status voucher berhasil diubah.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all vouchers
$vouchers = [];
try {
    $db = getDB();
    $vouchers = $db->query("SELECT * FROM vouchers ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-6">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Manajemen & Generate Kode Voucher Diskon</h1>
            <p class="text-xs text-slate-500 mt-0.5">Buat kupon promo diskon persentase (%) atau nominal (Rp) untuk pemesanan online dan kasir AN Grafika.</p>
        </div>
        <button onclick="toggleModal('modal-generate-voucher')" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-md shadow-[#00388C]/20 transition">
            <i data-lucide="sparkles" class="w-4 h-4"></i>
            <span>+ Generate Voucher Baru</span>
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

    <!-- Vouchers List Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900">Daftar Voucher Aktif & Promo (<?= count($vouchers); ?> Kode)</h2>
            <span class="text-xs text-slate-400">Dapat Diterapkan oleh Kasir POS & Pelanggan Online</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">Kode Voucher</th>
                        <th class="p-3.5">Deskripsi Promo</th>
                        <th class="p-3.5">Nilai Potongan</th>
                        <th class="p-3.5">Syarat Belanja</th>
                        <th class="p-3.5 text-center">Pemakaian</th>
                        <th class="p-3.5">Periode Berlaku</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($vouchers as $v): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 rounded-lg bg-pink-50 text-[#C30064] font-mono font-black text-xs border border-pink-200 tracking-wider">
                                    <?= htmlspecialchars($v['code']); ?>
                                </span>
                            </td>
                            <td class="p-3.5">
                                <p class="font-bold text-slate-900"><?= htmlspecialchars($v['title']); ?></p>
                                <span class="text-[10px] text-slate-400 uppercase">Tipe: <?= $v['discount_type'] === 'percent' ? 'Persentase %' : 'Potongan Tunai Rp'; ?></span>
                            </td>
                            <td class="p-3.5 font-black text-sm <?= $v['discount_type'] === 'percent' ? 'text-[#00388C]' : 'text-[#C30064]'; ?>">
                                <?= $v['discount_type'] === 'percent' ? $v['discount_value'] . '%' : formatRupiah($v['discount_value']); ?>
                            </td>
                            <td class="p-3.5">
                                <p class="text-[11px] text-slate-700">Min. <?= formatRupiah($v['min_purchase']); ?></p>
                                <?php if (!empty($v['max_discount'])): ?>
                                    <p class="text-[10px] text-slate-400">Maks. <?= formatRupiah($v['max_discount']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="font-bold text-slate-900"><?= $v['used_count']; ?></span>
                                <span class="text-slate-400">/ <?= $v['usage_limit']; ?></span>
                            </td>
                            <td class="p-3.5 text-[11px]">
                                <span class="text-slate-700"><?= date('d/m/Y', strtotime($v['start_date'])); ?> s/d <?= date('d/m/Y', strtotime($v['end_date'])); ?></span>
                            </td>
                            <td class="p-3.5">
                                <a href="vouchers.php?toggle_status=<?= $v['id']; ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition <?= $v['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $v['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-400'; ?>"></span>
                                    <span><?= $v['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?></span>
                                </a>
                            </td>
                            <td class="p-3.5 text-center">
                                <a href="vouchers.php?delete=<?= $v['id']; ?>" onclick="return confirm('Hapus voucher diskon ini?')" class="p-1.5 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 transition inline-block">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Generate Voucher -->
<div id="modal-generate-voucher" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Generate Kode Voucher Diskon Baru</h3>
            <button onclick="toggleModal('modal-generate-voucher')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="create_voucher">

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700">Kode Voucher: *</label>
                    <button type="button" onclick="autoGenerateCode()" class="text-[11px] font-bold text-[#C30064] hover:underline flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                        <span>Acak Kode Otomatis</span>
                    </button>
                </div>
                <input type="text" name="code" id="voucher_code_input" required placeholder="Contoh: CETAKHEMAT15" class="uppercase font-mono font-bold w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none tracking-wider">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Judul / Keterangan Promo: *</label>
                <input type="text" name="title" required placeholder="Contoh: Diskon 15% Cetak Massal UMKM" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Diskon: *</label>
                    <select name="discount_type" id="discount_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-slate-700">
                        <option value="percent">Persentase (%)</option>
                        <option value="fixed">Nominal Tetap (Rp)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nilai Diskon: *</label>
                    <input type="number" name="discount_value" required min="1" step="any" placeholder="10 (untuk 10%) atau 25000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Minimal Belanja (Rp):</label>
                    <input type="number" name="min_purchase" value="0" min="0" placeholder="0 jika tanpa minimal" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Maks. Diskon (Rp):</label>
                    <input type="number" name="max_discount" placeholder="Contoh: 30000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kuota Pakai:</label>
                    <input type="number" name="usage_limit" value="100" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-center">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mulai Tanggal:</label>
                    <input type="date" name="start_date" value="<?= date('Y-m-d'); ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Berakhir Tanggal:</label>
                    <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')); ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex gap-2">
                <button type="button" onclick="toggleModal('modal-generate-voucher')" class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95 shadow-md">
                    Simpan & Aktifkan
                </button>
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

    function autoGenerateCode() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let res = 'AN';
        for (let i = 0; i < 4; i++) {
            res += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('voucher_code_input').value = res;
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
