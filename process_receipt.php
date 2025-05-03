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
    $now = $data['now'];

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

    // Prepare insert for order_items
    $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, has_container, container_quantity, container_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

    foreach ($receipt as $item) {
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['unit_price'],
            $item['quantity'],
            $item['has_container'],
            $item['container_quantity'],
            $item['container_price'],
            $item['total_price']
        ]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback if something fails
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
