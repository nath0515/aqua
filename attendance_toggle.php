<?php
// Ensure session is started
require 'session.php';
require 'db.php';

if (isset($_GET['status'])) {
    $status = (int) $_GET['status'];  // 1 for On Duty, 0 for Off Duty
    $user_id = $_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');  // Current time

    // Update rider status
    $updateStatus = $conn->prepare("UPDATE rider_status SET status = :status, last_toggle = :now WHERE user_id = :user_id");
    $updateStatus->bindParam(':status', $status);
    $updateStatus->bindParam(':now', $now);
    $updateStatus->bindParam(':user_id', $user_id);
    $updateStatus->execute();

    // Optionally, update attendance based on the status
    if ($status == 1) {
        // Insert clock-in time
        $insertInTime = $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)");
        $insertInTime->bindParam(':user_id', $user_id);
        $insertInTime->bindParam(':in_time', $now);
        $insertInTime->execute();
    } elseif ($status == 0) {
        // Update clock-out time
        $updateOutTime = $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND out_time IS NULL");
        $updateOutTime->bindParam(':out_time', $now);
        $updateOutTime->bindParam(':user_id', $user_id);
        $updateOutTime->execute();
    }

    // Redirect back to the dashboard
    header('Location: riderdashboard.php');
    exit();
}
?>
