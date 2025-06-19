<?php
require 'session.php';
require 'db.php';

$user_id = $_SESSION['user_id'];

// Get user's delivery coordinates
$sql = "SELECT ud.latitude, ud.longitude 
        FROM user_details ud 
        JOIN orders o ON o.user_id = ud.user_id 
        WHERE o.user_id = :user_id AND o.status_id = 3 LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$destination = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Track My Delivery</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        #map { height: 500px; width: 100%; margin-top: 20px; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Live Delivery Tracking</h2>
    <div id="map"></div>

    <script>
        const destination = <?php echo json_encode($destination); ?>;
        const orsApiKey = "5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259";

        var map = L.map('map').setView([14.1916, 121.1378], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var riderMarker = null;
        var destinationMarker = null;
        var routeLine = null;

        if (destination && destination.latitude && destination.longitude) {
            destinationMarker = L.marker(
                [destination.latitude, destination.longitude],
                { title: "Your Delivery Address" }
            ).addTo(map).bindPopup("Your Delivery Address").openPopup();
        }

        function fetchRiderLocation() {
            fetch('get_rider_location.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const lat = parseFloat(data.data.latitude);
                        const lon = parseFloat(data.data.longitude);
                        const riderCoords = [lat, lon];
                        const destCoords = [destination.latitude, destination.longitude];

                        if (!riderMarker) {
                            riderMarker = L.marker(riderCoords, {
                                icon: L.icon({
                                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
                                    iconSize: [35, 35],
                                    iconAnchor: [17, 34],
                                    popupAnchor: [0, -30]
                                })
                            }).addTo(map).bindPopup("Your Rider").openPopup();
                        } else {
                            riderMarker.setLatLng(riderCoords);
                        }

                        drawRoute(riderCoords, destCoords);
                    }
                })
                .catch(err => {
                    console.error("Error fetching rider location:", err);
                });
        }

        function drawRoute(start, end) {
            const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=${orsApiKey}&start=${start[1]},${start[0]}&end=${end[1]},${end[0]}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    const coords = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);

                    if (routeLine) {
                        map.removeLayer(routeLine);
                    }

                    routeLine = L.polyline(coords, { color: 'blue', weight: 4 }).addTo(map);
                    map.fitBounds(routeLine.getBounds());
                })
                .catch(err => {
                    console.error("Error drawing route:", err);
                });
        }

        fetchRiderLocation();
        setInterval(fetchRiderLocation, 5000);
    </script>
</body>
</html>
