<?php
require 'db.php';
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

// Get current status
$stmt = $conn->prepare("SELECT status, last_toggle FROM rider_status WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    // Insert new status record if missing
    $insertStatus = $conn->prepare("INSERT INTO rider_status (user_id, status, last_toggle) VALUES (:user_id, 0, :now)");
    $insertStatus->bindParam(':user_id', $user_id);
    $insertStatus->bindParam(':now', $now);
    $insertStatus->execute();

    $status = 0;
    $lastToggle = $now;
} else {
    $status = $row['status'];
    $lastToggle = $row['last_toggle'];
}

if ($status == 1) {
    // Clock Out
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = 0, last_toggle = :now WHERE user_id = :user_id");
    $updateStatus->bindParam(':now', $now);
    $updateStatus->bindParam(':user_id', $user_id);
    $updateStatus->execute();

    $updateOut = $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND out_time IS NULL");
    $updateOut->bindParam(':out_time', $now);
    $updateOut->bindParam(':user_id', $user_id);
    $updateOut->execute();
} else {
    // Prevent multiple clock-ins after clocking out
    $checkAttendance = $conn->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today AND out_time IS NOT NULL");
    $checkAttendance->bindParam(':user_id', $user_id);
    $checkAttendance->bindParam(':today', $today);
    $checkAttendance->execute();
    $alreadyOut = $checkAttendance->fetch(PDO::FETCH_ASSOC);

    if ($alreadyOut) {
        echo "You have already clocked out today. Cannot clock in again.";
        exit();
    }

    // Clock In
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE user_id = :user_id");
    $updateStatus->bindParam(':now', $now);
    $updateStatus->bindParam(':user_id', $user_id);
    $updateStatus->execute();

    $insertIn = $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)");
    $insertIn->bindParam(':user_id', $user_id);
    $insertIn->bindParam(':in_time', $now);
    $insertIn->execute();
}

header('Location: riderdashboard.php');
exit();
?>
