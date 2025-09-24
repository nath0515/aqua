<?php
require 'session.php';  // Start session and get $_SESSION['user_id']
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
    // 1. Check if order exists and belongs to the user
    $stmt = $conn->prepare("SELECT order_id, status_id FROM orders WHERE order_id = :order_id AND user_id = :user_id");
    $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or permission denied.']);
        exit;
    }

    // 2. Get cancel status_id
    $cancelStatusStmt = $conn->prepare("SELECT status_id FROM orderstatus WHERE status_name = 'Cancel' LIMIT 1");
    $cancelStatusStmt->execute();
    $cancelStatusId = $cancelStatusStmt->fetchColumn();

    if (!$cancelStatusId) {
        echo json_encode(['success' => false, 'message' => 'Cancel status not found in the system.']);
        exit;
    }

    // 3. Check if order is already cancelled
    if ($order['status_id'] == $cancelStatusId) {
        echo json_encode(['success' => false, 'message' => 'Order is already cancelled.']);
        exit;
    }

    // 4. Update order status to Cancel
    $update = $conn->prepare("UPDATE orders SET status_id = :cancelStatusId WHERE order_id = :order_id");
    $update->execute([':cancelStatusId' => $cancelStatusId, ':order_id' => $order_id]);

    // 5. Log cancellation activity
    $log = $conn->prepare("
        INSERT INTO activity_logs (user_id, message, destination, date, read_status) 
        VALUES (:user_id, :message, 'orders.php', NOW(), 0)
    ");
    $log->execute([
        ':user_id' => $user_id,
        ':message' => "You cancelled order #$order_id. Reason: $reason"
    ]);

    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
}
