<?php
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

$startAddress = 'Calamba, Laguna';
$endAddresses = ['Calauan,Laguna', 'Santa Cruz,Laguna', 'Santisima Cruz'];
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
    <title>Map with Pinned Location</title>
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

<h1>Map Showing Route with Pinned Location</h1>
<div id="map"></div>
<button id="completeDeliveryBtn">Complete Delivery</button>
<button id="saveLocationBtn">💾 Save Pinned Location</button>

<script>
var map = L.map('map').setView([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

L.marker([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>]).addTo(map)
    .bindPopup("Start: Calamba, Laguna");

let selectedLat = null;
let selectedLng = null;
let userMarker = null;

map.on('click', function(e) {
    selectedLat = e.latlng.lat;
    selectedLng = e.latlng.lng;

    if (userMarker) {
        userMarker.setLatLng([selectedLat, selectedLng]);
    } else {
        userMarker = L.marker([selectedLat, selectedLng], {
            icon: L.icon({
                iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
                iconSize: [30, 30]
            })
        }).addTo(map).bindPopup("📍 Your chosen location").openPopup();
    }

    console.log("Pinned location:", selectedLat, selectedLng);
});

document.getElementById("saveLocationBtn").addEventListener("click", function() {
    if (selectedLat && selectedLng) {
        fetch("save_location.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `lat=${selectedLat}&lng=${selectedLng}`
        })
        .then(response => response.text())
        .then(result => {
            alert("Location saved! Server response: " + result);
        })
        .catch(error => {
            console.error("Error saving location:", error);
        });
    } else {
        alert("Please pin a location first.");
    }
});

// Routing functionality
var routes = [];
function fetchRoute(start, end) {
    var url = 'https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259&start=' + start[1] + ',' + start[0] + '&end=' + end[1] + ',' + end[0];

    fetch(url)
        .then(res => res.json())
        .then(data => {
            var routeCoordinates = data.features[0].geometry.coordinates;
            var route = L.polyline(routeCoordinates.map(c => [c[1], c[0]]), {color: 'blue'}).addTo(map);
            routes.push(route);

            var label = L.divIcon({
                className: 'distance-label',
                html: 'Distance: ' + calculateDistance([start, end]).toFixed(2) + ' km'
            });

            L.marker(route.getCenter(), {icon: label}).addTo(map);
            map.fitBounds(route.getBounds());
        });
}

function calculateDistance(coords) {
    var lat1 = coords[0][0], lon1 = coords[0][1];
    var lat2 = coords[1][0], lon2 = coords[1][1];
    var R = 6371;
    var dLat = toRad(lat2 - lat1);
    var dLon = toRad(lon2 - lon1);
    var a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function toRad(degrees) {
    return degrees * Math.PI / 180;
}

var currentStartCoord = [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>];
var endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;
var deliveryIndex = 0;

function startDeliverySimulation() {
    if (deliveryIndex < endCoordinates.length) {
        var endCoord = endCoordinates[deliveryIndex];
        L.marker([endCoord.lat, endCoord.lon]).addTo(map).bindPopup("End: " + endCoord.lat + ", " + endCoord.lon);
        fetchRoute(currentStartCoord, [endCoord.lat, endCoord.lon]);
        currentStartCoord = [endCoord.lat, endCoord.lon];
        deliveryIndex++;
    } else {
        alert('All deliveries completed!');
    }
}

function removeLastRoute() {
    if (routes.length > 0) {
        map.removeLayer(routes.pop());
    }
}

document.getElementById("completeDeliveryBtn").addEventListener("click", function() {
    startDeliverySimulation();
    removeLastRoute();
});

startDeliverySimulation();
</script>

</body>
</html>
