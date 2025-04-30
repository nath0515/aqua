<?php
require('db.php');
require('session.php');

$user_id = $_SESSION['user_id'];

// Get start location (shop)
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

// Get end locations (customer destinations)
$endCoordinatesArray = [];
$sql = "SELECT latitude, longitude FROM orders 
        JOIN user_details ON orders.user_id = user_details.user_id
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status_id = 3";
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
    <style>
        #map { height: 500px; width: 100%; }
        #completeBtn { margin: 10px; padding: 10px 20px; font-size: 16px; }
    </style>
</head>
<body>

<div id="map"></div>
<button id="completeBtn">Complete Delivery</button>

<script>
var map = L.map('map').setView([14.1916, 121.1378], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var currentStartCoord = null;
var currentStartMarker = null;
var currentRouteLine = null;
var destinationMarker = null;
var currentDestinationIndex = -1;

var endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;

// Convert degrees to radians
function toRad(degrees) {
    return degrees * Math.PI / 180;
}

// Haversine formula
function calculateDistance(coords) {
    var lat1 = coords[0][0], lon1 = coords[0][1];
    var lat2 = coords[1][0], lon2 = coords[1][1];
    var R = 6371;
    var dLat = toRad(lat2 - lat1);
    var dLon = toRad(lon2 - lon1);

    var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Find closest end point
function findClosestEndCoordinate(currentCoord, endCoordinates) {
    let minDistance = Infinity;
    let closestIndex = -1;

    endCoordinates.forEach((coord, index) => {
        const distance = calculateDistance([currentCoord, [coord.lat, coord.lon]]);
        if (distance < minDistance) {
            minDistance = distance;
            closestIndex = index;
        }
    });

    return closestIndex;
}

// Fetch route using OpenRouteService
function fetchRoute(start, end) {
    var orsUrl = 'https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259&start='
        + start[1] + ',' + start[0] + '&end=' + end[1] + ',' + end[0];

    fetch(orsUrl)
        .then(response => response.json())
        .then(data => {
            var coords = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);

            if (currentRouteLine) {
                map.removeLayer(currentRouteLine);
            }

            currentRouteLine = L.polyline(coords, { color: 'blue' }).addTo(map);
            map.fitBounds(currentRouteLine.getBounds());
        })
        .catch(err => console.error("Route error:", err));
}

// Update user's live location
function updateStartLocation(position) {
    currentStartCoord = [position.coords.latitude, position.coords.longitude];

    if (!currentStartMarker) {
        currentStartMarker = L.marker(currentStartCoord).addTo(map).bindPopup("You").openPopup();
    } else {
        currentStartMarker.setLatLng(currentStartCoord);
    }

    if (endCoordinates.length > 0) {
        currentDestinationIndex = findClosestEndCoordinate(currentStartCoord, endCoordinates);
        const next = endCoordinates[currentDestinationIndex];
        const destinationCoord = [next.lat, next.lon];

        if (!destinationMarker) {
            destinationMarker = L.marker(destinationCoord).addTo(map).bindPopup("Destination").openPopup();
        } else {
            destinationMarker.setLatLng(destinationCoord).openPopup();
        }

        fetchRoute(currentStartCoord, destinationCoord);
    }
}

// SweetAlert-enhanced delivery completion
function completeDelivery() {
    if (currentDestinationIndex === -1) return;

    Swal.fire({
        title: 'Complete this delivery?',
        text: "This will mark the current stop as delivered.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, complete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            endCoordinates.splice(currentDestinationIndex, 1);
            currentDestinationIndex = -1;

            if (destinationMarker) {
                map.removeLayer(destinationMarker);
                destinationMarker = null;
            }

            if (currentRouteLine) {
                map.removeLayer(currentRouteLine);
                currentRouteLine = null;
            }

            if (endCoordinates.length === 0) {
                Swal.fire({
                    title: 'All deliveries completed!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Delivery completed!',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Recalculate and go to next destination
                updateStartLocation({ coords: { latitude: currentStartCoord[0], longitude: currentStartCoord[1] } });
            }
        }
    });
}

// Button listener
document.getElementById("completeBtn").addEventListener("click", completeDelivery);

// Start geolocation
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(updateStartLocation, err => {
        console.error("Location error:", err);
    }, {
        enableHighAccuracy: true,
        maximumAge: 0,
        timeout: 5000
    });
} else {
    alert("Geolocation not supported.");
}
</script>

</body>
</html>
