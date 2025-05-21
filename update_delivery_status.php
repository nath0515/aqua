<?php
require 'session.php';
require 'db.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    // Handle file upload if present
    $uploadDir = 'uploads/';
    $filePath = null;

    if (!empty($_FILES['file']['name'])) {
        $fileName = basename($_FILES['file']['name']);
        $targetFile = $uploadDir . time() . "_" . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $filePath = $targetFile; // Save this to DB if needed
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload file.']);
            exit;
        }
    }

    // Update order status
    $sql = "UPDATE orders 
            SET status_id = 4, date = :date
            WHERE order_id = :order_id AND user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $now);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        // Optional: Save file path to `proof_file` column if you have one
        if ($filePath) {
            $proofSql = "UPDATE orders SET proof_file = :proof WHERE order_id = :order_id";
            $proofStmt = $conn->prepare($proofSql);
            $proofStmt->bindParam(':proof', $filePath);
            $proofStmt->bindParam(':order_id', $order_id);
            $proofStmt->execute();
        }

        $response['success'] = true;
    } else {
        $response['error'] = 'Database update failed.';
    }
} else {
    $response['error'] = 'Invalid request method.';
}

echo json_encode($response);
