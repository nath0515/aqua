<?php 
require 'db.php';
require 'session.php';

try {
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $user_id = $_SESSION['user_id'];

    // Fetch current rider status for the user
    $stmt = $conn->prepare("SELECT status, last_toggle FROM rider_status WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no record exists, insert one
    if (!$row) {
        $stmt = $conn->prepare("INSERT INTO rider_status (user_id, status, last_toggle) VALUES (:user_id, 0, :now)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':now', $now);
        $stmt->execute();

        // Re-fetch the newly inserted record
        $stmt = $conn->prepare("SELECT status, last_toggle FROM rider_status WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $status = $row['status']; // 1 = On Duty, 0 = Off Duty
    $lastToggle = $row['last_toggle'];

    if ($status == 1) {
        // Clocking Out (Off Duty)
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
        // Clocking In (On Duty)
        $toggleDate = date('Y-m-d', strtotime($lastToggle));
        if ($toggleDate !== $today) {
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
        // Else: already clocked in today and toggled off — no action needed
    }

    header('Location: riderdashboard.php');
    exit();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
