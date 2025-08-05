<?php
require 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Order Details Logic</h2>";

$order_id = $_GET['id'] ?? '1417';
echo "Testing order ID: " . $order_id . "<br>";

try {
    // Simulate the exact query from costumer_orderdetails.php
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
    
    echo "✅ Order query successful<br>";
    echo "Found " . count($order_data) . " items<br>";
    
    if(!empty($order_data)) {
        $order_status = $order_data[0]['status_name'];
        $can_rate = ($order_status === 'Delivered' || $order_status === 'Completed');
        
        echo "Order status: " . $order_status . "<br>";
        echo "Can rate: " . ($can_rate ? 'Yes' : 'No') . "<br>";
        
        if($can_rate) {
            // Test rating query
            $sql = "SELECT * FROM order_ratings WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            $existing_rating = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($existing_rating) {
                echo "✅ Rating exists: Order=" . $existing_rating['order_rating'] . " stars, Rider=" . $existing_rating['rider_rating'] . " stars<br>";
            } else {
                echo "✅ No existing rating found - rating form should show<br>";
            }
        }
        
        // Show order details
        echo "<br><strong>Order Details:</strong><br>";
        foreach($order_data as $item) {
            echo "- " . $item['product_name'] . " (Qty: " . $item['quantity'] . ")<br>";
        }
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='costumer_orderdetails.php?id=" . $order_id . "'>Try original page</a>";
?> 