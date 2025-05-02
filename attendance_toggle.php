<?php
require 'db.php';
require 'session.php';

try {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $user_id = $_SESSION['user_id'];

    // Check if rider_status exists for this user
    $stmt = $conn->prepare("SELECT status, last_toggle FROM rider_status WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // If no record exists, insert a default row
        $insert = $conn->prepare("INSERT INTO rider_status (user_id, status, last_toggle) VALUES (:user_id, 0, :now)");
        $insert->bindParam(':user_id', $user_id);
        $insert->bindParam(':now', $now);
        $insert->execute();

        $row = ['status' => 0, 'last_toggle' => $now];
    }

    $status = $row['status'];
    $lastToggle = $row['last_toggle'];

    if ($status == 1) {
        // Clock out
        $updateStatus = $conn->prepare("UPDATE rider_status SET status = 0, last_toggle = :now WHERE user_id = :user_id");
        $updateStatus->bindParam(':now', $now);
        $updateStatus->bindParam(':user_id', $user_id);
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
        $toggleDate = date('Y-m-d', strtotime($lastToggle));
        if ($toggleDate !== $today) {
            // Clock in
            $updateStatus = $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE user_id = :user_id");
            $updateStatus->bindParam(':now', $now);
            $updateStatus->bindParam(':user_id', $user_id);
            $updateStatus->execute();

            $insertInTime = $conn->prepare("
                INSERT INTO attendance (user_id, in_time) 
                VALUES (:user_id, :in_time)
            ");
            $insertInTime->bindParam(':user_id', $user_id);
            $insertInTime->bindParam(':in_time', $now);
            $insertInTime->execute();
        }
        // Otherwise, do nothing (already clocked in today)
    }

    header('Location: riderdashboard.php');
    exit();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
