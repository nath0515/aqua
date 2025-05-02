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
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
