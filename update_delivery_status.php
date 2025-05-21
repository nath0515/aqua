<?php
require 'session.php';
require 'db.php';

$now = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Missing order_id']);
        exit;
    }

    // Optional: Verify that this order belongs to the logged-in rider's delivery batch
    $sql = "UPDATE orders 
            SET status_id = 4, date = :date
            WHERE order_id = :order_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':date', $now);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update order.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}
?>
