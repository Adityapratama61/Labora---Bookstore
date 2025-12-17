<?php
require_once '../../config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = isset($_POST['code']) ? trim(strtoupper($_POST['code'])) : '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Kode promo tidak boleh kosong']);
    exit;
}

try {
    // Check if promo code exists and is valid
    $stmt = $pdo->prepare("
        SELECT * FROM promo_codes 
        WHERE code = ? 
        AND is_active = 1 
        AND (start_date IS NULL OR start_date <= CURDATE())
        AND (end_date IS NULL OR end_date >= CURDATE())
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Kode promo tidak valid atau sudah kadaluarsa']);
        exit;
    }

    // Calculate cart total
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT SUM(
            CASE 
                WHEN b.discount_price IS NOT NULL THEN b.discount_price * c.quantity
                ELSE b.price * c.quantity
            END
        ) as total
        FROM cart c
        JOIN books b ON c.book_id = b.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_total = $stmt->fetchColumn() ?: 0;

    // Check minimum purchase
    if ($cart_total < $promo['min_purchase']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum pembelian Rp ' . number_format($promo['min_purchase'], 0, ',', '.') . ' untuk menggunakan kode ini'
        ]);
        exit;
    }

    // Calculate discount
    if ($promo['discount_type'] === 'percentage') {
        $discount = $cart_total * ($promo['discount_value'] / 100);
        if ($promo['max_discount'] && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        $discount = $promo['discount_value'];
    }

    // Save promo code to session
    $_SESSION['applied_promo'] = [
        'code' => $promo['code'],
        'discount' => $discount,
        'description' => $promo['description']
    ];

    echo json_encode([
        'success' => true, 
        'message' => 'Kode promo berhasil diterapkan!',
        'discount' => $discount,
        'discount_formatted' => number_format($discount, 0, ',', '.'),
        'description' => $promo['description']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
