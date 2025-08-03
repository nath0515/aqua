<?php
session_start();
require 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Calendar Debug Test</h2>";

try {
    // Test 1: Check if we can connect to database
    echo "<p>✅ Database connection: OK</p>";
    
    // Test 2: Check if attendance table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'attendance'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Attendance table exists</p>";
    } else {
        echo "<p>❌ Attendance table does NOT exist</p>";
    }
    
    // Test 3: Check attendance table structure
    $stmt = $conn->prepare("DESCRIBE attendance");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>📋 Attendance table columns:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Test 4: Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User logged in: ID {$_SESSION['user_id']}</p>";
    } else {
        echo "<p>❌ User not logged in</p>";
    }
    
    // Test 5: Check user role
    if (isset($_SESSION['role_id'])) {
        echo "<p>✅ User role: {$_SESSION['role_id']}</p>";
    } else {
        echo "<p>❌ User role not set</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ General error: " . $e->getMessage() . "</p>";
}
?> 