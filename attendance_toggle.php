<?php
require 'db.php';
require 'session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['id'], $_POST['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$attendance_id = $_POST['id'];
$type = $_POST['type']; // 'in' or 'out'
$now = date('Y-m-d H:i:s');

try {
    if ($type === 'in') {
        // Update time_in
        $stmt = $conn->prepare("UPDATE attendance SET time_in = :now WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':now', $now);
        $stmt->bindParam(':id', $attendance_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Time In recorded']);
    } elseif ($type === 'out') {
        // Update time_out
        $stmt = $conn->prepare("UPDATE attendance SET time_out = :now WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':now', $now);
        $stmt->bindParam(':id', $attendance_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Time Out recorded']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
