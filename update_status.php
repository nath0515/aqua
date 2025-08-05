<?php
require 'db.php';

if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    // Prepare the update query
    $query = "UPDATE orders SET status_id = (SELECT status_id FROM orderstatus WHERE status_name = :new_status) WHERE order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":new_status", $new_status);
    $stmt->bindParam(":order_id", $order_id);

    // Execute the query
    if ($stmt->execute()) {
        // Get order details for customer notification
        $order_sql = "SELECT o.user_id, o.amount, ud.firstname, ud.lastname 
                     FROM orders o 
                     JOIN user_details ud ON o.user_id = ud.user_id 
                     WHERE o.order_id = :order_id";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bindParam(':order_id', $order_id);
        $order_stmt->execute();
        $order_data = $order_stmt->fetch();
        
        // Create notification for customer about order status change
        if ($order_data) {
            $customer_name = $order_data['firstname'] . ' ' . $order_data['lastname'];
            $amount = number_format($order_data['amount'], 2);
            
            $customer_notification_message = "Order #$order_id status updated to: $new_status - Amount: ₱$amount";
            $now = date('Y-m-d H:i:s');
            
            $customer_notification_sql = "INSERT INTO activity_logs (message, date, destination, user_id) VALUES (:message, :date, 'costumer_orderdetails.php?id=$order_id', :user_id)";
            $customer_notification_stmt = $conn->prepare($customer_notification_sql);
            $customer_notification_stmt->execute([
                ':message' => $customer_notification_message,
                ':date' => $now,
                ':user_id' => $order_data['user_id']
            ]);
        }
        
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
