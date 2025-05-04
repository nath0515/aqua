<?php
require 'db.php';

$rider_id = $_GET['rider_id'];
$stmt = $conn->prepare("SELECT current_latitude, current_longitude FROM user_details WHERE user_id = ?");
$stmt->execute([$rider_id]);
$loc = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode([
    "latitude" => $loc['current_latitude'],
    "longitude" => $loc['current_longitude']
]);