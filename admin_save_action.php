<?php
require 'session.php';
require 'db.php';

// Only admins (role_id 1 or 2) can access
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: order_details.php?error=invalid_request");
    exit;
}

$rating_id = isset($_POST['rating_id']) ? (int)$_POST['rating_id'] : null;
$action_text = trim($_POST['action_text'] ?? '');
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;

if (empty($rating_id) || empty($action_text) || empty($order_id)) {
    header("Location: order_details.php?id=$order_id&error=missing_fields");
    exit;
}

$sql = "SELECT action_taken FROM order_ratings WHERE rating_id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':id', $rating_id);
$stmt->execute();

$rating = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rating) {
    header("Location: order_details.php?id=$order_id&error=not_found");
    exit;
}

if (!empty($rating['action_taken'])) {
    header("Location: order_details.php?id=$order_id&error=already_submitted");
    exit;
}

$action_date = date('Y-m-d H:i:s');

$sql = "UPDATE order_ratings 
        SET action_taken = :action_taken, action_date = :action_date 
        WHERE rating_id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':action_taken', $action_text);
$stmt->bindValue(':action_date', $action_date);
$stmt->bindValue(':id', $rating_id);

if ($stmt->execute()) {
    header("Location: order_details.php?id=$order_id&success=action_saved");
    exit;
} else {
    error_log(print_r($stmt->errorInfo(), true));
    header("Location: order_details.php?id=$order_id&error=db_error");
    exit;
}
