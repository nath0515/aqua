<?php
require 'session.php';
require 'db.php';

$user_id = $_SESSION['user_id'];

// Get current order with status = 3 (Out for Delivery)
$sql = "SELECT o.order_id, o.rider_id, ud.latitude, ud.longitude 
        FROM orders o
        JOIN user_details ud ON o.user_id = ud.user_id
        WHERE o.user_id = :user_id AND o.status_id = 3
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("No ongoing delivery to track.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Track My Delivery</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        #map { height: 100vh; width: 100%; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Live Delivery Tracking</h2>
<div id="map"></div>

<script>
    const customerLocation = [<?php echo $order['latitude']; ?>, <?php echo $order['longitude']; ?>];
    const riderId = <?php echo $order['rider_id']; ?>;

    var map = L.map('map').setView(customerLocation, 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data &copy; OpenStreetMap contributors'
    }).addTo(map);

    var customerMarker = L.marker(customerLocation).addTo(map).bindPopup("Your Location").openPopup();
    var riderMarker = null;

    function fetchRiderLocation() {
        fetch('get_rider_location.php?rider_id=' + riderId)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error("Could not get rider location.");
                    return;
                }

                let riderLatLng = [data.latitude, data.longitude];

                if (!riderMarker) {
                    riderMarker = L.marker(riderLatLng, {icon: L.icon({ iconUrl: 'assets/img/rider-icon.png', iconSize: [25, 41], iconAnchor: [12, 41] })})
                        .addTo(map)
                        .bindPopup("Rider");
                } else {
                    riderMarker.setLatLng(riderLatLng);
                }
            })
            .catch(error => console.error("Error:", error));
    }

    fetchRiderLocation(); // initial call
    setInterval(fetchRiderLocation, 5000); // every 5 seconds
</script>

</body>
</html>
