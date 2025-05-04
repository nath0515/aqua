<?php
require 'db.php';
$rider_id = $_GET['rider_id'];

$stmt = $conn->prepare("SELECT current_latitude, current_longitude FROM user_details WHERE user_id = ?");
$stmt->execute([$rider_id]);
$loc = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Track Delivery</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
</head>
<body>
    <h2>Tracking Rider</h2>
    <div id="map" style="height: 500px;"></div>
    <script>
        var map = L.map('map').setView([<?php echo $loc['current_latitude']; ?>, <?php echo $loc['current_longitude']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker = L.marker([<?php echo $loc['current_latitude']; ?>, <?php echo $loc['current_longitude']; ?>]).addTo(map)
            .bindPopup("Rider's Location").openPopup();

        // Auto-refresh location every 10 seconds
        setInterval(() => {
            fetch("get_rider_location.php?rider_id=<?php echo $rider_id; ?>")
                .then(res => res.json())
                .then(data => {
                    marker.setLatLng([data.latitude, data.longitude]);
                    map.setView([data.latitude, data.longitude]);
                });
        }, 10000);
    </script>
</body>
</html>
