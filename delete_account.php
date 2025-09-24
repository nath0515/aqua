<?php
require 'db.php';
require 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'No user ID provided.']);
        exit;
    }

    try {
        // Optional: Prevent deleting yourself
        if ($_SESSION['user_id'] == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
            exit;
        }

        // Optional: Check permissions
        if ($_SESSION['role_id'] != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit;
        }

        // Delete from user_details first (due to foreign key)
        $stmt1 = $conn->prepare("DELETE FROM user_details WHERE user_id = :user_id");
        $stmt1->bindParam(':user_id', $user_id);
        $stmt1->execute();

        // Then delete from users table
        $stmt2 = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt2->bindParam(':user_id', $user_id);
        $stmt2->execute();

        echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
