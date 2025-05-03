<?php
// check_clock_in.php
require 'db.php';
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    echo "User not authenticated";
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch the most recent completed attendance record
$sql = "SELECT in_time, out_time FROM attendance 
        WHERE user_id = :user_id AND out_time IS NOT NULL 
        ORDER BY in_time DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $lastOutDate = date('Y-m-d', strtotime($row['out_time']));

    if ($lastOutDate == $today) {
        // Already completed a shift today
        echo 'ClockInNotAllowed';
        exit();
    }
}

// No completed shift today — allow clock in
echo 'ClockInAllowed';
?>
