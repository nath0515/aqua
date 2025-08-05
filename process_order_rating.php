<?php
require 'session.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? null;
$order_rating = $_POST['order_rating'] ?? null;
$rider_rating = $_POST['rider_rating'] ?? null;
$review_text = $_POST['review_text'] ?? '';

// Validate inputs
if (!$order_id || !$order_rating || !$rider_rating) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if ($order_rating < 1 || $order_rating > 5 || $rider_rating < 1 || $rider_rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating values']);
    exit;
}

try {
    // Check if order exists and belongs to user
    $sql = "SELECT o.order_id, o.rider, o.status_id, os.status_name 
            FROM orders o 
            JOIN orderstatus os ON o.status_id = os.status_id 
            WHERE o.order_id = :order_id AND o.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Check if order is completed/delivered
    if ($order['status_name'] !== 'Delivered' && $order['status_name'] !== 'Completed') {
        echo json_encode(['success' => false, 'message' => 'Order must be completed before rating']);
        exit;
    }

    // Check if already rated
    $sql = "SELECT rating_id FROM order_ratings WHERE order_id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order already rated']);
        exit;
    }

    // Insert rating
    $sql = "INSERT INTO order_ratings (order_id, user_id, rider_id, order_rating, rider_rating, review_text) 
            VALUES (:order_id, :user_id, :rider_id, :order_rating, :rider_rating, :review_text)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':rider_id', $order['rider']);
    $stmt->bindParam(':order_rating', $order_rating);
    $stmt->bindParam(':rider_rating', $rider_rating);
    $stmt->bindParam(':review_text', $review_text);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 