<?php
// Assuming a connection is already established
require 'db.php';
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');  // Current date and time
$today = date('Y-m-d');  // Current date only (for checking if already clocked out today)

// Check if user has already clocked out today
$sql = "SELECT out_time FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today AND out_time IS NOT NULL";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// If the user has already clocked out today, prevent clocking in again
if ($row) {
    echo "You cannot clock in again today because you have already clocked out.";
    exit();
}

// Proceed with Clock In process if user hasn't clocked out yet
if (isset($_GET['status']) && $_GET['status'] == 1) {
    // Update the rider status to On Duty
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE riderstatus_id = 1");
    $updateStatus->bindParam(':now', $now);
    $updateStatus->execute();

    // Insert attendance record with current in_time
    $insertInTime = $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)");
    $insertInTime->bindParam(':user_id', $user_id);
    $insertInTime->bindParam(':in_time', $now);
    $insertInTime->execute();

    // Redirect back to rider dashboard
    header('Location: riderdashboard.php');
    exit();
}
?>
