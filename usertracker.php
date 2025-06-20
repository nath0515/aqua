<?php
require 'session.php';
require 'db.php';

$user_id = $_SESSION['user_id'];
if(isset($_GET['id'])){
    $order_id = $_GET['id'];
}
else{
    header("Location: orders.php");
}


// Fetch user's destination coordinates (optional, for user marker)
$sql = "SELECT ud.latitude, ud.longitude, o.rider
        FROM orders o
        JOIN user_details ud ON o.user_id = ud.user_id 
        WHERE o.order_id = :order_id AND o.status_id = 3 LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':order_id', $order_id);
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
    var map = L.map('map').setView([14.1916, 121.1378], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var riderMarker = null;
    var destinationMarker = null;
    var routeLine = null;

    const userDestination = <?php echo json_encode($destination); ?>;

    if (userDestination && userDestination.latitude && userDestination.longitude) {
        destinationMarker = L.marker(
            [userDestination.latitude, userDestination.longitude],
            { title: "Your Delivery Address" }
        ).addTo(map).bindPopup("Your Delivery Address").openPopup();
    }

    function fetchRiderLocation() {
        fetch('get_rider_location.php?rider=<?php echo $destination['rider']?>')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const lat = parseFloat(data.data.latitude);
                    const lon = parseFloat(data.data.longitude);

                    // Update or create rider marker
                    if (!riderMarker) {
                        riderMarker = L.marker([lat, lon], {
                            icon: L.icon({
                                iconUrl: 'https://cdn-icons-png.flaticon.com/512/11431/11431942.png',
                                iconSize: [35, 35],
                                iconAnchor: [17, 34],
                                popupAnchor: [0, -30]
                            })
                        }).addTo(map).bindPopup("Your Rider").openPopup();
                    } else {
                        riderMarker.setLatLng([lat, lon]);
                    }

                    // Draw route from rider to destination
                    if (userDestination && userDestination.latitude && userDestination.longitude) {
                        drawRoute([lon, lat], [userDestination.longitude, userDestination.latitude]);
                    }
                } else {
                    console.warn("No rider location found.");
                    if (routeLine) {
                        map.removeLayer(routeLine);
                        routeLine = null;
                    }
                }
            })
            .catch(err => {
                console.error("Error fetching rider location:", err);
            });
    }

    function drawRoute(start, end) {
        const apiKey = '5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259'; // OpenRouteService demo key

        const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=${apiKey}&start=${start[0]},${start[1]}&end=${end[0]},${end[1]}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const coords = data.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);

                if (routeLine) {
                    map.removeLayer(routeLine);
                }

                routeLine = L.polyline(coords, { color: 'blue', weight: 4 }).addTo(map);
                map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
            })
            .catch(error => {
                console.error('Error fetching route:', error);
            });
    }

    fetchRiderLocation();
    setInterval(fetchRiderLocation, 5000);
</script>

</body>
</html>
