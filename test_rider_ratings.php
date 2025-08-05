<?php
require 'session.php';
require 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Rider Ratings Page</h2>";

$user_id = $_SESSION['user_id'] ?? 'Not logged in';
$role_id = $_SESSION['role_id'] ?? 'No role';

echo "User ID: " . $user_id . "<br>";
echo "Role ID: " . $role_id . "<br>";

if($role_id == 3) {
    echo "✅ User is a rider<br><br>";
    
    try {
        // Test the exact query from rider_ratings.php
        $sql = "SELECT r.*, o.order_id, o.date, o.amount, 
                ud.firstname, ud.lastname, ud.contact_number,
                p.product_name
                FROM order_ratings r
                JOIN orders o ON r.order_id = o.order_id
                JOIN user_details ud ON r.user_id = ud.user_id
                JOIN orderitems oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE r.rider_id = :rider_id
                ORDER BY r.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':rider_id', $user_id);
        $stmt->execute();
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Query executed successfully<br>";
        echo "Found " . count($ratings) . " ratings<br><br>";
        
        if(count($ratings) > 0) {
            echo "<h3>Ratings Found:</h3>";
            foreach($ratings as $rating) {
                echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
                echo "<strong>Order #" . $rating['order_id'] . "</strong><br>";
                echo "Customer: " . $rating['firstname'] . " " . $rating['lastname'] . "<br>";
                echo "Product: " . $rating['product_name'] . "<br>";
                echo "Order Rating: " . $rating['order_rating'] . " stars<br>";
                echo "Rider Rating: " . $rating['rider_rating'] . " stars<br>";
                if(!empty($rating['review_text'])) {
                    echo "Review: " . htmlspecialchars($rating['review_text']) . "<br>";
                }
                echo "Date: " . $rating['created_at'] . "<br>";
                echo "</div>";
            }
        } else {
            echo "No ratings found for this rider.<br>";
        }
        
    } catch(PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ User is not a rider<br>";
}

echo "<br><a href='rider_ratings.php'>Go to Rider Ratings Page</a>";
?> 