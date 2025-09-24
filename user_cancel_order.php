<?php
require 'session.php'; // Start session, get user info
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $order_id = $_POST['order_id'];
    $reason = $_POST['reason'];

    try {
        // Step 1: Get the order and associated user
        $stmt = $conn->prepare("
            SELECT u.user_id 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no order or user is found, throw an exception
        if (!$order) {
            throw new Exception("Order not found or user not found.");
        }

        // Get user_id from the order
        $user_id = $order['user_id'];

        // Step 2: Update order status to 'Cancelled'
        $update = $conn->prepare("
            UPDATE orders 
            SET status_id = (SELECT status_id FROM orderstatus WHERE status_name = 'Cancel') 
            WHERE order_id = :order_id
        ");
        $update->execute([':order_id' => $order_id]);

        // Step 3: Log the cancellation activity for the user
        $notif = $conn->prepare("
            INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
            VALUES (:user_id, :message, 'orders.php', NOW(), 0)
        ");
        $notif->execute([
            ':user_id' => $user_id,
            ':message' => "Your order #$order_id has been cancelled. Reason: $reason"
        ]);

        // Success response
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);
        
    } catch (Exception $e) {
        // If an error occurs, send an error response
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
    }
}
?>
