<?php
require 'session.php'; // Start session, get user info
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? null;
$reason = trim($_POST['reason'] ?? '');

if (empty($order_id) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Order ID and cancellation reason are required.']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if order exists and belongs to the user
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = :order_id AND user_id = :user_id");
    $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or permission denied.']);
        exit;
    }

    // Update order status to Cancelled
    $update = $conn->prepare("UPDATE orders SET status_name = 'Cancelled' WHERE order_id = :order_id");
    $update->execute([':order_id' => $order_id]);

    // Notify the shop owner/admin
    $adminStmt = $conn->prepare("SELECT user_id FROM users WHERE role_id = 1");
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($admins as $admin) {
        $notify = $conn->prepare("INSERT INTO notifications (user_id, message, date, read_status) VALUES (:user_id, :message, NOW(), 0)");
        $notify->execute([
            ':user_id' => $admin['user_id'],
            ':message' => "Order #$order_id was cancelled by user ID $user_id. Reason: $reason"
        ]);
    }

    // Log activity for the user
    $log = $conn->prepare("
        INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
        VALUES (:user_id, :message, 'orders.php', NOW(), 0)
    ");
    $log->execute([
        ':user_id' => $user_id,
        ':message' => "You cancelled order #$order_id. Reason: $reason"
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);

} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollBack();
    error_log("Error cancelling order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
}

