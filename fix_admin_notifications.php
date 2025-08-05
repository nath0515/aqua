<?php
require 'db.php';

try {
    // Update any existing admin notifications with wrong destination
    $sql = "UPDATE activity_logs SET destination = 'orders.php' WHERE destination = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $affected_rows = $stmt->rowCount();
    
    if ($affected_rows > 0) {
        echo "✅ Successfully updated $affected_rows admin notifications to redirect to orders.php\n";
    } else {
        echo "✅ No admin notifications found with wrong destination\n";
    }
    
    // Show current admin notifications
    $sql = "SELECT * FROM activity_logs WHERE destination = 'orders.php' ORDER BY date DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    echo "\n📋 Current admin notifications:\n";
    foreach ($notifications as $notification) {
        echo "- " . $notification['message'] . " (Destination: " . $notification['destination'] . ")\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 