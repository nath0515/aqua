<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $locationId = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM locations WHERE location_id = :id");
    $stmt->bindParam(':id', $locationId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error']);
    }
}
?>
