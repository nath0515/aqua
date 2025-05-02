<?php
// Include required files
require 'db.php';
require 'session.php';

try {
    // Ensure session is started and user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if user is not authenticated
        header('Location: login.php');
        exit();
    }

    $now = date('Y-m-d H:i:s');  // Current date and time
    $today = date('Y-m-d');  // Current date only (for checking toggle status)
    $user_id = $_SESSION['user_id'];  // Get the logged-in user ID

    // Get current status and last toggle time of the rider from rider_status table
    $stmt = $conn->prepare("SELECT status, last_toggle FROM rider_status WHERE riderstatus_id = 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $status = $row['status'];  // 1 = On Duty, 0 = Off Duty
        $lastToggle = $row['last_toggle'];  // Last time the status was toggled

        // If the user is currently On Duty (status = 1), we will Clock Out
        if ($status == 1) {
            // Update status to Off Duty (0) and update the last toggle time
            $updateStatus = $conn->prepare("UPDATE rider_status SET status = 0, last_toggle = :now WHERE riderstatus_id = 1");
            $updateStatus->bindParam(':now', $now);
            $updateStatus->execute();

            // Update the attendance record with the current out_time (Clock Out)
            $updateOutTime = $conn->prepare("
                UPDATE attendance 
                SET out_time = :out_time 
                WHERE user_id = :user_id AND out_time IS NULL
            ");
            $updateOutTime->bindParam(':out_time', $now);
            $updateOutTime->bindParam(':user_id', $user_id);
            $updateOutTime->execute();

        } else {
            // If currently Off Duty (status = 0), we will allow Clock In, but only once per day
            $toggleDate = date('Y-m-d', strtotime($lastToggle));  // Get the date from last toggle

            // Check if the user has already toggled Off Duty today
            if ($toggleDate !== $today) {
                // Update status to On Duty (1) and update the last toggle time
                $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE riderstatus_id = 1");
                $updateStatus->bindParam(':now', $now);
                $updateStatus->execute();

                // Insert new attendance record with the current in_time (Clock In)
                $insertInTime = $conn->prepare("
                    INSERT INTO attendance (user_id, in_time) 
                    VALUES (:user_id, :in_time)
                ");
                $insertInTime->bindParam(':user_id', $user_id);
                $insertInTime->bindParam(':in_time', $now);
                $insertInTime->execute();
            }
            // If already toggled Off Duty today, do nothing (no clocking in)
        }

        // After updating the status, redirect back to the rider dashboard
        header('Location: riderdashboard.php');
        exit();

    } else {
        // Handle case where rider status does not exist (error)
        echo "Error: Rider status not found!";
    }

} catch (PDOException $e) {
    // Handle database errors
    echo "Error: " . $e->getMessage();
}
?>
