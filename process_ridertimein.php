<?php
require 'db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id or action']);
        exit;
    }

    $user_id = $data['user_id'];
    $action = $data['action'];

    // Determine which status to update
    if ($action == 'time_in') {
        $status = 1; // Example status for time-in
    } elseif ($action == 'time_out') {
        $status = 2; // Example status for time-out
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    // Update the rider status based on action
    $sql = "UPDATE rider_status SET time_in_status = :status WHERE user_id = :user_id AND DATE(date) = CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':status' => $status, ':user_id' => $user_id]);

    // Check if the update was successful
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
