<?php
session_start();
require 'db.php';

echo "<h2>Calendar Login Test</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Please login first</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>✅ User ID: $user_id</p>";
echo "<p>✅ Role ID: " . $_SESSION['role_id'] . "</p>";

try {
    // Check rider status
    $stmt = $conn->prepare("SELECT status FROM rider_status WHERE user_id = :user_id AND DATE(date) = CURDATE()");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $rider_status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>🚗 Rider Status: " . ($rider_status ? ($rider_status['status'] ? 'On Duty' : 'Off Duty') : 'No status record') . "</p>";
    
    // Check today's attendance
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>📅 Today's Attendance: " . ($attendance ? 'Found' : 'None') . "</p>";
    if ($attendance) {
        echo "<ul>";
        echo "<li>Login Time: " . $attendance['in_time'] . "</li>";
        echo "<li>Logout Time: " . ($attendance['out_time'] ? $attendance['out_time'] : 'Not logged out') . "</li>";
        echo "</ul>";
    }
    
    // Test the process_calendar_action.php directly
    echo "<h3>🧪 Test Calendar Action</h3>";
    echo "<form method='POST' action='process_calendar_action.php'>";
    echo "<input type='hidden' name='action' value='login'>";
    echo "<input type='hidden' name='date' value='$today'>";
    echo "<button type='submit'>Test Login Action</button>";
    echo "</form>";
    
    echo "<form method='POST' action='process_calendar_action.php'>";
    echo "<input type='hidden' name='action' value='logout'>";
    echo "<input type='hidden' name='date' value='$today'>";
    echo "<button type='submit'>Test Logout Action</button>";
    echo "</form>";
    
    // Check if process_calendar_action.php exists
    if (file_exists('process_calendar_action.php')) {
        echo "<p>✅ process_calendar_action.php exists</p>";
    } else {
        echo "<p>❌ process_calendar_action.php missing!</p>";
    }
    
    // Check attendance table structure
    $stmt = $conn->prepare("DESCRIBE attendance");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>📋 Attendance table columns:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 