<?php
require 'session.php';
require 'db.php';

// Only admins (role_id 1 or 2) can access
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header("Location: index.php");
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: order_details.php?error=invalid_request");
    exit;
}

// Get submitted data
$rating_id = $_POST['rating_id'] ?? null;
$action_text = trim($_POST['action_text'] ?? '');
$order_id = $_POST['order_id'] ?? null;

if (empty($rating_id) || empty($action_text) || empty($order_id)) {
    header("Location: order_details.php?order_id=$order_id&error=missing_fields");
    exit;
}

// Step 1: Verify the rating exists
$sql = "SELECT action_taken FROM order_ratings WHERE rating_id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $rating_id);
$stmt->execute();

$rating = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rating) {
    // Rating does not exist
    header("Location: order_details.php?order_id=$order_id&error=not_found");
    exit;
}

// Step 2: Prevent editing if action already exists
if (!empty($rating['action_taken'])) {
    header("Location: order_details.php?order_id=$order_id&error=already_submitted");
    exit;
}

// Step 3: Save action taken
$action_date = date('Y-m-d H:i:s'); // MySQL DATETIME

$sql = "UPDATE order_ratings 
        SET action_taken = :action_taken, action_date = :action_date 
        WHERE rating_id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':action_taken', $action_text);
$stmt->bindParam(':action_date', $action_date);
$stmt->bindParam(':id', $rating_id);

if ($stmt->execute()) {
    header("Location: order_details.php?order_id=$order_id&success=action_saved");
    exit;
} else {
    header("Location: order_details.php?order_id=$order_id&error=db_error");
    exit;
}
?>
