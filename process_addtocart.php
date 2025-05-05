<?php
header('Content-Type: application/json');

require 'db.php';

// Get POST data
$product_id = $_POST['product_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$unit_price = $_POST['unit_price'] ?? null;
$has_container = $_POST['has_container'] ?? 0;
$container_quantity = $_POST['container_quantity'] ?? 0;
$container_price = $_POST['container_price'] ?? 0;
$total_price = $_POST['total_price'] ?? null;

// Validation (basic)
if (!$product_id || !$quantity || !$unit_price || !$total_price) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO cart (product_id, quantity, unit_price, has_container, container_quantity, container_price, total_price)
        VALUES (:product_id, :quantity, :unit_price, :has_container, :container_quantity, :container_price, :total_price)
    ");

    $stmt->execute([
        ':product_id' => $product_id,
        ':quantity' => $quantity,
        ':unit_price' => $unit_price,
        ':has_container' => $has_container,
        ':container_quantity' => $container_quantity,
        ':container_price' => $container_price,
        ':total_price' => $total_price
    ]);

    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
