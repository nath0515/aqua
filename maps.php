<?php
session_start();
require 'db.php';

// Assume user is logged in and $userId is available
$userId = $_SESSION['user_id'] ?? 1; // Hardcoded for testing

// Get saved location if it exists
$stmt = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userLocation = $stmt->fetch(PDO::FETCH_ASSOC);

// Starting and destination addresses
$startAddress = 'Calamba, Laguna';
$endAddresses = ['Calauan, Laguna', 'Santa Cruz, Laguna', 'Santisima Cruz'];

// Nominatim address lookup
function getCoordinates($address) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address);
    $opts = ['http' => ['header' => "User-Agent: AquaDrop/1.0 (support@aqua-drop.shop)\r\n"]];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    return !empty($data) ? ['lat' => $data[0]['lat'], 'lon' => $data[0]['lon']] : null;
}

// Get coordinates
$startCoordinates = getCoordinates($startAddress);
$endCoordinatesArray = array_map('getCoordinates', $endAddresses);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Map & Delivery Tracker</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.1/dist/leaflet-routing-machine.js"></script>
    <style>
        #map { height: 500px; width: 100%; }
        button { margin: 10px 5px; padding: 8px 16px; }
        .distance-label {
            background-color: rgba(255,255,255,0.8);
            padding: 5px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h2>Delivery Routes + Pin Your Location</h2>
<button id="completeDeliveryBtn">🚚 Complete Delivery</button>
<button id="pinLocationBtn">📍 Pin My Location</button>
<div id="map"></div>

<script>
let map = L.map('map').setView([<?= $startCoordinates['lat'] ?>, <?= $startCoordinates['lon'] ?>], 13);

// OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Start Marker
L.marker([<?= $startCoordinates['lat'] ?>, <?= $startCoordinates['lon'] ?>]).addTo(map)
    .bindPopup("📦 Start: Calamba, Laguna");

// Routes
let routes = [];
let currentStartCoord = [<?= $startCoordinates['lat'] ?>, <?= $startCoordinates['lon'] ?>];
let endCoords = <?= json_encode($endCoordinatesArray); ?>;
let deliveryIndex = 0;

function fetchRoute(start, end) {
    const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259&start=${start[1]},${start[0]}&end=${end[1]},${end[0]}`;
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const coords = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
            const route = L.polyline(coords, { color: 'blue' }).addTo(map);
            routes.push(route);

            const distance = calculateDistance([start, end]);
            const label = L.divIcon({
                className: 'distance-label',
                html: `Distance: ${distance.toFixed(2)} km`
            });

            L.marker(route.getCenter(), { icon: label }).addTo(map);
            map.fitBounds(route.getBounds());
        });
}

function calculateDistance(coords) {
    let [lat1, lon1] = coords[0], [lat2, lon2] = coords[1];
    const R = 6371, dLat = toRad(lat2 - lat1), dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}
function toRad(d) { return d * Math.PI / 180; }

function startDeliverySimulation() {
    if (deliveryIndex < endCoords.length) {
        let end = [endCoords[deliveryIndex].lat, endCoords[deliveryIndex].lon];
        L.marker(end).addTo(map).bindPopup("📍 Delivery Stop");
        fetchRoute(currentStartCoord, end);
        currentStartCoord = end;
        deliveryIndex++;
    } else alert("✅ All deliveries completed!");
}

function removeLastRoute() {
    if (routes.length > 0) {
        map.removeLayer(routes.pop());
    }
}

document.getElementById("completeDeliveryBtn").addEventListener("click", function () {
    startDeliverySimulation();
    removeLastRoute();
});

// -----------------------------
// 📍 Pin & Save Customer Location
let customerMarker = null;

document.getElementById("pinLocationBtn").addEventListener("click", function () {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;

            if (customerMarker) {
                customerMarker.setLatLng([lat, lon]);
            } else {
                customerMarker = L.marker([lat, lon], {
                    icon: L.icon({
                        iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
                        iconSize: [32, 32]
                    })
                }).addTo(map).bindPopup("📍 Your Location").openPopup();
            }

            map.setView([lat, lon], 16);

            // Save location via AJAX
            fetch('save_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lat: lat, lon: lon })
            })
            .then(res => res.json())
            .then(data => alert("📍 Location pinned successfully!"))
            .catch(err => console.error(err));

        }, () => alert("Please allow location access."));
    }
});

// 📍 If user has a saved location already
<?php if (!empty($userLocation['latitude']) && !empty($userLocation['longitude'])): ?>
L.marker([<?= $userLocation['latitude'] ?>, <?= $userLocation['longitude'] ?>], {
    icon: L.icon({
        iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
        iconSize: [32, 32]
    })
}).addTo(map).bindPopup("📍 Your Saved Location");
<?php endif; ?>

startDeliverySimulation();
</script>
</body>
</html>
