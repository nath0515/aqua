<?php 
require ('db.php');
require ('session.php');
try {
    $id = $_GET['id'];
    $destination = $_GET['destination'];

    $stmt = $conn->prepare("UPDATE activity_logs SET read_status = 1 WHERE activitylogs_id = :id");
    $stmt->execute(['id' => $id]);

    header('Location: '.$destination);
    exit();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>