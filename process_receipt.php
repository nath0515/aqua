<?php
// process_receipt.php
header('Content-Type: application/json');
require 'db.php';
require 'session.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['receipt'], $data['total_price'], $data['payment_method'], $data['now'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
        exit;
    }

    $receipt = $data['receipt'];
    $totalPrice = $data['total_price'];
    $paymentMethod = $data['payment_method'];
    $now = date("Y-m-d H:i:s");

    $conn->beginTransaction();

    $stmt = $conn->prepare('INSERT INTO orders (amount, payment_id, date, user_id, status_id) VALUES (:amount, :payment_id, :date, :user_id, :status_id)');
    $stmt->execute([
        ':amount' => $totalPrice,
        ':payment_id' => $paymentMethod,
        ':date' => $now,
        ':user_id' => 31,
        ':status_id' => 5
    ]);

    $orderId = $conn->lastInsertId();

    $itemStmt = $conn->prepare('INSERT INTO orderitems 
    (order_id, product_id, quantity, with_container, container_quantity) 
    VALUES 
    (:order_id, :product_id, :quantity, :with_container, :container_quantity)');

    $selectStmt = $conn->prepare('SELECT stock FROM products WHERE product_id = :product_id');

    $updateStmt = $conn->prepare('UPDATE products SET stock = :stock WHERE product_id = :product_id');

    foreach ($receipt as $item) {
        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':with_container' => $item['has_container'],
            ':container_quantity' => $item['container_quantity']
        ]);

        $selectStmt->execute([':product_id' => $item['product_id']]);
        $currentStock = $selectStmt->fetchColumn();
        $newStock = $currentStock - $item['container_quantity'];

        $updateStmt->execute(
            ':stock' => $newStock,
            ':product_id' => $item['product_id']
        );
    }

    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
