<?php
header('Content-Type: application/json');
require 'session.php';
require 'db.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'No product ID provided']);
    exit;
}

$cart_id = intval($data['cart_id']);
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    // Example: assuming you have a `cart` table with user_id and product_id
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id AND cart_id = :cart_id");
    $stmt->execute([
        ':user_id' => $user_id,
        ':cart_id' => $cart_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item: ' . $e->getMessage()]);
}
?>
