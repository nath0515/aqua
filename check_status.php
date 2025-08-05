<?php
require 'db.php';

echo "<h2>Check Order Status</h2>";

try {
    // Check what status_id 4 means
    $sql = "SELECT status_id, status_name FROM orderstatus WHERE status_id = 4";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($status) {
        echo "Status ID 4 = " . $status['status_name'] . "<br>";
    }
    
    // List all statuses
    $sql = "SELECT status_id, status_name FROM orderstatus ORDER BY status_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<br>All statuses:<br>";
    foreach($statuses as $s) {
        echo "ID " . $s['status_id'] . " = " . $s['status_name'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 