<?php
// Clean up incorrect September 9th data
require 'db.php';

echo "<h1>Cleaning September 9th Data</h1>";
echo "<hr>";

try {
    // First, let's see what September 9th data exists
    $sql = "SELECT order_id, date, amount, user_id FROM orders WHERE DATE(date) = '2025-09-09'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $sept9_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>September 9th Orders Found:</h2>";
    if (empty($sept9_orders)) {
        echo "<p style='color: green;'>✅ No September 9th orders found. Database is clean!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Order ID</th><th>Date</th><th>Amount</th><th>User ID</th></tr>";
        foreach ($sept9_orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['order_id'] . "</td>";
            echo "<td>" . $order['date'] . "</td>";
            echo "<td>₱" . $order['amount'] . "</td>";
            echo "<td>" . $order['user_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>Cleaning Up...</h2>";
        
        // Delete order items first (foreign key constraint)
        $delete_items_sql = "DELETE oi FROM orderitems oi 
                           JOIN orders o ON oi.order_id = o.order_id 
                           WHERE DATE(o.date) = '2025-09-09'";
        $stmt = $conn->prepare($delete_items_sql);
        $stmt->execute();
        $deleted_items = $stmt->rowCount();
        echo "<p>🗑️ Deleted $deleted_items order items</p>";
        
        // Delete orders
        $delete_orders_sql = "DELETE FROM orders WHERE DATE(date) = '2025-09-09'";
        $stmt = $conn->prepare($delete_orders_sql);
        $stmt->execute();
        $deleted_orders = $stmt->rowCount();
        echo "<p>🗑️ Deleted $deleted_orders orders</p>";
        
        // Delete any related activity logs
        $delete_logs_sql = "DELETE FROM activity_logs WHERE DATE(date) = '2025-09-09'";
        $stmt = $conn->prepare($delete_logs_sql);
        $stmt->execute();
        $deleted_logs = $stmt->rowCount();
        echo "<p>🗑️ Deleted $deleted_logs activity logs</p>";
        
        echo "<p style='color: green;'>✅ Cleanup completed successfully!</p>";
    }
    
    // Show current date for reference
    echo "<hr>";
    echo "<h2>Current System Date:</h2>";
    echo "<p><strong>PHP Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>MySQL Date:</strong> ";
    $stmt = $conn->query("SELECT NOW() as current_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['current_time'] . "</p>";
    
    // Show latest orders to verify
    echo "<h2>Latest Orders (should be September 8th or earlier):</h2>";
    $sql = "SELECT order_id, date, amount FROM orders ORDER BY date DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $latest_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Order ID</th><th>Date</th><th>Amount</th></tr>";
    foreach ($latest_orders as $order) {
        echo "<tr>";
        echo "<td>" . $order['order_id'] . "</td>";
        echo "<td>" . $order['date'] . "</td>";
        echo "<td>₱" . $order['amount'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This script removes all data from September 9, 2025 since it's not yet September 9th.</p>";
echo "<p><strong>Current Date:</strong> " . date('F j, Y') . "</p>";
?>
