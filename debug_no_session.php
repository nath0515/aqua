<?php
require 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Database Connection</h2>";

try {
    echo "✅ Database connection successful<br>";
    
    // Test basic query
    $sql = "SELECT COUNT(*) as count FROM orders";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total orders: " . $result['count'] . "<br>";
    
    // Test order 1417 specifically
    $order_id = '1417';
    $sql = "SELECT order_id, status_id FROM orders WHERE order_id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($order) {
        echo "Order 1417 found with status_id: " . $order['status_id'] . "<br>";
    } else {
        echo "Order 1417 not found<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?> 