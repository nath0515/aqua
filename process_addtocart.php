<?php
header('Content-Type: application/json');

require 'db.php';
require 'session.php';

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$with_container = $_POST['has_container'] ?? 0;
$container_quantity = $_POST['container_quantity'] ?? 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO cart (product_id, user_id, quantity, with_container, container_quantity)
        VALUES (:product_id, :user_id, :quantity, :with_container, :container_quantity)
    ");

    $stmt->execute([
        ':product_id' => $product_id,
        ':user_id' => $user_id,
        ':quantity' => $quantity,
        ':with_container' => $with_container,
        ':container_quantity' => $container_quantity
    ]);

    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
