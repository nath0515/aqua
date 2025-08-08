<?php
// Prevent any output before JSON
ob_start();

// Suppress warnings and notices that might cause output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require 'session.php';
require 'db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Delivery process started for order: " . ($_POST['order_id'] ?? 'unknown'));

// Clear any output buffer
ob_clean();

header('Content-Type: application/json');

// Check if user is logged in and is a rider
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$rider_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? null;
$delivery_notes = $_POST['delivery_notes'] ?? '';

// Validate inputs
if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

// Check if proof of delivery file was uploaded
if (!isset($_FILES['proof_of_delivery']) || $_FILES['proof_of_delivery']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Proof of delivery image is required']);
    exit;
}

try {
    error_log("Checking order $order_id for rider $rider_id");
    
    // Check if order exists and is assigned to this rider
    $sql = "SELECT o.order_id, o.status_id, o.user_id, os.status_name 
            FROM orders o 
            JOIN orderstatus os ON o.status_id = os.status_id 
            WHERE o.order_id = :order_id AND o.rider = :rider_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':rider_id', $rider_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        error_log("Order $order_id not found or not assigned to rider $rider_id");
        echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
        exit;
    }
    
    error_log("Order found: " . json_encode($order));

    // Check if order is already delivered
    if ($order['status_name'] === 'Delivered' || $order['status_name'] === 'Completed') {
        echo json_encode(['success' => false, 'message' => 'Order is already delivered']);
        exit;
    }

    // Handle file upload
    $file = $_FILES['proof_of_delivery'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
        exit;
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'proof_delivery_' . $order_id . '_' . time() . '.' . $file_extension;
    $upload_path = 'uploads/' . $filename;

    // Move uploaded file
    error_log("Attempting to move uploaded file to: $upload_path");
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to $upload_path");
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }
    error_log("File uploaded successfully to: $upload_path");

    // Start transaction
    $conn->beginTransaction();

    // Update order status to Delivered (status_id = 4)
    error_log("Updating order status to delivered");
    $update_sql = "UPDATE orders SET status_id = 4, proof_file = :proof_path WHERE order_id = :order_id";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':proof_path', $upload_path);
    $update_stmt->bindParam(':order_id', $order_id);
    $update_stmt->execute();
    error_log("Order status updated successfully");

    // Add delivery notes if provided
    if (!empty($delivery_notes)) {
        // You might want to store delivery notes in a separate table or add a column to orders table
        // For now, we'll just log it
        error_log("Delivery notes for order $order_id: $delivery_notes");
    }

    // Create notification for customer about delivery
    $customer_sql = "SELECT firstname, lastname FROM user_details WHERE user_id = :user_id";
    $customer_stmt = $conn->prepare($customer_sql);
    $customer_stmt->bindParam(':user_id', $order['user_id']);
    $customer_stmt->execute();
    $customer_data = $customer_stmt->fetch();

    $customer_name = '';
    if ($customer_data) {
        $customer_name = $customer_data['firstname'] . ' ' . $customer_data['lastname'];
    }

    $notification_message = "Order #$order_id has been delivered successfully! Thank you for choosing AquaDrop.";
    $now = date('Y-m-d H:i:s');

    $notification_sql = "INSERT INTO activity_logs (message, date, destination, user_id) VALUES (:message, :date, 'costumer_orderdetails.php?id=$order_id', :user_id)";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->execute([
        ':message' => $notification_message,
        ':date' => $now,
        ':user_id' => $order['user_id']
    ]);

    // Commit transaction
    $conn->commit();

    // Clear any output and send JSON response
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Order marked as delivered successfully']);

} catch(PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Delete uploaded file if it exists
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    // Clear any output and send JSON response
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Delete uploaded file if it exists
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    // Clear any output and send JSON response
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// End output buffering and send response
ob_end_flush();
?> 