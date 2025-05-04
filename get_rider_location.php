<?php
require 'db.php';

if (!isset($_GET['rider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing rider ID']);
    exit;
}

$rider_id = intval($_GET['rider_id']);

$sql = "SELECT latitude, longitude FROM rider_location WHERE rider_id = :rider_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':rider_id', $rider_id);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode([
        'success' => true,
        'latitude' => $data['latitude'],
        'longitude' => $data['longitude']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Location not found']);
}
