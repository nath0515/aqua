<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require 'session.php';
require 'db.php';

try {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Validate required fields
    if (
        !isset($_POST['items']) || 
        !isset($_POST['payment_id']) || 
        !isset($_POST['location_id']) || 
        !isset($_POST['delivery_date'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $items = json_decode($_POST['items'], true);
    $payment_id = (int) $_POST['payment_id'];
    $location_id = (int) $_POST['location_id'];
    $delivery_date = $_POST['delivery_date'];

    if (empty($items) || $payment_id === 0 || $location_id === 0 || empty($delivery_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid reservation data.']);
        exit;
    }

    // Start transaction
    $conn->beginTransaction();

    // Insert into orders table
    $order_sql = "INSERT INTO orders (user_id, payment_id, location_id, delivery_date, status_id)
                  VALUES (:user_id, :payment_id, :location_id, :delivery_date, 7)";
    $stmt = $conn->prepare($order_sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':payment_id' => $payment_id,
        ':location_id' => $location_id,
        ':delivery_date' => $delivery_date
    ]);

    $order_id = $conn->lastInsertId();

    // Insert order items
    $order_item_sql = "INSERT INTO orders (order_id, product_id, quantity, with_container, container_quantity)
                       VALUES (:order_id, :product_id, :quantity, :with_container, :container_quantity)";
    $item_stmt = $conn->prepare($order_item_sql);

    foreach ($items as $item) {
        $item_stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':with_container' => $item['with_container'],
            ':container_quantity' => $item['container_quantity']
        ]);
    }

    // Optionally log activity
    $log_sql = "INSERT INTO activity_logs (user_id, message, destination, date, read_status)
                VALUES (:user_id, :message, :destination, NOW(), 0)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->execute([
        ':user_id' => $user_id,
        ':message' => 'You reserved an order for delivery on ' . $delivery_date,
        ':destination' => 'costumer_orderdetails.php?id=' . $order_id
    ]);

    // Delete reserved items from cart
    $cart_ids = array_column($items, 'cart_id');
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $delete_stmt = $conn->prepare("DELETE FROM cart WHERE cart_id IN ($placeholders)");
    $delete_stmt->execute($cart_ids);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Reservation placed successfully!']);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reserve: ' . $e->getMessage()
    ]);
}
