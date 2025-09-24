<?php
require 'db.php';
require 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $admin_password = $_POST['admin_password'] ?? '';

    if (!$user_id || !$admin_password) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    // Get admin's current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :admin_id");
    $stmt->bindParam(':admin_id', $_SESSION['user_id']);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($admin_password, $admin['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
        exit;
    }

    // Prevent deleting yourself
    if ($_SESSION['user_id'] == $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
        exit;
    }

    // Check admin role
    if ($_SESSION['role_id'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit;
    }

    try {
        // Delete from user_details
        $stmt1 = $conn->prepare("DELETE FROM user_details WHERE user_id = :user_id");
        $stmt1->bindParam(':user_id', $user_id);
        $stmt1->execute();

        // Delete from users
        $stmt2 = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt2->bindParam(':user_id', $user_id);
        $stmt2->execute();

        echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
