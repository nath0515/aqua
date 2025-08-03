<?php
require 'db.php';
require 'session.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $now = date('Y-m-d H:i:s');
    $date = date('Y-m-d');

    if (!isset($data['user_id']) || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id or action']);
        exit;
    }

    $user_id = $data['user_id'];
    $action = $data['action'];

    // Determine which status to update
    if ($action == 'time_in') {
        $sql = "UPDATE rider_status SET time_in_status = :time_status, status = :status, time_in = :time WHERE user_id = :user_id AND DATE(date) = :date";
        $status = 1;
    } elseif ($action == 'time_out') {
        $sql = "UPDATE rider_status SET time_out_status = :time_status, status = :status, time_out = :time WHERE user_id = :user_id AND DATE(date) = :date";
        $status = 0;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([':time_status' => 1, ':status' => $status, ':time' => $now, ':user_id' => $user_id, ':date' => $date]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
