<?php
require 'session.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'];
    $reason = $_POST['reason'];

    try {
        // Get user_id for notification
        $stmt = $conn->prepare("SELECT user_id FROM orders WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $order['user_id'];

        // Update order status
        $update = $conn->prepare("UPDATE orders SET status_id = (SELECT status_id FROM orderstatus WHERE status_name = 'Cancel') WHERE order_id = :order_id");
        $update->execute([':order_id' => $order_id]);

        // Add notification for user
        $notif = $conn->prepare("INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
                                 VALUES (:user_id, :message, 'orders.php', NOW(), 0)");
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
