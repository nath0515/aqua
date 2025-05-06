<?php
require 'db.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE activiti_logs SET read_status = 1 WHERE activitylogs_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}
?>