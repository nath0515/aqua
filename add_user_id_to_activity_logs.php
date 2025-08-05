<?php
require 'db.php';

try {
    // Add user_id column to activity_logs table
    $sql = "ALTER TABLE activity_logs ADD COLUMN user_id INT NULL AFTER destination";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    echo "✅ Successfully added user_id column to activity_logs table!";
    
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✅ user_id column already exists in activity_logs table!";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
?> 