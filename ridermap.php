<?php
require('db.php');
require('session.php');

$user_id = $_SESSION['user_id'];

$startCoordinates = null;
$sql = "SELECT latitude, longitude FROM shop_location WHERE location_id = 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $startCoordinates = [
        'lat' => $row['latitude'],
        'lon' => $row['longitude']
    ];
}

$endCoordinatesArray = [];
$sql = "SELECT latitude, longitude FROM orders JOIN user_details ON orders.user_id = user_details.user_id
WHERE (latitude IS NOT NULL AND longitude IS NOT NULL) AND status_id = 3";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $endCoordinatesArray[] = [
        'lat' => $row['latitude'],
        'lon' => $row['longitude']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Real-Time Delivery Map</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.1/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #map { height: 500px; width: 100%; }
        .distance-label {
            background-color: rgba(255, 255, 255, 0.7);
            padding: 5px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<h1>Real-Time Delivery Map</h1>
<div id="map"></div>
<button id="completeDeliveryBtn">Next Delivery</button>

<script>
let map = L.map('map').setView([14.1916, 121.1378], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

let deliveryIndex = 0;
let currentStartCoord = null;
let currentMarker = null;
let currentRoute = null;
let endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;

function toRad(degrees) {
    return degrees * Math.PI / 180;
}

function calculateDistance(coords) {
    const [lat1, lon1] = coords[0];
    const [lat2, lon2] = coords[1];
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function findClosestEndCoordinate(currentCoord, destinations) {
    let closestIndex = -1, minDistance = Infinity;
    destinations.forEach((coord, i) => {
        const dist = calculateDistance([currentCoord, [coord.lat, coord.lon]]);
        if (dist < minDistance) {
            minDistance = dist;
            closestIndex = i;
        }
    });
    return closestIndex;
}

function fetchRoute(start, end) {
    if (currentRoute) map.removeLayer(currentRoute);
    const orsUrl = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259&start=${start[1]},${start[0]}&end=${end[1]},${end[0]}`;

    fetch(orsUrl)
        .then(response => response.json())
        .then(data => {
            const coordinates = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
            currentRoute = L.polyline(coordinates, { color: 'blue' }).addTo(map);

            const dist = calculateDistance([start, end]);
            const label = L.divIcon({ className: 'distance-label', html: `Distance: ${dist.toFixed(2)} km` });
            L.marker(currentRoute.getCenter(), { icon: label }).addTo(map);
            map.fitBounds(currentRoute.getBounds());
        })
        .catch(error => console.error("Route fetch error:", error));
}

function updateLocation(position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;
    currentStartCoord = [lat, lon];

    if (!currentMarker) {
        currentMarker = L.marker(currentStartCoord).addTo(map).bindPopup("Current Location").openPopup();
    } else {
        currentMarker.setLatLng(currentStartCoord);
    }

    if (endCoordinates.length > 0 && deliveryIndex === 0) {
        startDeliverySimulation();
    }
}

function startDeliverySimulation() {
    if (!currentStartCoord || endCoordinates.length === 0) {
        Swal.fire("All Deliveries Completed", "Returning to base...", "success");
        fetchRoute(currentStartCoord, [14.1916, 121.1378]); // return to base
        return;
    }

    const closestIndex = findClosestEndCoordinate(currentStartCoord, endCoordinates);
    const closestEndCoord = endCoordinates.splice(closestIndex, 1)[0];

    L.marker([closestEndCoord.lat, closestEndCoord.lon])
        .addTo(map).bindPopup(`Delivery #${deliveryIndex + 1}`).openPopup();

    fetchRoute(currentStartCoord, [closestEndCoord.lat, closestEndCoord.lon]);

    // Update start for next route
    currentStartCoord = [closestEndCoord.lat, closestEndCoord.lon];
    deliveryIndex++;
}

document.getElementById("completeDeliveryBtn").addEventListener("click", startDeliverySimulation);

// Watch real-time position
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(updateLocation, err => {
        console.error("Geolocation error:", err);
    }, {
        enableHighAccuracy: true,
        maximumAge: 5000,
        timeout: 10000
    });
} else {
    alert("Geolocation is not supported by your browser.");
}
</script>
</body>
</html>
