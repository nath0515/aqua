<?php
// Function to get coordinates (latitude and longitude) from an address or Plus Code using Nominatim API
function getCoordinates($address) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address);
    $options = [
        'http' => [
            'header' => "User-Agent: YourAppName/1.0 (youremail@example.com)\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    if (!empty($data)) {
        return [
            'lat' => $data[0]['lat'],
            'lon' => $data[0]['lon']
        ];
    }
    return null;
}

// Example usage: Get coordinates for the given Plus Code and address
$startAddress = 'Calamba, Laguna';  // Starting point: Plus Code address
$endAddresses = ['Calauan,Laguna', 'Santa Cruz,Laguna', 'Santisima Cruz'];  // Multiple end addresses

// Get coordinates for the start address
$startCoordinates = getCoordinates($startAddress);
$endCoordinatesArray = [];

foreach ($endAddresses as $endAddress) {
    $endCoordinatesArray[] = getCoordinates($endAddress);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Showing Route with Multiple Endpoints</title>
    <!-- Include Leaflet.js -->
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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var map = L.map('map').setView([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

var startMarker = L.marker([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>]).addTo(map)
    .bindPopup("Start: Calamba, Laguna");

var routes = [];

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

function calculateDistance(coords) {
    var lat1 = coords[0][0], lon1 = coords[0][1];
    var lat2 = coords[1][0], lon2 = coords[1][1];

    var R = 6371;
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

const originalStartCoord = [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>];
let currentStartCoord = [...originalStartCoord];
let endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;
let deliveryIndex = 0;

function startDeliverySimulation() {
    if (deliveryIndex < endCoordinates.length) {
        let endCoord = endCoordinates[deliveryIndex];

        L.marker([endCoord.lat, endCoord.lon]).addTo(map)
            .bindPopup("Delivery #" + (deliveryIndex + 1)).openPopup();

        fetchRoute(currentStartCoord, [endCoord.lat, endCoord.lon]);

        currentStartCoord = [endCoord.lat, endCoord.lon];
        deliveryIndex++;
    } else {
        Swal.fire({
            icon: 'success',
            title: 'All Deliveries Completed!',
            text: 'Calculating route back to base...'
        }).then(() => {
            fetchRoute(currentStartCoord, originalStartCoord);

            // Add return home marker
            L.marker(originalStartCoord, {
                icon: L.icon({
                    iconUrl: 'https://img.icons8.com/color/48/home--v1.png',
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                })
            }).addTo(map).bindPopup("🏠 Return to Base").openPopup();

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

startDeliverySimulation();

var riderMarker = L.marker(originalStartCoord, {
    icon: L.icon({
        iconUrl: 'https://img.icons8.com/ios-filled/50/000000/user-location.png',
        iconSize: [25, 25]
    })
}).addTo(map);

function updateRiderPosition() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;

            riderMarker.setLatLng([lat, lon]);
            map.setView([lat, lon], 15);
        }, function (error) {
            console.error("Error getting geolocation: ", error);
        });
    } else {
        alert("Geolocation is not supported by this browser.");
    }
}

// Optional: Uncomment to simulate live tracking every 5 seconds
// setInterval(updateRiderPosition, 5000);
</script>


</body>
</html>