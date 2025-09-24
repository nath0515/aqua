<?php
require 'db.php';
session_start();

header('Content-Type: application/json'); // ensure JSON output only

try {
    if (!isset($_POST['order_id']) || !isset($_POST['reason'])) {
        throw new Exception("Missing required fields.");
    }

    $order_id = $_POST['order_id'];
    $reason   = trim($_POST['reason']);

    if ($reason === '') {
        throw new Exception("Cancellation reason cannot be empty.");
    }

    // Example: Update database
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', cancel_reason = :reason WHERE order_id = :order_id");
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cancelling order: ' . $e->getMessage()
    ]);
}
