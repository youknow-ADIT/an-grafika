<?php
$adminTitle = "Manajemen Akun Pegawai";
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Handle Create Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_employee') {
    $name = cleanInput($_POST['name'] ?? '');
    $username = trim(cleanInput($_POST['username'] ?? ''));
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = cleanInput($_POST['phone'] ?? '');
    $role = cleanInput($_POST['role'] ?? 'pegawai');

    if (empty($name) || empty($username) || empty($password)) {
        $error = 'Nama, Username, dan Password wajib diisi.';
    } else {
        try {
            $db = getDB();
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :u");
            $checkStmt->execute([':u' => $username]);
            if ($checkStmt->fetch()) {
                $error = "Username '{$username}' sudah digunakan. Silakan gunakan username lain.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insStmt = $db->prepare("INSERT INTO users (name, username, email, password, role, phone, status) VALUES (:name, :u, :e, :p, :r, :phone, 'active')");
                $insStmt->execute([
                    ':name'  => $name,
                    ':u'     => $username,
                    ':e'     => $email,
                    ':p'     => $hashed,
                    ':r'     => $role,
                    ':phone' => $phone
                ]);
                $success = "Akun pegawai '{$name}' (Username: {$username}) berhasil dibuat!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newPass = $_POST['new_password'] ?? '';

    if ($userId > 0 && !empty($newPass)) {
        try {
            $db = getDB();
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = :p WHERE id = :id")->execute([':p' => $hashed, ':id' => $userId]);
            $success = "Password akun berhasil direset!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    if ($userId !== (int)$currentUser['id']) {
        try {
            $db = getDB();
            $curr = $db->prepare("SELECT status FROM users WHERE id = :id");
            $curr->execute([':id' => $userId]);
            $userRow = $curr->fetch();
            if ($userRow) {
                $newStatus = ($userRow['status'] === 'active') ? 'inactive' : 'active';
                $db->prepare("UPDATE users SET status = :s WHERE id = :id")->execute([':s' => $newStatus, ':id' => $userId]);
                $success = "Status pegawai berhasil diubah menjadi {$newStatus}!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    if ($userId === (int)$currentUser['id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.";
    } else {
        try {
            $db = getDB();
            $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $userId]);
            $success = "Akun pegawai berhasil dihapus.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch all employees
$employees = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE staff_id = u.id) as processed_orders FROM users u ORDER BY u.role ASC, u.id DESC");
    $employees = $stmt->fetchAll();
} catch (Exception $e) {
    // handled
}
?>

<div class="space-y-6">
    
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Manajemen Akun Pegawai & Kasir</h1>
            <p class="text-xs text-slate-500 mt-0.5">Buat akun pegawai kasir baru, atur status akses, dan reset password.</p>
        </div>
        <button onclick="toggleModal('modal-add-employee')" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 shadow-md shadow-[#00388C]/20 transition">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>+ Buat Akun Pegawai Baru</span>
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

    <!-- Employees List Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900">Daftar Pengguna Sistem (<?= count($employees); ?> Akun)</h2>
            <span class="text-xs text-slate-400">Admin & Pegawai Kasir AN Grafika</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">Nama Pegawai</th>
                        <th class="p-3.5">Username & Kontak</th>
                        <th class="p-3.5">Role</th>
                        <th class="p-3.5 text-center">Transaksi Diproses</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-center">Aksi & Keamanan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($employees as $emp): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 text-[#00388C] font-bold flex items-center justify-center text-xs">
                                        <?= strtoupper(substr($emp['name'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($emp['name']); ?></p>
                                        <p class="text-[11px] text-slate-400">Terdaftar: <?= date('d M Y', strtotime($emp['created_at'])); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <p class="font-mono font-bold text-[#00388C]"><?= htmlspecialchars($emp['username']); ?></p>
                                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($emp['email'] ?: '-'); ?></p>
                                <p class="text-[11px] text-slate-400"><?= htmlspecialchars($emp['phone'] ?: '-'); ?></p>
                            </td>
                            <td class="p-3.5">
                                <span class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $emp['role'] === 'admin' ? 'bg-blue-100 text-[#00388C]' : 'bg-pink-100 text-[#C30064]'; ?>">
                                    <?= htmlspecialchars($emp['role']); ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-center font-bold text-slate-900">
                                <?= $emp['processed_orders']; ?> Struk
                            </td>
                            <td class="p-3.5">
                                <a href="employees.php?toggle_status=<?= $emp['id']; ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition <?= $emp['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 hover:bg-rose-200'; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $emp['status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                    <span><?= $emp['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?></span>
                                </a>
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="openResetModal(<?= $emp['id']; ?>, '<?= htmlspecialchars($emp['name']); ?>')" title="Reset Password" class="p-1.5 rounded-lg bg-slate-100 hover:bg-amber-100 text-slate-600 hover:text-amber-800 transition">
                                        <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <?php if ($emp['id'] !== $currentUser['id']): ?>
                                        <a href="employees.php?delete=<?= $emp['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus akun pegawai ini?')" title="Hapus Akun" class="p-1.5 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 transition">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Create Employee -->
<div id="modal-add-employee" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Buat Akun Pegawai / Staff Baru</h3>
            <button onclick="toggleModal('modal-add-employee')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="create_employee">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Pegawai: *</label>
                <input type="text" name="name" required placeholder="Contoh: Riko Pratama" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Username Login: *</label>
                    <input type="text" name="username" required placeholder="Contoh: kasir3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password Awal: *</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Hak Akses (Role):</label>
                <select name="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none font-bold text-slate-700">
                    <option value="pegawai" selected>Pegawai Kasir (POS Workshop)</option>
                    <option value="admin">Administrator Utama</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email:</label>
                    <input type="email" name="email" placeholder="pegawai@angrafika.id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. HP / WhatsApp:</label>
                    <input type="tel" name="phone" placeholder="08123456789" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex gap-2">
                <button type="button" onclick="toggleModal('modal-add-employee')" class="flex-1 py-2.5 px-4 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] text-white rounded-xl text-xs font-bold hover:opacity-95 shadow-md">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Password -->
<div id="modal-reset-password" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Reset Password Pegawai</h3>
            <button onclick="toggleModal('modal-reset-password')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4 mt-3">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset_user_id" value="">

            <p class="text-xs text-slate-500">
                Ubah password untuk: <strong id="reset_user_name" class="text-slate-900"></strong>
            </p>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru:</label>
                <input type="password" name="new_password" required placeholder="Masukkan password baru..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
            </div>

            <div class="pt-2 flex gap-2">
                <button type="button" onclick="toggleModal('modal-reset-password')" class="flex-1 py-2 px-3 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2 px-3 bg-[#00388C] text-white rounded-xl text-xs font-bold hover:bg-[#002c70]">
                    Simpan Password
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

    function openResetModal(userId, userName) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('reset_user_name').innerText = userName;
        toggleModal('modal-reset-password');
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
