<?php
/**
 * Global Helper Functions for AN Grafika
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function formatRupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateReceiptNo($prefix = 'AN') {
    $datePart = date('Ymd');
    $randomPart = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "{$prefix}-{$datePart}-{$randomPart}";
}

function generateTicketNo($prefix = 'DSG') {
    $datePart = date('Ymd');
    $randomPart = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "{$prefix}-{$datePart}-{$randomPart}";
}

function generateVoucherCode($prefix = 'AN', $length = 4) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . $code;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'name'     => $_SESSION['user_name'] ?? 'User',
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'pegawai',
    ];
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function isStaff() {
    return isLoggedIn() && in_array(($_SESSION['user_role'] ?? ''), ['admin', 'pegawai']);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php?error=unauthorized');
        exit;
    }
}

function requireStaff() {
    if (!isStaff()) {
        header('Location: ../login.php?error=unauthorized');
        exit;
    }
}

/**
 * File upload helper for design files & product photos
 */
function handleFileUpload($fileInputName, $subfolder = 'designs') {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fileInputName];
    $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'svg', 'ai', 'psd', 'cdr', 'zip', 'rar'];
    $maxSize = 50 * 1024 * 1024; // 50MB

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        return ['error' => 'Format file tidak didukung. Ekstensi yang diizinkan: JPG, PNG, WEBP, PDF, AI, PSD, CDR, ZIP.'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => 'Ukuran file terlalu besar. Maksimal 50 MB.'];
    }

    $uploadDir = __DIR__ . '/../uploads/' . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newFileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success'   => true,
            'file_name' => $file['name'],
            'file_path' => 'uploads/' . $subfolder . '/' . $newFileName
        ];
    }

    return ['error' => 'Gagal mengupload file ke server.'];
}

function getStatusBadge($status, $type = 'production') {
    if ($type === 'production') {
        switch ($status) {
            case 'pending':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">⏳ Menunggu Antrean</span>';
            case 'verified':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-[#00388C] border border-blue-200">🔍 File Terverifikasi</span>';
            case 'printing':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-[#C30064] border border-pink-200 animate-pulse">🖨️ Proses Cetak</span>';
            case 'finishing':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">✂️ Finishing / Cutting</span>';
            case 'ready':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800 border border-cyan-200">📦 Siap Diambil / Kirim</span>';
            case 'completed':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">✅ Selesai</span>';
            case 'cancelled':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800 border border-rose-200">❌ Dibatalkan</span>';
            default:
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800">' . htmlspecialchars($status) . '</span>';
        }
    } elseif ($type === 'design') {
        switch ($status) {
            case 'submitted':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">📝 Brief Diterima</span>';
            case 'in_progress':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-[#00388C] border border-blue-200">🎨 Sedang Didesain</span>';
            case 'review_ready':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-[#C30064] border border-pink-200">👀 Preview Siap Direview</span>';
            case 'revision':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-200">🔄 Tahap Revisi</span>';
            case 'completed':
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">🎉 Desain Disetujui</span>';
            default:
                return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800">' . htmlspecialchars($status) . '</span>';
        }
    }
    return '';
}
