<?php 
require 'db.php';
require 'session.php';

try {
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $user_id = $_SESSION['user_id'];

    // Get current status and last toggle time
    $stmt = $conn->prepare("SELECT status, last_toggle FROM rider_status WHERE riderstatus_id = 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $status = $row['status']; // 1 = On Duty, 0 = Off Duty
    $lastToggle = $row['last_toggle'];

    // If currently On Duty -> Clock Out
    if ($status == 1) {
        $updateStatus = $conn->prepare("UPDATE rider_status SET status = 0, last_toggle = :now WHERE riderstatus_id = 1");
        $updateStatus->bindParam(':now', $now);
        $updateStatus->execute();

        $updateOutTime = $conn->prepare("
            UPDATE attendance 
            SET out_time = :out_time 
            WHERE user_id = :user_id AND out_time IS NULL
        ");
        $updateOutTime->bindParam(':out_time', $now);
        $updateOutTime->bindParam(':user_id', $user_id);
        $updateOutTime->execute();

    } else {
        // Check if they already toggled Off Duty today
        $toggleDate = date('Y-m-d', strtotime($lastToggle));
        if ($toggleDate !== $today) {
            // Allow Clock In
            $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE riderstatus_id = 1");
            $updateStatus->bindParam(':now', $now);
            $updateStatus->execute();

            $insertInTime = $conn->prepare("
                INSERT INTO attendance (user_id, in_time) 
                VALUES (:user_id, :in_time)
            ");
            $insertInTime->bindParam(':user_id', $user_id);
            $insertInTime->bindParam(':in_time', $now);
            $insertInTime->execute();
        }
        // Else do nothing (already clocked out today)
    }

    header('Location: riderdashboard.php');
    exit();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
