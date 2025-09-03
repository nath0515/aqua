<?php
require 'session.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'];
    $reason = $_POST['reason'];

    try {
        $stmt = $conn->prepare("
            SELECT u.user_id 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Order not found or user not found.");
        }

        $user_id = $order['user_id'];

        $update = $conn->prepare("
            UPDATE orders 
            SET status_id = (SELECT status_id FROM orderstatus WHERE status_name = 'Cancel') 
            WHERE order_id = :order_id
        ");
        $update->execute([':order_id' => $order_id]);

        $notif = $conn->prepare("
            INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
            VALUES (:user_id, :message, 'orders.php', NOW(), 0)
        ");
        $notif->execute([
            ':user_id' => $user_id,
            ':message' => "Your order #$order_id has been cancelled. Reason: $reason"
        ]);

        echo "success";
    } catch (Exception $e) {
        http_response_code(500);
        echo "error: " . $e->getMessage();
    }
}
?>
