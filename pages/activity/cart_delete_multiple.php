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

$input = json_decode(file_get_contents('php://input'), true);
$ids = isset($input['ids']) ? $input['ids'] : [];
$user_id = $_SESSION['user_id'];

if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

try {
    // Sanitize IDs
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Delete items (only if they belong to user)
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id IN ($placeholders) AND user_id = ?");
    $params = array_merge($ids, [$user_id]);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Items deleted', 'deleted_count' => $stmt->rowCount()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No items deleted']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
