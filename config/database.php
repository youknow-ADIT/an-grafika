<?php
/**
 * Database Connection & Configuration for AN Grafika
 * Compatible with MySQL / MariaDB (Standard XAMPP default)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_hosts = ['127.0.0.1', 'localhost'];
$db_port = '3306';
$db_user = 'root';
$db_pass = '';
$db_name = 'angrafika_db';

$pdo = null;
$db_error = null;

foreach ($db_hosts as $host) {
    try {
        $dsn = "mysql:host={$host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        break; // Successfully connected!
    } catch (PDOException $e) {
        // Try creating database on this host if it doesn't exist
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

// Self-healing schema checks: ensure column 'image' exists in 'categories'
if ($pdo) {
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetch();
        if (!$checkCol) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) NULL AFTER icon");
        }
    } catch (Exception $e) {
        // safe ignore
    }
}

function getDB() {
    global $pdo, $db_error;
    if (!$pdo) {
        die('<div style="font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;background:#fdf2f8;border:1px solid #f472b6;border-radius:16px;color:#831843;">
            <div style="display:flex;items-center;gap:12px;margin-bottom:16px;">
                <span style="font-size:32px;">🖨️</span>
                <h2 style="margin:0;color:#00388c;font-size:20px;">AN Grafika - Koneksi Database</h2>
            </div>
            <p style="margin-bottom:12px;font-size:14px;color:#374151;">Sistem tidak dapat terhubung ke MySQL XAMPP di <code>localhost:3306</code>.</p>
            <p style="font-weight:bold;margin-bottom:8px;font-size:13px;">Langkah Mengaktifkan:</p>
            <ol style="line-height:1.8;padding-left:20px;font-size:13px;color:#4b5563;">
                <li>Buka aplikasi <strong>XAMPP Control Panel</strong> Anda.</li>
                <li>Klik tombol <strong>Start</strong> pada modul <strong>Apache</strong> dan <strong>MySQL</strong>.</li>
                <li>Setelah keduanya berwarna hijau, buka <a href="install.php" style="color:#c30064;font-weight:bold;text-decoration:underline;">install.php</a> untuk inisialisasi database otomatis!</li>
            </ol>
            <p style="font-size:11px;color:#9ca3af;margin-top:16px;">Info Teknis: ' . htmlspecialchars($db_error ?? '') . '</p>
        </div>');
    }
    return $pdo;
}
