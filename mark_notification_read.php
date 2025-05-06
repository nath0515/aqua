<?php
require 'db.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}
?>