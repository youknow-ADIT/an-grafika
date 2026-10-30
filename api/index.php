<?php
/**
 * Vercel Serverless Entrypoint & Front Controller for AN Grafika
 * Routes incoming web requests to their respective PHP handlers
 */

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH);
$path = rawurldecode($path);

$baseDir = dirname(__DIR__);

// Normalize home / root route
if ($path === '/' || $path === '' || $path === '/index') {
    $script = '/index.php';
} else {
    $script = $path;
}

$targetFile = $baseDir . $script;

// Check if direct file exists, or resolve without .php extension
if (!file_exists($targetFile) || is_dir($targetFile)) {
    if (file_exists($baseDir . $script . '.php')) {
        $targetFile = $baseDir . $script . '.php';
    } else if (file_exists(rtrim($targetFile, '/') . '/index.php')) {
        $targetFile = rtrim($targetFile, '/') . '/index.php';
    }
}

// Security: Prevent directory traversal outside the project directory
$realTarget = realpath($targetFile);
$realBase = realpath($baseDir);

if (!$realTarget || strpos($realTarget, $realBase) !== 0 || !file_exists($realTarget)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>404 Not Found - AN Grafika</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script></head>';
    echo '<body class="bg-slate-100 flex items-center justify-center min-h-screen text-center p-6">';
    echo '<div class="bg-white p-8 rounded-3xl shadow-xl max-w-md w-full border border-slate-200">';
    echo '<div class="text-4xl mb-3">🖨️</div>';
    echo '<h1 class="text-4xl font-extrabold text-[#00388C] mb-2">404</h1>';
    echo '<p class="text-slate-600 mb-6 text-sm">Halaman yang Anda tuju tidak ditemukan.</p>';
    echo '<a href="/" class="inline-block px-6 py-2.5 bg-[#C30064] text-white rounded-xl font-bold shadow-lg hover:bg-pink-700 transition">Kembali ke Beranda</a>';
    echo '</div></body></html>';
    exit;
}

// Fallback handling for static assets (css, js, images, webp, pdf)
$ext = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
$mimeTypes = [
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'json'  => 'application/json',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'webp'  => 'image/webp',
    'ico'   => 'image/x-icon',
    'pdf'   => 'application/pdf',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
];

if (isset($mimeTypes[$ext])) {
    header('Content-Type: ' . $mimeTypes[$ext]);
    header('Cache-Control: public, max-age=86400');
    readfile($realTarget);
    exit;
}

// Setup execution context for target PHP script
chdir(dirname($realTarget));
$relativeScript = '/' . ltrim(str_replace('\\', '/', substr($realTarget, strlen($realBase))), '/');
$_SERVER['SCRIPT_FILENAME'] = $realTarget;
$_SERVER['SCRIPT_NAME']     = $relativeScript;
$_SERVER['PHP_SELF']        = $relativeScript;

require $realTarget;
