<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
        exit;
    } else {
        header('Location: staff/pos.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan masukkan username dan password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Akun Anda dinonaktifkan oleh Administrator.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                        exit;
                    } else {
                        header('Location: staff/pos.php');
                        exit;
                    }
                }
            } else {
                $error = 'Username atau password salah. Silakan coba kembali.';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staff & Administrator - AN Grafika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12 border border-slate0">
        
        <!-- Left Side: Brand Visual & Logo -->
        <div class="md:col-span-5 bg-gradient-to-br from-[#002052] via-[#00388C] to-[#8c0047] p-8 text-white flex flex-col justify-between relative overflow-hidden">
            <div class="relative z-10 space-y-4">
                <a href="index.php" class="inline-flex items-center gap-1.5 text-xs text-pink-200 hover:text-white transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali ke Website Utama</span>
                </a>

                <div class="pt-6">
                    <div class="w-16 h-16 rounded-2xl bg-white p-1.5 flex items-center justify-center shadow-2xl mb-4">
                        <img src="assets/img/logo.png" alt="AN Grafika" class="w-full h-full object-contain">
                    </div>
                    <h2 class="text-2xl font-black tracking-tight">Portal Internal AN Grafika</h2>
                    <p class="text-xs text-pink-100 mt-2 leading-relaxed">
                        Akses khusus untuk Administrator dan Pegawai Kasir (*POS & Workshop Management*).
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="md:col-span-7 p-8 sm:p-12 flex flex-col justify-center">
            
            <div class="mb-6">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">Masuk ke Sistem</h3>
                <p class="text-xs text-slate-500 mt-1">Masukkan kredensial akun Anda untuk melanjutkan ke dashboard.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Username:</label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="username" id="input_username" required placeholder="Masukkan username..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password:</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="password" name="password" id="input_password" required placeholder="Masukkan password..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-[#00388C] focus:outline-none">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-bold text-xs sm:text-sm rounded-xl shadow-lg shadow-[#00388C]/25 transition flex items-center justify-center gap-2">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span>Masuk Sekarang</span>
                    </button>
                </div>
            </form>

            <!-- Quick Auto-Fill Demo Accounts -->
            <div class="mt-8 pt-6 border-t border-slate-100">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Akun Demo Siap Klik (Otomatis Isi):</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillAccount('admin', 'admin123')" class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 text-left transition group">
                        <span class="font-bold text-[#00388C] block text-[11px] group-hover:underline">Akun Administrator</span>
                        <span class="text-[10px] text-slate-500">User: admin / admin123</span>
                    </button>
                    <button type="button" onclick="fillAccount('kasir1', 'kasir123')" class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-pink-50 hover:border-pink-300 text-left transition group">
                        <span class="font-bold text-[#C30064] block text-[11px] group-hover:underline">Akun Pegawai Kasir</span>
                        <span class="text-[10px] text-slate-500">User: kasir1 / kasir123</span>
                    </button>
                </div>
            </div>

        </div>

    </div>

    <script>
        lucide.createIcons();
        function fillAccount(u, p) {
            document.getElementById('input_username').value = u;
            document.getElementById('input_password').value = p;
        }
    </script>
</body>
</html>
