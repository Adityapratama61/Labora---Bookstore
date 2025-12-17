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

$wishlist_id = isset($_POST['wishlist_id']) ? (int)$_POST['wishlist_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($wishlist_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid wishlist ID']);
    exit;
}

try {
    // Delete from wishlist (only if it belongs to user)
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->execute([$wishlist_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // Get updated wishlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wishlist_count = $stmt->fetchColumn();

        echo json_encode([
            'success' => true, 
            'message' => 'Buku dihapus dari wishlist',
            'wishlist_count' => $wishlist_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
