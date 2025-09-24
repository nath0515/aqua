<?php
require 'session.php'; // Start session, get user info
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'];
    $reason = $_POST['reason'];

    try {
        // Step 1: Get the order and associated user
        $stmt = $conn->prepare("
            SELECT u.user_id, u.firstname, u.lastname
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
        $customerName = $order['firstname'] . " " . $order['lastname'];

        // Step 2: Update order status to 'Cancelled'
        $update = $conn->prepare("
            UPDATE orders 
            SET status_id = (SELECT status_id FROM orderstatus WHERE status_name = 'Cancel') 
            WHERE order_id = :order_id
        ");
        $update->execute([':order_id' => $order_id]);

        // Step 3: Log cancellation activity for the user
        $notif = $conn->prepare("
            INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
            VALUES (:user_id, :message, 'orders.php', NOW(), 0)
        ");
        $notif->execute([
            ':user_id' => $user_id,
            ':message' => "Your order #$order_id has been cancelled. Reason: $reason"
        ]);

        // Step 4: Log cancellation activity for the admin
        $adminNotif = $conn->prepare("
            INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
            VALUES (0, :message, 'orders.php', NOW(), 0)
        ");
        $adminNotif->execute([
            ':message' => "Order #$order_id was cancelled by $customerName. Reason: $reason"
        ]);

        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
    }
}
?>
