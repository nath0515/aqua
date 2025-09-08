<?php
session_start();
require 'db.php';

echo "<h2>🧪 Test Rider Logout Salary Addition</h2>";

// Check if user is logged in as a rider
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo "<p>❌ Please login as a rider (role_id = 3) to test this functionality</p>";
    echo "<p><a href='login.php'>Login as Rider</a></p>";
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    // Check current attendance status
    echo "<h3>📅 Current Attendance Status</h3>";
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        echo "<ul>";
        echo "<li>Login Time: " . $attendance['in_time'] . "</li>";
        echo "<li>Logout Time: " . ($attendance['out_time'] ? $attendance['out_time'] : 'Not logged out yet') . "</li>";
        echo "</ul>";
        
        if ($attendance['out_time']) {
            echo "<p>✅ Already logged out today. Let's check if salary was added to expenses.</p>";
        } else {
            echo "<p>⏰ Currently logged in. Let's test the logout functionality.</p>";
        }
    } else {
        echo "<p>❌ No attendance record for today. Please login first.</p>";
        echo "<p><a href='attendance.php'>Go to Attendance Page</a></p>";
        exit();
    }
    
    // Check recent expenses for this rider
    echo "<h3>💸 Recent Salary Expenses</h3>";
    $stmt = $conn->prepare("SELECT * FROM expense WHERE expensetype_id = 2 AND comment LIKE '%" . $_SESSION['username'] . "%' ORDER BY date DESC LIMIT 5");
    $stmt->execute();
    $salary_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($salary_expenses) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Date</th><th>Comment</th><th>Amount</th></tr>";
        foreach ($salary_expenses as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['date'] . "</td>";
            echo "<td>" . htmlspecialchars($expense['comment']) . "</td>";
            echo "<td>₱" . number_format($expense['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No salary expenses found for this rider yet.</p>";
    }
    
    // Test the logout functionality
    if (!$attendance['out_time']) {
        echo "<h3>🧪 Test Logout (Add Salary to Expenses)</h3>";
        echo "<form method='POST' action='process_calendar_action.php' style='margin: 10px 0;'>";
        echo "<input type='hidden' name='action' value='logout'>";
        echo "<input type='hidden' name='date' value='$today'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px;'>Test Logout (Should Add Salary to Expenses)</button>";
        echo "</form>";
        
        echo "<p><strong>What should happen:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Logout time will be recorded</li>";
        echo "<li>✅ Daily salary will be calculated based on hours worked</li>";
        echo "<li>✅ Salary expense will be added to the expense table</li>";
        echo "<li>✅ Expense will appear in admin's expense dashboard</li>";
        echo "</ul>";
    }
    
    // Show all recent salary expenses
    echo "<h3>📊 All Recent Salary Expenses (Type ID = 2)</h3>";
    $stmt = $conn->prepare("SELECT * FROM expense WHERE expensetype_id = 2 ORDER BY date DESC LIMIT 10");
    $stmt->execute();
    $all_salary_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_salary_expenses) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Date</th><th>Comment</th><th>Amount</th></tr>";
        foreach ($all_salary_expenses as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['date'] . "</td>";
            echo "<td>" . htmlspecialchars($expense['comment']) . "</td>";
            echo "<td>₱" . number_format($expense['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No salary expenses found in the system yet.</p>";
    }
    
    echo "<p><a href='attendance.php'>View Attendance Page</a> | <a href='expenses.php'>View Expenses Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
