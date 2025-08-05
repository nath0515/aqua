<?php
require 'session.php';
require 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Rider Ratings</h2>";

$user_id = $_SESSION['user_id'] ?? 'Not logged in';
$role_id = $_SESSION['role_id'] ?? 'No role';

echo "Current user ID: " . $user_id . "<br>";
echo "Current role ID: " . $role_id . "<br><br>";

try {
    // Check if user is a rider
    if($role_id == 3) {
        echo "✅ User is a rider<br>";
        
        // Get rider's name
        $sql = "SELECT firstname, lastname FROM user_details WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $rider = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($rider) {
            echo "Rider name: " . $rider['firstname'] . " " . $rider['lastname'] . "<br><br>";
        }
        
        // Check all ratings for this rider
        $sql = "SELECT r.*, o.order_id, o.date, 
                ud.firstname, ud.lastname
                FROM order_ratings r
                JOIN orders o ON r.order_id = o.order_id
                JOIN user_details ud ON r.user_id = ud.user_id
                WHERE r.rider_id = :rider_id
                ORDER BY r.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':rider_id', $user_id);
        $stmt->execute();
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($ratings) . " ratings for this rider<br><br>";
        
        if(count($ratings) > 0) {
            foreach($ratings as $rating) {
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
                echo "<strong>Order #" . $rating['order_id'] . "</strong><br>";
                echo "Customer: " . $rating['firstname'] . " " . $rating['lastname'] . "<br>";
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
        
    } else {
        echo "❌ User is not a rider (role_id = " . $role_id . ")<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 