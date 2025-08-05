<?php
require 'session.php';
require 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Order Query</h2>";

$order_id = $_GET['id'] ?? '1417';
echo "Testing order ID: " . $order_id . "<br>";

try {
    // Test the exact query from costumer_orderdetails.php
    $sql = "SELECT a.quantity, a.with_container,a.container_quantity,
    b.product_name, b.water_price, b.container_price, 
    c.date, c.amount, c.rider, 
    d.firstname, d.lastname, d.address, d.contact_number,
    e.status_name
    FROM orderitems a
    JOIN products b ON a.product_id = b.product_id
    JOIN orders c ON a.order_id = c.order_id
    JOIN user_details d ON c.user_id = d.user_id
    JOIN orderstatus e ON c.status_id = e.status_id
    WHERE a.order_id = :order_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully<br>";
    echo "Found " . count($order_data) . " items<br>";
    
    if(!empty($order_data)) {
        echo "Order status: " . $order_data[0]['status_name'] . "<br>";
        echo "Customer: " . $order_data[0]['firstname'] . " " . $order_data[0]['lastname'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='costumer_orderdetails.php?id=" . $order_id . "'>Try original page</a>";
?> 