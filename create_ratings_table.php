<?php
require 'db.php';

try {
    // Create order_ratings table
    $sql = "CREATE TABLE IF NOT EXISTS order_ratings (
        rating_id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT,
        user_id INT,
        rider_id INT,
        order_rating INT(1),
        rider_rating INT(1),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (rider_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    echo "✅ Order ratings table created successfully!";
    
} catch(PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage();
}
?> 