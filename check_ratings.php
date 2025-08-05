<?php
require 'db.php';

echo "<h2>Check Ratings in Database</h2>";

try {
    // Check if order_ratings table exists and has data
    $sql = "SELECT COUNT(*) as count FROM order_ratings";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total ratings in database: " . $result['count'] . "<br><br>";
    
    if($result['count'] > 0) {
        // Show all ratings
        $sql = "SELECT or.*, o.order_id, o.date, 
                ud.firstname, ud.lastname,
                rd.firstname as rider_firstname, rd.lastname as rider_lastname
                FROM order_ratings or
                JOIN orders o ON or.order_id = o.order_id
                JOIN user_details ud ON or.user_id = ud.user_id
                JOIN user_details rd ON or.rider_id = rd.user_id
                ORDER BY or.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>All Ratings:</h3>";
        foreach($ratings as $rating) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Order #" . $rating['order_id'] . "</strong><br>";
            echo "Customer: " . $rating['firstname'] . " " . $rating['lastname'] . "<br>";
            echo "Rider: " . $rating['rider_firstname'] . " " . $rating['rider_lastname'] . "<br>";
            echo "Order Rating: " . $rating['order_rating'] . " stars<br>";
            echo "Rider Rating: " . $rating['rider_rating'] . " stars<br>";
            if(!empty($rating['review_text'])) {
                echo "Review: " . htmlspecialchars($rating['review_text']) . "<br>";
            }
            echo "Date: " . $rating['created_at'] . "<br>";
            echo "</div>";
        }
    } else {
        echo "No ratings found in database.<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 