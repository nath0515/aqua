<?php
require('db.php');
require('session.php');

try {

    $user_id = $_SESSION['user_id']; 

   
    $stmt = $conn->prepare("UPDATE activity_logs SET read_status = 1 WHERE user_id = :user_id AND read_status = 0");
    $stmt->execute(['user_id' => $user_id]);

    
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
