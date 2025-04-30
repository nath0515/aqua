<?php
require ('db.php');
require ('session.php');

try {
    if (isset($_POST['lat']) && isset($_POST['lng'])) {
        $lat = floatval($_POST['lat']);
        $lng = floatval($_POST['lng']);
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("UPDATE user_details SET latitude = :lat, longitude = :lng WHERE user_id = :user_id");
        $stmt->execute(['lat' => $lat, 'lng' => $lng, 'user_id' => $user_id]);

        echo "Success";
    } else {
        echo "Missing latitude or longitude.";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>

