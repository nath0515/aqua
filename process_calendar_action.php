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
                    $stmt = $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':in_time', $current_time);
                }
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Login recorded successfully', 'time' => date('g:i A')]);
                break;
                
            case 'logout':
                // Check if logged in today
                $stmt = $conn->prepare("SELECT attendance_id, in_time FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :date");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $attendance_record = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Update logout time
                    $stmt = $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND DATE(in_time) = :date");
                    $stmt->bindParam(':out_time', $current_time);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':date', $date);
                    $stmt->execute();
                    
                    // Calculate daily salary and add to expenses
                    $salary_per_day = 500; // Daily rate
                    $hourly_rate = $salary_per_day / 8; // Assuming 8-hour work day
                    
                    $time_in = strtotime($attendance_record['in_time']);
                    $time_out = strtotime($current_time);
                    $hours_worked = ($time_out - $time_in) / 3600; // Convert seconds to hours
                    $daily_salary = $hours_worked * $hourly_rate;
                    
                    // Get rider details for expense comment
                    $rider_stmt = $conn->prepare("SELECT u.username, ud.firstname, ud.lastname FROM users u 
                                                JOIN user_details ud ON u.user_id = ud.user_id 
                                                WHERE u.user_id = :user_id");
                    $rider_stmt->bindParam(':user_id', $user_id);
                    $rider_stmt->execute();
                    $rider_data = $rider_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $rider_name = $rider_data['firstname'] . ' ' . $rider_data['lastname'];
                    $comment = "Daily salary for " . $rider_name . " - " . number_format($hours_worked, 2) . " hours worked";
                    
                    // Add salary to expenses (Salary expense type ID = 2)
                    $expense_stmt = $conn->prepare("INSERT INTO expense (expensetype_id, comment, amount) VALUES (2, :comment, :amount)");
                    $expense_stmt->bindParam(':comment', $comment);
                    $expense_stmt->bindParam(':amount', $daily_salary);
                    $expense_stmt->execute();
                    
                    echo json_encode(['success' => true, 'message' => 'Logout recorded successfully', 'time' => date('g:i A'), 'salary_added' => $daily_salary]);
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