<?php
require 'session.php';
require 'db.php';

// Only riders can access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header("Location: index.php");
    exit;
}

$rider_id = $_SESSION['user_id'];

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: rider_ratings.php?error=invalid_request");
    exit;
}

// Get submitted data
$rating_id = $_POST['rating_id'];
$action_text = trim($_POST['action_text']);

if (empty($rating_id) || empty($action_text)) {
    header("Location: rider_ratings.php?error=missing_fields");
    exit;
}

// Step 1: Verify the rating exists and belongs to the logged-in rider
$sql = "SELECT action_taken FROM order_ratings WHERE rating_id = :id AND rider_id = :rider_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $rating_id);
$stmt->bindParam(':rider_id', $rider_id);
$stmt->execute();

$rating = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rating) {
    // Rating doesn't belong to rider
    header("Location: rider_ratings.php?error=unauthorized");
    exit;
}

// Step 2: Prevent editing if action already exists
if (!empty($rating['action_taken'])) {
    header("Location: rider_ratings.php?error=already_submitted");
    exit;
}

// Step 3: Save action taken
$action_date = date('Y-m-d H:i:s'); // current date and time in MySQL DATETIME format

$sql = "UPDATE order_ratings SET action_taken = :action_taken, action_date = :action_date WHERE rating_id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':action_taken', $action_text);
$stmt->bindParam(':action_date', $action_date);
$stmt->bindParam(':id', $rating_id);
$stmt->execute();

if ($stmt->execute()) {
    header("Location: rider_ratings.php?success=action_saved");
    exit;
} else {
    header("Location: rider_ratings.php?error=db_error");
    exit;
}
?>
