<?php
require 'session.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lat']) && isset($_POST['lng'])) {
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $user_id = $_SESSION['user_id'];

        try {
            $sql = "UPDATE user_details SET latitude = :lat, longitude = :lng WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':lat', $lat);
            $stmt->bindParam(':lng', $lng);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            echo "success";
        } catch (PDOException $e) {
            http_response_code(500);
            echo "Database error: " . $e->getMessage();
        }
    } else {
        http_response_code(400);
        echo "Invalid input";
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo "Only POST requests are allowed";
}


