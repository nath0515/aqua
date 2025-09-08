<?php
session_start();
require 'db.php';

echo "<h2>🧪 Test Salary Expense Integration</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Please login first</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

echo "<p>✅ User ID: $user_id</p>";
echo "<p>✅ Role ID: $role_id</p>";

if ($role_id != 3) {
    echo "<p>❌ This test is for riders only (role_id = 3)</p>";
    exit();
}

try {
    // Check current attendance status
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📅 Current Attendance Status</h3>";
    if ($attendance) {
        echo "<ul>";
        echo "<li>Login Time: " . $attendance['in_time'] . "</li>";
        echo "<li>Logout Time: " . ($attendance['out_time'] ? $attendance['out_time'] : 'Not logged out yet') . "</li>";
        echo "</ul>";
        
        if ($attendance['out_time']) {
            // Calculate what the salary would be
            $salary_per_day = 500;
            $hourly_rate = $salary_per_day / 8;
            $time_in = strtotime($attendance['in_time']);
            $time_out = strtotime($attendance['out_time']);
            $hours_worked = ($time_out - $time_in) / 3600;
            $daily_salary = $hours_worked * $hourly_rate;
            
            echo "<p>💰 Calculated Salary: ₱" . number_format($daily_salary, 2) . " (" . number_format($hours_worked, 2) . " hours)</p>";
        }
    } else {
        echo "<p>No attendance record for today</p>";
    }
    
    // Check recent expenses
    echo "<h3>💸 Recent Salary Expenses</h3>";
    $stmt = $conn->prepare("SELECT * FROM expense WHERE expensetype_id = 2 ORDER BY date DESC LIMIT 5");
    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($expenses) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Date</th><th>Comment</th><th>Amount</th></tr>";
        foreach ($expenses as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['date'] . "</td>";
            echo "<td>" . htmlspecialchars($expense['comment']) . "</td>";
            echo "<td>₱" . number_format($expense['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No salary expenses found</p>";
    }
    
    // Test buttons
    echo "<h3>🧪 Test Actions</h3>";
    echo "<form method='POST' action='process_calendar_action.php' style='display: inline-block; margin: 5px;'>";
    echo "<input type='hidden' name='action' value='login'>";
    echo "<input type='hidden' name='date' value='$today'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px;'>Test Login</button>";
    echo "</form>";
    
    echo "<form method='POST' action='process_calendar_action.php' style='display: inline-block; margin: 5px;'>";
    echo "<input type='hidden' name='action' value='logout'>";
    echo "<input type='hidden' name='date' value='$today'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px;'>Test Logout (Add Salary to Expenses)</button>";
    echo "</form>";
    
    echo "<br><br>";
    echo "<a href='attendance.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>View Attendance Page</a>";
    echo " ";
    echo "<a href='expenses.php' style='padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>View Expenses Page</a>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
