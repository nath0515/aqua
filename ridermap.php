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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Showing Route with Multiple Endpoints</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.1/dist/leaflet-routing-machine.js"></script>
    <style>
        #map {
            height: 500px;
            width: 100%;
        }
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

<h1>Map Showing Route with Multiple Endpoints</h1>
<div id="map"></div>
<button id="completeDeliveryBtn">Complete Delivery</button>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Variables for tracking coordinates and routes
var currentStartCoord = null;
var routes = [];
var endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;
var deliveryIndex = 0;

// Fetch route for a start and end coordinates pair
function fetchRoute(start, end) {
    var orsUrl = 'https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259&start=' + start[1] + ',' + start[0] + '&end=' + end[1] + ',' + end[0];

    fetch(orsUrl)
        .then(response => response.json())
        .then(data => {
            var routeCoordinates = data.features[0].geometry.coordinates;

            var route = L.polyline(routeCoordinates.map(coord => [coord[1], coord[0]]), {
                color: 'blue'
            }).addTo(map);

            routes.push(route);

            var routeLength = calculateDistance([start, end]);

            var label = L.divIcon({
                className: 'distance-label',
                html: 'Distance: ' + routeLength.toFixed(2) + ' km'
            });

            var labelMarker = L.marker(route.getCenter(), { icon: label }).addTo(map);

            map.fitBounds(route.getBounds());
        })
        .catch(error => {
            console.error("Error fetching the route:", error);
        });
}

// Function to calculate the distance between two coordinates
function calculateDistance(coords) {
    var lat1 = coords[0][0], lon1 = coords[0][1];
    var lat2 = coords[1][0], lon2 = coords[1][1];

    var R = 6371; // Earth radius in km
    var dLat = toRad(lat2 - lat1);
    var dLon = toRad(lon2 - lon1);

    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);

    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

function toRad(degrees) {
    return degrees * Math.PI / 180;
}

// Select closest end point based on current location
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

// Initialize the map
var map = L.map('map').setView([14.1916, 121.1378], 14); // Default to Calamba, Laguna
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Function to update the start coordinates to the device's location
function updateStartLocation(position) {
    currentStartCoord = [position.coords.latitude, position.coords.longitude];
    map.setView(currentStartCoord, 14);

    var startMarker = L.marker(currentStartCoord).addTo(map)
        .bindPopup("Current Location");

    // Start delivery simulation after fetching location
    startDeliverySimulation();
}

// Function to simulate the delivery process
function startDeliverySimulation() {
    if (endCoordinates.length > 0 && currentStartCoord) {
        const closestIndex = findClosestEndCoordinate(currentStartCoord, endCoordinates);
        let closestEndCoord = endCoordinates.splice(closestIndex, 1)[0];

        // Display marker for the closest delivery point
        L.marker([closestEndCoord.lat, closestEndCoord.lon]).addTo(map)
            .bindPopup("Delivery #" + (deliveryIndex + 1)).openPopup();

        // Fetch route for the closest delivery
        fetchRoute(currentStartCoord, [closestEndCoord.lat, closestEndCoord.lon]);

        // Update the current delivery position
        currentStartCoord = [closestEndCoord.lat, closestEndCoord.lon];
        deliveryIndex++;
    } else {
        Swal.fire({
            icon: 'success',
            title: 'All Deliveries Completed!',
            text: 'Calculating route back to base...'
        }).then(() => {
            fetchRoute(currentStartCoord, [14.1916, 121.1378]); // Route back to base (start)
        });
    }
}

// Event listener to begin delivery simulation
document.getElementById("completeDeliveryBtn").addEventListener("click", function () {
    startDeliverySimulation();
});

// Get current geolocation
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(updateStartLocation, function (error) {
        console.error("Error getting geolocation: ", error);
    });
} else {
    alert("Geolocation is not supported by this browser.");
}

</script>

</body>
</html>
