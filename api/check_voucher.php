<?php
/**
 * API Endpoint: Realtime Voucher Validation
 * Checks voucher code against database vouchers table
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$code = strtoupper(trim($_REQUEST['code'] ?? ''));
$subtotal = max(0, (float)($_REQUEST['subtotal'] ?? 0));

if (empty($code)) {
    echo json_encode([
        'valid' => false,
        'message' => 'Silakan masukkan kode voucher terlebih dahulu.'
    ]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM vouchers WHERE UPPER(TRIM(code)) = :code LIMIT 1");
    $stmt->execute([':code' => $code]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        echo json_encode([
            'valid' => false,
            'message' => '❌ Kode voucher "' . htmlspecialchars($code) . '" tidak ditemukan.'
        ]);
        exit;
    }

    if ($voucher['status'] !== 'active') {
        echo json_encode([
            'valid' => false,
            'message' => '❌ Voucher "' . htmlspecialchars($voucher['code']) . '" sedang dinonaktifkan oleh admin.'
        ]);
        exit;
    }

    $today = date('Y-m-d');
    if (!empty($voucher['start_date']) && $voucher['start_date'] > $today) {
        echo json_encode([
            'valid' => false,
            'message' => '❌ Voucher baru dapat digunakan mulai tanggal ' . date('d/m/Y', strtotime($voucher['start_date'])) . '.'
        ]);
        exit;
    }

    if (!empty($voucher['end_date']) && $voucher['end_date'] < $today) {
        echo json_encode([
            'valid' => false,
            'message' => '❌ Voucher sudah kedaluwarsa pada ' . date('d/m/Y', strtotime($voucher['end_date'])) . '.'
        ]);
        exit;
    }

    if ((int)$voucher['usage_limit'] > 0 && (int)$voucher['used_count'] >= (int)$voucher['usage_limit']) {
        echo json_encode([
            'valid' => false,
            'message' => '❌ Kuota penggunaan voucher ini sudah habis (' . $voucher['used_count'] . '/' . $voucher['usage_limit'] . ' terpakai).'
        ]);
        exit;
    }

    $minPurchase = (float)$voucher['min_purchase'];
    if ($subtotal > 0 && $subtotal < $minPurchase) {
        echo json_encode([
            'valid' => false,
            'message' => '⚠️ Minimal belanja untuk voucher ini adalah ' . formatRupiah($minPurchase) . ' (Subtotal Anda: ' . formatRupiah($subtotal) . ').'
        ]);
        exit;
    }

    // Hitung nominal diskon
    $discountAmount = 0.00;
    if ($voucher['discount_type'] === 'percent') {
        $calc = ($subtotal * (float)$voucher['discount_value']) / 100;
        if (!empty($voucher['max_discount']) && (float)$voucher['max_discount'] > 0) {
            $calc = min($calc, (float)$voucher['max_discount']);
        }
        $discountAmount = $calc;
        $discountLabel = $voucher['discount_value'] . '%';
        if (!empty($voucher['max_discount']) && (float)$voucher['max_discount'] > 0) {
            $discountLabel .= ' (Maks. ' . formatRupiah($voucher['max_discount']) . ')';
        }
    } else {
        $discountAmount = (float)$voucher['discount_value'];
        if ($subtotal > 0) {
            $discountAmount = min($discountAmount, $subtotal);
        }
        $discountLabel = formatRupiah($voucher['discount_value']);
    }

    echo json_encode([
        'valid'           => true,
        'code'            => $voucher['code'],
        'discount_type'   => $voucher['discount_type'],
        'discount_value'  => (float)$voucher['discount_value'],
        'discount_amount' => $discountAmount,
        'min_purchase'    => $minPurchase,
        'max_discount'    => (float)($voucher['max_discount'] ?? 0),
        'message'         => '✅ Voucher ' . $voucher['code'] . ' berhasil diterapkan! (Potongan ' . $discountLabel . ')'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => 'Terjadi kesalahan sistem saat mengecek voucher: ' . $e->getMessage()
    ]);
}
