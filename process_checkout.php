<?php
header('Content-Type: application/json');
require 'session.php';
require 'db.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);
$user_id = $_SESSION['user_id'] ?? null;
$now = date('Y-m-d H:i:s');

if (!isset($data['items']) || !is_array($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'No items received']);
    exit;
}

$payment_id = isset($data['payment_id']) ? intval($data['payment_id']) : 0;
if ($payment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method selected']);
    exit;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $total_amount = 0;
    foreach ($data['items'] as $item) {
        $quantity = intval($item['quantity']);
        $with_container = intval($item['with_container']);
        $container_quantity = intval($item['container_quantity']);
        $product_id = intval($item['product_id']);

        $sql = "SELECT water_price, water_price_promo, container_price FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $prices = $stmt->fetch();

        if($quantity >= 10){
            $item_price = $prices['water_price_promo'];
        }
        else{
            $item_price = $prices['water_price'];
        }
        $container_price = $prices['container_price'];

        $item_total = $quantity * $item_price;
        if ($with_container) {
            $item_total += $container_quantity * $container_price;
        }
        $total_amount += $item_total;
    }
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO orders (date, amount, user_id, status_id, rider, payment_id) 
        VALUES (:now, :amount, :user_id, 1, 0, :payment_id)
    ");
    $stmt->execute([
        ':now' => $now,
        ':amount' => $total_amount,
        ':user_id' => $user_id,
        ':payment_id' => $payment_id
    ]);
    $order_id = $conn->lastInsertId();

    $item_stmt = $conn->prepare("
        INSERT INTO orderitems (order_id, product_id, quantity, with_container, container_quantity) 
        VALUES (:order_id, :product_id, :quantity, :with_container, :container_quantity)
    ");

    foreach ($data['items'] as $item) {
        $item_stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => intval($item['product_id']),
            ':quantity' => intval($item['quantity']),
            ':with_container' => intval($item['with_container']),
            ':container_quantity' => intval($item['container_quantity'])
        ]);
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = :cart_id");
    foreach($data['items'] as $item){
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Order processing failed: ' . $e->getMessage()]);
}
?>
