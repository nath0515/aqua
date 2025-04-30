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
// Initialize the map without setting the start location initially
var map = L.map('map').setView([14.0, 121.0], 14); // Default to a general area

var routes = [];

// Fetch the route for a start and end coordinates pair
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

const originalStartCoord = [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>];
let currentStartCoord = [...originalStartCoord];
let endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;
let deliveryIndex = 0;

function startDeliverySimulation() {
    if (endCoordinates.length > 0) {
        // Find the closest delivery location from the current position
        const closestIndex = findClosestEndCoordinate(currentStartCoord, endCoordinates);
        let closestEndCoord = endCoordinates.splice(closestIndex, 1)[0]; // Remove selected endpoint

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
            fetchRoute(currentStartCoord, originalStartCoord);
            L.marker(originalStartCoord, {
                icon: L.icon({
                    iconUrl: 'https://img.icons8.com/color/48/home--v1.png',
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                })
            }).addTo(map).bindPopup("🏠 Return to Shop").openPopup();
            map.fitBounds([currentStartCoord, originalStartCoord]);
        });
    }
}

function removeLastRoute() {
    if (routes.length > 0) {
        map.removeLayer(routes[routes.length - 1]);
        routes.pop();
    }
}

document.getElementById("completeDeliveryBtn").addEventListener("click", function () {
    startDeliverySimulation();
    removeLastRoute();
});

// Use geolocation to set current location as the start
function setCurrentLocationAsStart() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;

            // Set map center and current start location to current position
            map.setView([lat, lon], 14);

            currentStartCoord = [lat, lon]; // Update start coordinates

            // Add marker for current location
            var riderMarker = L.marker([lat, lon], {
                icon: L.icon({
                    iconUrl: 'https://img.icons8.com/ios-filled/50/000000/user-location.png',
                    iconSize: [25, 25]
                })
            }).addTo(map);

            startDeliverySimulation(); // Start delivery simulation with the current location
        }, function (error) {
            console.error("Error getting geolocation: ", error);
        });
    } else {
        alert("Geolocation is not supported by this browser.");
    }
}

setCurrentLocationAsStart(); // Call the function to set current location
</script>

</body>
</html>
