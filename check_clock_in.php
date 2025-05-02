<?php
// check_clock_in.php
require 'db.php';
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    echo "User not authenticated";
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');  // Current date only (for checking if already clocked out today)

// Check if user has already clocked out today
$sql = "SELECT out_time FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today AND out_time IS NOT NULL";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// If the user has already clocked out today, return "ClockInNotAllowed"
if ($row) {
    echo 'ClockInNotAllowed';
} else {
    echo 'ClockInAllowed';
}
?>
