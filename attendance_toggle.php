<?php 
require 'db.php';
require 'session.php';

try {
    $now = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id']; // assumes session holds user_id

    // Get current store status
    $stmt = $conn->prepare("SELECT status FROM store_status WHERE ss_id = 1");
    $stmt->execute();
    $status = $stmt->fetchColumn();

    if ($status) {
        // Clocking out: set store to closed
        $updateStatus = $conn->prepare("UPDATE store_status SET status = 0 WHERE ss_id = 1");
        $updateStatus->execute();

        // Update the latest attendance with out_time
        $updateOutTime = $conn->prepare("
            UPDATE attendance 
            SET out_time = :out_time 
            WHERE user_id = :user_id 
            AND out_time IS NULL 
            ORDER BY in_time DESC LIMIT 1
        ");
        $updateOutTime->bindParam(':out_time', $now);
        $updateOutTime->bindParam(':user_id', $user_id);
        $updateOutTime->execute();

    } else {
        // Clocking in: set store to open
        $updateStatus = $conn->prepare("UPDATE store_status SET status = 1 WHERE ss_id = 1");
        $updateStatus->execute();

        // Insert new attendance with in_time
        $insertInTime = $conn->prepare("
            INSERT INTO attendance (user_id, in_time) 
            VALUES (:user_id, :in_time)
        ");
        $insertInTime->bindParam(':user_id', $user_id);
        $insertInTime->bindParam(':in_time', $now);
        $insertInTime->execute();
    }

    header('Location: index.php');
    exit();
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
