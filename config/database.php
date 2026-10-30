<?php
/**
 * Database Connection & Configuration for AN Grafika
 * Compatible with TiDB Cloud Serverless & Local MySQL / MariaDB (XAMPP)
 */

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// 1. Detect Cloud Environment Variables (Vercel / Cloud Provider)
$env_host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? (getenv('TIDB_HOST') ?: ($_ENV['TIDB_HOST'] ?? ($_SERVER['TIDB_HOST'] ?? null)))));
$env_port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? (getenv('TIDB_PORT') ?: ($_ENV['TIDB_PORT'] ?? ($_SERVER['TIDB_PORT'] ?? null)))));
$env_user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? (getenv('TIDB_USER') ?: ($_ENV['TIDB_USER'] ?? ($_SERVER['TIDB_USER'] ?? null)))));
$env_pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? (getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? ($_SERVER['DB_PASSWORD'] ?? (getenv('TIDB_PASSWORD') ?: ($_ENV['TIDB_PASSWORD'] ?? ($_SERVER['TIDB_PASSWORD'] ?? null))))))));
$env_name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? (getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? ($_SERVER['DB_DATABASE'] ?? (getenv('TIDB_DATABASE') ?: ($_ENV['TIDB_DATABASE'] ?? ($_SERVER['TIDB_DATABASE'] ?? null))))))));

$pdo = null;
$db_error = null;

// A. Cloud Connection (TiDB Cloud Serverless / Remote MySQL)
if (!empty($env_host)) {
    try {
        $port = !empty($env_port) ? (string)$env_port : '4000';
        $user = !empty($env_user) ? (string)$env_user : 'root';
        $pass = $env_pass !== null ? (string)$env_pass : '';
        $name = !empty($env_name) ? (string)$env_name : 'angrafika_db';

        $dsn = "mysql:host={$env_host};port={$port};dbname={$name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // SSL / TLS configuration for TiDB Cloud
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];

        // Custom CA certificate (bundled in repo or system store)
        $caCert = __DIR__ . '/ca.pem';
        if (file_exists($caCert)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caCert;
        } else if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        }

        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        $db_error = "Koneksi Cloud DB Gagal: " . $e->getMessage();
    }
}

// B. Local Connection Fallback (XAMPP Localhost)
if (!$pdo) {
    $db_hosts = ['127.0.0.1', 'localhost'];
    $db_port = '3306';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'angrafika_db';

    foreach ($db_hosts as $host) {
        try {
            $dsn = "mysql:host={$host};port={$db_port};dbname={$db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            break; // Successfully connected
        } catch (PDOException $e) {
            // Auto create database on localhost if it does not exist
            try {
                $dsn_server = "mysql:host={$host};port={$db_port};charset=utf8mb4";
                $pdo_server = new PDO($dsn_server, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                $pdo = new PDO("mysql:host={$host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                $sqlFile = __DIR__ . '/../database.sql';
                if (file_exists($sqlFile)) {
                    $sqlContent = file_get_contents($sqlFile);
                    $pdo->exec($sqlContent);
                }
                break;
            } catch (PDOException $ex) {
                $db_error = $ex->getMessage();
            }
        }
    }
}

// C. Auto-migration & Self-healing on any connected database
if ($pdo) {
    try {
        // Auto-initialize schema & seed data if tables do not exist (e.g. new TiDB Cloud instance)
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$tableCheck) {
            $sqlFile = __DIR__ . '/../database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $pdo->exec($sqlContent);
            }
        }

        // Ensure category image column exists
        $checkCol = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetch();
        if (!$checkCol) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) NULL AFTER icon");
        }
    } catch (Exception $e) {
        // Suppress migration errors if already present
    }
}

function getDB() {
    global $pdo, $db_error;
    if (!$pdo) {
        die('<div style="font-family:sans-serif;max-width:640px;margin:50px auto;padding:28px;background:#fdf2f8;border:1px solid #f472b6;border-radius:20px;color:#831843;box-shadow:0 10px 25px -5px rgba(0,0,0,0.1);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <span style="font-size:32px;">🖨️</span>
                <h2 style="margin:0;color:#00388c;font-size:20px;font-weight:800;">AN Grafika - Koneksi Database</h2>
            </div>
            <p style="margin-bottom:12px;font-size:14px;color:#374151;">Sistem tidak dapat terhubung ke database MySQL.</p>
            <p style="font-weight:bold;margin-bottom:8px;font-size:13px;color:#00388c;">Panduan Pengaturan:</p>
            <ul style="line-height:1.8;padding-left:20px;font-size:13px;color:#4b5563;">
                <li><strong>Deploy di Vercel:</strong> Masukkan Environment Variables <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code>, dan <code>DB_PORT</code> di menu Settings &gt; Environment Variables.</li>
                <li><strong>Jalankan di Local XAMPP:</strong> Buka <strong>XAMPP Control Panel</strong> dan klik <strong>Start</strong> pada modul <strong>MySQL</strong>.</li>
            </ul>
            <div style="margin-top:16px;padding:12px;background:#fff;border-radius:10px;border:1px solid #fed7aa;font-size:12px;color:#9a3412;">
                <strong>Detail Pesan:</strong> ' . htmlspecialchars($db_error ?? 'Tidak dapat menjangkau host database.') . '
            </div>
        </div>');
    }
    return $pdo;
}
