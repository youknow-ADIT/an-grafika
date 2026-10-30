<?php
/**
 * TiDB Cloud Connection Tester & Database Migrator
 * Run via CLI: php test_tidb.php
 * Or via Browser: http://localhost/angrafika/test_tidb.php
 */

$isCli = (php_sapi_name() === 'cli');

$host = $_REQUEST['host'] ?? getenv('DB_HOST') ?: getenv('TIDB_HOST') ?: '';
$port = $_REQUEST['port'] ?? getenv('DB_PORT') ?: getenv('TIDB_PORT') ?: '4000';
$user = $_REQUEST['user'] ?? getenv('DB_USER') ?: getenv('TIDB_USER') ?: '';
$pass = $_REQUEST['pass'] ?? getenv('DB_PASS') ?: getenv('TIDB_PASSWORD') ?: '';
$name = $_REQUEST['name'] ?? getenv('DB_NAME') ?: getenv('TIDB_DATABASE') ?: 'test';
$doImport = isset($_REQUEST['import']) && $_REQUEST['import'] === '1';

$results = [];
$status = 'idle';

if (!empty($host) && !empty($user)) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $caCert = __DIR__ . '/config/ca.pem';
        if (file_exists($caCert)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caCert;
        }

        $pdo = new PDO($dsn, $user, $pass, $options);
        $status = 'connected';
        $results[] = "✅ Sukses terhubung ke TiDB Cloud di: {$host}:{$port}";

        // Check existing tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $results[] = "📊 Tabel saat ini: " . (empty($tables) ? "(Belum ada tabel / database kosong)" : implode(', ', $tables));

        if ($doImport || empty($tables)) {
            $sqlFile = __DIR__ . '/database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $pdo->exec($sqlContent);
                $tablesAfter = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $status = 'imported';
                $results[] = "🚀 Berhasil mengimport database.sql!";
                $results[] = "📋 Tabel aktif sekarang: " . implode(', ', $tablesAfter);
                
                // Count records
                $prodCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                $vouchCount = $pdo->query("SELECT COUNT(*) FROM vouchers")->fetchColumn();
                $results[] = "🎉 Data terverifikasi: {$catCount} Kategori, {$prodCount} Produk, {$vouchCount} Voucher Promo aktif.";
            } else {
                $results[] = "⚠️ File database.sql tidak ditemukan.";
            }
        }
    } catch (Exception $e) {
        $status = 'error';
        $results[] = "❌ Gagal terhubung ke TiDB Cloud: " . $e->getMessage();
    }
}

if ($isCli) {
    echo "=== PENGUJIAN KONEKSI TIDB CLOUD ===\n";
    if (empty($host) || empty($user)) {
        echo "Format pemanggilan:\n";
        echo "php test_tidb.php [host] [user] [password] [database] [port]\n";
        echo "Atau jalankan via browser di http://localhost/angrafika/test_tidb.php\n";
        exit;
    }
    foreach ($results as $res) {
        echo $res . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>TiDB Cloud Setup & Migrator - AN Grafika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-slate-800 rounded-3xl shadow-2xl border border-slate-700 overflow-hidden">
        <div class="bg-gradient-to-r from-[#00388C] to-[#C30064] p-6 text-white text-center">
            <span class="text-3xl">☁️</span>
            <h1 class="text-2xl font-black mt-2">TiDB Cloud Connector & Auto-Migrator</h1>
            <p class="text-xs text-pink-100 mt-1">Uji koneksi SSL TiDB Cloud & inisialisasi tabel untuk Vercel</p>
        </div>

        <div class="p-6">
            <?php if (!empty($results)): ?>
                <div class="mb-6 p-4 rounded-2xl <?= $status === 'error' ? 'bg-rose-950/60 border border-rose-700 text-rose-200' : 'bg-emerald-950/60 border border-emerald-700 text-emerald-200' ?> text-xs space-y-1.5 font-mono">
                    <?php foreach ($results as $r): ?>
                        <div><?= htmlspecialchars($r); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="test_tidb.php" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Host / Endpoint TiDB</label>
                        <input type="text" name="host" value="<?= htmlspecialchars($host); ?>" placeholder="gateway01.ap-southeast-1.prod.aws.tidbcloud.com" required
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#C30064]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Port</label>
                        <input type="text" name="port" value="<?= htmlspecialchars($port); ?>" placeholder="4000" required
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#C30064]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">User TiDB</label>
                        <input type="text" name="user" value="<?= htmlspecialchars($user); ?>" placeholder="xxxxxxx.root" required
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#C30064]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Password TiDB</label>
                        <input type="password" name="pass" value="<?= htmlspecialchars($pass); ?>" placeholder="••••••••" required
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#C30064]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Database Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($name); ?>" placeholder="test" required
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#C30064]">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="import" id="import" value="1" <?= $doImport ? 'checked' : ''; ?> class="rounded text-[#C30064] focus:ring-[#C30064]">
                    <label for="import" class="text-xs text-slate-300">Jalankan import / update struktur & data sampel <code>database.sql</code></label>
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#00388C] to-[#C30064] hover:opacity-95 text-white font-bold rounded-xl text-xs transition shadow-lg mt-2">
                    ⚡ Uji Koneksi & Jalankan Migrasi
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-700/60 text-[11px] text-slate-400">
                <p class="font-bold text-slate-300 mb-1">Langkah Mendapatkan Database TiDB Cloud Gratis:</p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Buka <a href="https://tidbcloud.com" target="_blank" class="text-pink-400 underline">tidbcloud.com</a> dan daftar dengan akun Google/GitHub (Gratis 5GB, tanpa kartu kredit).</li>
                    <li>Buat Cluster baru tipe <strong>Serverless</strong> (pilih Region Singapore).</li>
                    <li>Klik <strong>Connect</strong> di dashboard TiDB untuk melihat Host, User, Port (4000), dan Generate Password.</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
