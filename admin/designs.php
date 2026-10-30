<?php
$adminTitle = "Request Jasa Desain";
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Handle Update Status & Upload Preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_design') {
    $designId = (int)($_POST['design_id'] ?? 0);
    $status = cleanInput($_POST['status'] ?? 'submitted');
    $designerNotes = cleanInput($_POST['designer_notes'] ?? '');

    if ($designId > 0) {
        try {
            $db = getDB();
            
            $previewPath = null;
            if (isset($_FILES['preview_file']) && $_FILES['preview_file']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handleFileUpload('preview_file', 'designs');
                if (isset($uploadRes['success'])) {
                    $previewPath = $uploadRes['file_path'];
                }
            }

            if ($previewPath) {
                $stmt = $db->prepare("UPDATE design_requests SET status = :s, designer_notes = :n, preview_file = :p WHERE id = :id");
                $stmt->execute([':s' => $status, ':n' => $designerNotes, ':p' => $previewPath, ':id' => $designId]);
            } else {
                $stmt = $db->prepare("UPDATE design_requests SET status = :s, designer_notes = :n WHERE id = :id");
                $stmt->execute([':s' => $status, ':n' => $designerNotes, ':id' => $designId]);
            }

            $success = "Status brief desain berhasil diperbarui!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch all design requests
$requests = [];
try {
    $db = getDB();
    $requests = $db->query("SELECT * FROM design_requests ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-6">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Studio Penerimaan Request Desain (Logo dll)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola permintaan desain logo, packaging, banner, review brief klien, dan unggah draft pratinjau.</p>
        </div>
        <a href="../design-request.php" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C30064] hover:bg-[#a30053] shadow-sm transition">
            <i data-lucide="external-link" class="w-4 h-4"></i>
            <span>Buka Formulir Request Klien</span>
        </a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span><?= htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900">Semua Permintaan Desain Masuk (<?= count($requests); ?> Brief)</h2>
            <span class="text-xs text-slate-400">Terhubung Otomatis dengan Pelacakan No. Struk</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">No. Tiket & Tanggal</th>
                        <th class="p-3.5">Brand / Toko</th>
                        <th class="p-3.5">Jenis Desain & Paket</th>
                        <th class="p-3.5">Klien & WhatsApp</th>
                        <th class="p-3.5">Biaya Desain</th>
                        <th class="p-3.5">Status Pengerjaan</th>
                        <th class="p-3.5 text-center">Kelola & Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                Belum ada permintaan jasa desain yang masuk.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-3.5">
                                    <span class="font-mono font-bold text-[#00388C]"><?= htmlspecialchars($r['ticket_no']); ?></span>
                                    <?php if (!empty($r['receipt_no'])): ?>
                                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">Struk: <?= htmlspecialchars($r['receipt_no']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-[10px] text-slate-400 mt-0.5"><?= date('d/m/Y H:i', strtotime($r['created_at'])); ?></p>
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($r['brand_name']); ?></p>
                                    <?php if (!empty($r['tagline'])): ?>
                                        <p class="text-[11px] text-slate-500 italic">"<?= htmlspecialchars($r['tagline']); ?>"</p>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5">
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($r['design_type']); ?></span>
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-pink-50 text-[#C30064] block w-max mt-1">
                                        <?= htmlspecialchars($r['package_type']); ?>
                                    </span>
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($r['customer_name']); ?></p>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['customer_phone']); ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-bold hover:underline mt-0.5">
                                        <i data-lucide="message-circle" class="w-3 h-3"></i>
                                        <span><?= htmlspecialchars($r['customer_phone']); ?></span>
                                    </a>
                                </td>
                                <td class="p-3.5 font-black text-slate-900 text-sm">
                                    <?= formatRupiah($r['estimated_price']); ?>
                                </td>
                                <td class="p-3.5">
                                    <?= getStatusBadge($r['status'], 'design'); ?>
                                </td>
                                <td class="p-3.5 text-center">
                                    <button onclick='openDesignModal(<?= json_encode($r); ?>)' class="px-3 py-1.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-[#C30064] font-bold text-xs transition inline-flex items-center gap-1 border border-pink-200">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Detail & Update</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Manage Design Request -->
<div id="modal-manage-design" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900" id="modal-ticket-title">Detail Permintaan Desain</h3>
                <span class="text-xs text-[#00388C] font-mono font-bold" id="modal-ticket-no"></span>
            </div>
            <button onclick="toggleModal('modal-manage-design')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="space-y-4 mt-4 text-xs text-slate-700">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2">
                <p><strong>Gaya Desain:</strong> <span id="modal-style"></span> &bull; <strong>Warna:</strong> <span id="modal-colors"></span></p>
                <div>
                    <strong class="block text-slate-900 mb-0.5">Brief / Deskripsi Kebutuhan:</strong>
                    <p id="modal-brief" class="text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200"></p>
                </div>
                <div id="modal-ref-container" class="hidden pt-1">
                    <strong class="block text-slate-900 mb-1">File Contoh Referensi Klien:</strong>
                    <a id="modal-ref-link" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[#00388C] font-bold hover:underline">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Lihat Lampiran File Klien</span>
                    </a>
                </div>
            </div>

            <!-- Form update status & preview -->
            <form method="POST" enctype="multipart/form-data" class="space-y-4 pt-2 border-t border-slate-100">
                <input type="hidden" name="action" value="update_design">
                <input type="hidden" name="design_id" id="modal-design-id" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Update Status Pengerjaan Desain: *</label>
                    <select name="status" id="modal-status-select" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-bold text-slate-800 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                        <option value="submitted">📝 Brief Diterima (Menunggu Antrean)</option>
                        <option value="in_progress">🎨 Sedang Dikerjakan Desainer</option>
                        <option value="review_ready">👀 Preview Siap Direview Klien</option>
                        <option value="revision">🔄 Tahap Revisi dari Klien</option>
                        <option value="completed">🎉 Selesai & File Final Siap</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Upload File Pratinjau / Draft Desain (Preview JPG/PNG/PDF):</label>
                    <input type="file" name="preview_file" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-[#C30064] hover:file:bg-pink-100 cursor-pointer">
                    <span class="text-[10px] text-slate-400">File ini akan tampil di halaman pelacakan pesanan milik klien.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Desainer untuk Klien:</label>
                    <textarea name="designer_notes" id="modal-notes" rows="2" placeholder="Contoh: Konsep 1 dan 2 sudah diunggah, silakan dicek kombinasi warnanya..." class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#00388C] focus:outline-none"></textarea>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="button" onclick="toggleModal('modal-manage-design')" class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">
                        Tutup
                    </button>
                    <button type="submit" class="flex-1 py-2.5 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95 shadow-md">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(id) {
        const m = document.getElementById(id);
        m.classList.toggle('hidden');
        m.classList.toggle('flex');
    }

    function openDesignModal(data) {
        document.getElementById('modal-design-id').value = data.id;
        document.getElementById('modal-ticket-title').innerText = data.brand_name + ' (' + data.design_type + ')';
        document.getElementById('modal-ticket-no').innerText = 'No. Tiket: ' + data.ticket_no;
        document.getElementById('modal-style').innerText = data.design_style || '-';
        document.getElementById('modal-colors').innerText = data.preferred_colors || '-';
        document.getElementById('modal-brief').innerText = data.design_brief || '-';
        document.getElementById('modal-status-select').value = data.status;
        document.getElementById('modal-notes').value = data.designer_notes || '';

        const refContainer = document.getElementById('modal-ref-container');
        if (data.reference_file) {
            refContainer.classList.remove('hidden');
            document.getElementById('modal-ref-link').href = '../' + data.reference_file;
        } else {
            refContainer.classList.add('hidden');
        }

        toggleModal('modal-manage-design');
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
