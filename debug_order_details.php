<?php
require 'session.php';
require 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Order Details</h2>";

// Test database connection
try {
    echo "✅ Database connection successful<br>";
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test if order_ratings table exists
try {
    $sql = "DESCRIBE order_ratings";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "✅ order_ratings table exists<br>";
} catch(PDOException $e) {
    echo "❌ order_ratings table error: " . $e->getMessage() . "<br>";
}

// Test the main order query
if(isset($_GET['id'])) {
    $order_id = $_GET['id'];
    echo "Testing order ID: " . $order_id . "<br>";
    
    try {
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
        
        echo "✅ Order query successful. Found " . count($order_data) . " items<br>";
        
        if(!empty($order_data)) {
            echo "Order status: " . $order_data[0]['status_name'] . "<br>";
            
            // Test rating query
            $can_rate = ($order_data[0]['status_name'] === 'Delivered' || $order_data[0]['status_name'] === 'Completed');
            echo "Can rate: " . ($can_rate ? 'Yes' : 'No') . "<br>";
            
            if($can_rate) {
                try {
                    $sql = "SELECT * FROM order_ratings WHERE order_id = :order_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':order_id', $order_id);
                    $stmt->execute();
                    $existing_rating = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "✅ Rating query successful<br>";
                } catch(PDOException $e) {
                    echo "❌ Rating query error: " . $e->getMessage() . "<br>";
                }
            }
        }
        
    } catch(PDOException $e) {
        echo "❌ Order query error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "No order ID provided<br>";
}

echo "<br><a href='costumer_orderdetails.php?id=" . ($_GET['id'] ?? '') . "'>Try original page</a>";
?> 