<?php
require 'db.php';
require 'session.php';

if (isset($_GET['user_id'])) {
    $id = intval($_GET['user_id']);
    $sql = "UPDATE activiti_logs SET read_status = 1 WHERE activitylogs_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $id);
    $stmt->execute();
}
?>