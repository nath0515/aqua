<?php
require 'session.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lat']) && isset($_POST['lng'])) {
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $location_id = $_POST['id'];
        $label = $_POST['label'];
        $barangay_id = $_POST['barangay_id'];
        $address = $_POST['address'];

        try {
            $sql = "UPDATE user_locations SET latitude = :lat, longitude = :lng, label = :label, barangay_id = :barangay_id, address = :address WHERE location_id = :location_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':lat', $lat);
            $stmt->bindParam(':lng', $lng);
            $stmt->bindParam(':label', $label);
            $stmt->bindParam(':barangay_id', $barangay_id);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':location_id', $location_id);
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


