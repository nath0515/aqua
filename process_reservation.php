<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require 'session.php';
require 'db.php';

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

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

    // Calculate total amount
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += floatval($item['price']) * intval($item['quantity']);
    }

    // Start transaction
    $conn->beginTransaction();

    // Insert into orders table
    $order_sql = "INSERT INTO orders (
        user_id, payment_id, location_id, delivery_date, status_id, amount, rider, proof_file, proofofpayment
    ) VALUES (
        :user_id, :payment_id, :location_id, :delivery_date, 7, :amount, 0, 1, 1
    )";
    
    $stmt = $conn->prepare($order_sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':payment_id' => $payment_id,
        ':location_id' => $location_id,
        ':delivery_date' => $delivery_date,
        ':amount' => $total_amount
    ]);

    $order_id = $conn->lastInsertId();

    // Optionally insert into order_items if you have such table
    // You can skip this part if not needed

    // Log activity
    $log_sql = "INSERT INTO activity_logs (user_id, message, destination, date, read_status)
                VALUES (:user_id, :message, :destination, NOW(), 0)";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->execute([
        ':user_id' => $user_id,
        ':message' => 'You reserved an order for delivery on ' . $delivery_date,
        ':destination' => 'costumer_orderdetails.php?id=' . $order_id
    ]);

    // Remove from cart
    $cart_ids = array_column($items, 'cart_id');
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $delete_stmt = $conn->prepare("DELETE FROM cart WHERE cart_id IN ($placeholders)");
    $delete_stmt->execute($cart_ids);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Reservation placed successfully!']);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reserve: ' . $e->getMessage()
    ]);
}
