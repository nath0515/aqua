<?php
require 'session.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lat']) && isset($_POST['lng'])) {
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $label = $_POST['label'];
        $barangay_id = $_POST['barangay_id'];
        $address = $_POST['address'];

        try {
            $sql = "INSERT INTO user_locations (latitude, longitude, user_id, label, barangay_id, address, created_at) 
            VALUES (:latitude, :longitude, :user_id, :label, :barangay_id, :address, :created_at)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':latitude', $lat);
            $stmt->bindParam(':longitude', $lng);
            $stmt->bindParam(':label', $label);
            $stmt->bindParam(':barangay_id', $barangay_id);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':created_at', $created_at);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
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


