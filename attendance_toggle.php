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

// Prevent clocking in again if already clocked out today
if ($row) {
    echo "You cannot clock in again today because you have already clocked out.";
    exit();
}

// If status is set to 1 (Clock In)
if (isset($_GET['status']) && $_GET['status'] == 1) {
    // Update the rider status to On Duty (status = 1)
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE riderstatus_id = 1");
    $updateStatus->bindParam(':now', $now);
    $updateStatus->execute();

    // Insert attendance record for Clock In
    $insertInTime = $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)");
    $insertInTime->bindParam(':user_id', $user_id);
    $insertInTime->bindParam(':in_time', $now);
    $insertInTime->execute();

    // Redirect back to rider dashboard
    header('Location: riderdashboard.php');
    exit();
}

// If status is set to 0 (Clock Out)
if (isset($_GET['status']) && $_GET['status'] == 0) {
    // Check if the user is already on duty (status = 1)
    $sql = "SELECT status FROM rider_status WHERE riderstatus_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status = $stmt->fetchColumn();

    if ($status == 1) {
        // Update the rider status to Off Duty (status = 0)
        $updateStatus = $conn->prepare("UPDATE rider_status SET status = 0, last_toggle = :now WHERE riderstatus_id = 1");
        $updateStatus->bindParam(':now', $now);
        $updateStatus->execute();

        // Update the attendance record with the clock-out time
        $updateOutTime = $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND out_time IS NULL");
        $updateOutTime->bindParam(':out_time', $now);
        $updateOutTime->bindParam(':user_id', $user_id);
        $updateOutTime->execute();

        // Redirect back to rider dashboard
        header('Location: riderdashboard.php');
        exit();
    } else {
        // If the user was not on duty, prevent clocking out
        echo "You cannot clock out because you were not on duty.";
        exit();
    }
}
?>
