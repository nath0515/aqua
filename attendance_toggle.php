<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'db.php';
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

// Get current status
$stmt = $conn->prepare("SELECT status FROM rider_status WHERE user_id = :user_id AND DATE(date) = :date");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':date', $today);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $insertStatus = $conn->prepare("INSERT INTO rider_status (user_id, status, date) VALUES (:user_id, 0, :now)");
    $insertStatus->bindParam(':user_id', $user_id);
    $insertStatus->bindParam(':now', $now);
    $insertStatus->execute();

    $status = 0;
    $date = $now;
} else {
    $status = $row['status'];
    $date = $row['date'];
}

if ($status == 1) {
    // Clock Out
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = 0, time_out = :now WHERE user_id = :user_id AND DATE(date) = :date");
    $updateStatus->bindParam(':now', $now);
    $updateStatus->bindParam(':user_id', $user_id);
    $updateStatus->bindParam(':date', $today);
    $updateStatus->execute();

    // Get the attendance record to calculate salary
    $attendance_stmt = $conn->prepare("SELECT in_time FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today AND out_time IS NULL");
    $attendance_stmt->bindParam(':user_id', $user_id);
    $attendance_stmt->bindParam(':today', $today);
    $attendance_stmt->execute();
    $attendance_record = $attendance_stmt->fetch(PDO::FETCH_ASSOC);

    $updateOut = $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND out_time IS NULL");
    $updateOut->bindParam(':out_time', $now);
    $updateOut->bindParam(':user_id', $user_id);
    $updateOut->execute();

    // Calculate daily salary and add to expenses if attendance record exists
    if ($attendance_record) {
        $salary_per_day = 500; // Daily rate
        $hourly_rate = $salary_per_day / 8; // Assuming 8-hour work day
        
        $time_in = strtotime($attendance_record['in_time']);
        $time_out = strtotime($now);
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
    }
} else {
    // Prevent multiple clock-ins after clocking out
    $checkAttendance = $conn->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today AND out_time IS NOT NULL");
    $checkAttendance->bindParam(':user_id', $user_id);
    $checkAttendance->bindParam(':today', $today);
    $checkAttendance->execute();
    $alreadyOut = $checkAttendance->fetch(PDO::FETCH_ASSOC);

    if ($alreadyOut) {
        echo "You have already clocked out today. Cannot clock in again.";
        exit();
    }

    // Clock In
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, time_in = :now WHERE user_id = :user_id AND DATE(date) = :date");
    $updateStatus->bindParam(':now', $now);
    $updateStatus->bindParam(':user_id', $user_id);
    $updateStatus->bindParam(':date', $today);
    $updateStatus->execute();

    $insertIn = $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)");
    $insertIn->bindParam(':user_id', $user_id);
    $insertIn->bindParam(':in_time', $now);
    $insertIn->execute();
}

header('Location: riderdashboard.php');
exit();
?>
