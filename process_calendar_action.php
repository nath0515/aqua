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
                $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :date");
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
                    $stmt = $conn->prepare("INSERT INTO attendance (user_id, in_time, status, created_at) VALUES (:user_id, :in_time, 'present', :created_at)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':in_time', $current_time);
                    $stmt->bindParam(':created_at', $current_time);
                }
                $stmt->execute();
                
                // Log activity
                $activity_sql = "INSERT INTO activity_logs (user_id, action, description, message, destination, date, created_at) VALUES (:user_id, 'login', 'Rider logged in', 'Logged in at " . date('g:i A') . "', 'calendar.php', :date, :created_at)";
                $activity_stmt = $conn->prepare($activity_sql);
                $activity_stmt->bindParam(':user_id', $user_id);
                $activity_stmt->bindParam(':date', $date);
                $activity_stmt->bindParam(':created_at', $current_time);
                $activity_stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Login recorded successfully']);
                break;
                
            case 'logout':
                // Check if logged in today
                $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :date");
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
                    
                    // Log activity
                    $activity_sql = "INSERT INTO activity_logs (user_id, action, description, message, destination, date, created_at) VALUES (:user_id, 'logout', 'Rider logged out', 'Logged out at " . date('g:i A') . "', 'calendar.php', :date, :created_at)";
                    $activity_stmt = $conn->prepare($activity_sql);
                    $activity_stmt->bindParam(':user_id', $user_id);
                    $activity_stmt->bindParam(':date', $date);
                    $activity_stmt->bindParam(':created_at', $current_time);
                    $activity_stmt->execute();
                    
                    echo json_encode(['success' => true, 'message' => 'Logout recorded successfully']);
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
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 