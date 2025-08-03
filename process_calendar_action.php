<?php
session_start();
require 'db.php';

// Check if user is logged in and is a rider
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    date_default_timezone_set('Asia/Manila'); // Set to Philippine timezone
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $current_time = date('Y-m-d H:i:s');
    
    try {
        switch ($action) {
            case 'login':
                // Check if already logged in today
                $stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :date");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing record
                    $stmt = $conn->prepare("UPDATE attendance SET in_time = :in_time WHERE user_id = :user_id AND DATE(in_time) = :date");
                    $stmt->bindParam(':in_time', $current_time);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':date', $date);
                } else {
                    // Create new record
                    $stmt = $conn->prepare("INSERT INTO attendance (user_id, in_time, status) VALUES (:user_id, :in_time, 'present')");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':in_time', $current_time);
                }
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Login recorded successfully', 'time' => date('g:i A')]);
                break;
                
            case 'logout':
                // Check if logged in today
                $stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :date");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update logout time
                    $stmt = $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND DATE(in_time) = :date");
                    $stmt->bindParam(':out_time', $current_time);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':date', $date);
                    $stmt->execute();
                    
                    echo json_encode(['success' => true, 'message' => 'Logout recorded successfully', 'time' => date('g:i A')]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No login record found for today']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (PDOException $e) {
        error_log("Calendar action error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 