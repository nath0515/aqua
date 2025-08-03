<?php
require 'session.php';
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$user_id = $_SESSION['user_id'];
$lat = $_POST['latitude'] ?? null;
$lon = $_POST['longitude'] ?? null;

if (!is_numeric($lat) || !is_numeric($lon)) {
    http_response_code(400);
    echo "Invalid data";
    exit;
}

$stmt = $conn->prepare("UPDATE user_details SET latitude = :lat, longitude = :lon WHERE user_id = :user_id");
$stmt->bindParam(":lat", $lat);
$stmt->bindParam(":lon", $lon);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

echo "OK";
?>