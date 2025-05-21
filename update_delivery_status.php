<?php
require 'session.php';
require 'db.php';

$now = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Optional: validate that the user is a rider
    $sql = "UPDATE orders 
            SET status_id = 4, updated_at = date()
            WHERE user_id = :user_id AND status_id = 3
            ORDER BY order_id ASC LIMIT 1";  // deliver one at a time

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update order.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}
?>
