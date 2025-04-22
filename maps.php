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
<button id="pinLocationBtn">📍 Pin My Location</button>

<script>
// Initialize the map with the start coordinates
var map = L.map('map').setView([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 14);

// Set up the OpenStreetMap tiles layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Add a marker for the start coordinates
var startMarker = L.marker([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>]).addTo(map)
    .bindPopup("Start: Calamba, Laguna");

// Array to store polyline (route) objects
var routes = [];

// Function to fetch route data from OpenRouteService API
function fetchRoute(start, end) {
    var orsUrl = 'https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259&start=' + start[1] + ',' + start[0] + '&end=' + end[1] + ',' + end[0];

    fetch(orsUrl)
        .then(response => response.json())
        .then(data => {
            // Extract the coordinates of the route from the response
            var routeCoordinates = data.features[0].geometry.coordinates;

            // Create a polyline (the route) and add it to the map
            var route = L.polyline(routeCoordinates.map(function(coord) {
                return [coord[1], coord[0]];  // Switch lat/lon order
            }), {color: 'blue'}).addTo(map);

            // Store the route for later removal
            routes.push(route);

            // Calculate the route's distance manually using the polyline's coordinates
            var routeLength = calculateDistance([start, end]); // Calculate using Haversine formula

            // Add a label with the distance on the map
            var label = L.divIcon({
                className: 'distance-label',
                html: 'Distance: ' + routeLength.toFixed(2) + ' km'
            });

            var labelMarker = L.marker(route.getCenter(), {icon: label}).addTo(map);
            
            // Optionally, fit the map view to the route
            map.fitBounds(route.getBounds());
        })
        .catch(error => {
            console.error("Error fetching the route:", error);
        });
}

// Function to calculate the distance between the start and end locations (Haversine Formula)
function calculateDistance(coords) {
    var lat1 = coords[0][0], lon1 = coords[0][1];
    var lat2 = coords[1][0], lon2 = coords[1][1];
    
    var R = 6371; // Earth radius in kilometers
    var dLat = toRad(lat2 - lat1);
    var dLon = toRad(lon2 - lon1);
    
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    
    var distance = R * c; // Distance in kilometers
    return distance;
}

// Helper function to convert degrees to radians
function toRad(degrees) {
    return degrees * Math.PI / 180;
}

// Initial start coordinates
var currentStartCoord = [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>];
var endCoordinates = <?php echo json_encode($endCoordinatesArray); ?>;
var deliveryIndex = 0;

// Function to start the simulation of deliveries
function startDeliverySimulation() {
    if (deliveryIndex < endCoordinates.length) {
        var endCoord = endCoordinates[deliveryIndex];

        // Add marker on the endpoint
        var endMarker = L.marker([endCoord.lat, endCoord.lon]).addTo(map)
            .bindPopup("End: " + endCoord.lat + ", " + endCoord.lon);

        // Fetch the route between current start and end coordinates
        fetchRoute(currentStartCoord, [endCoord.lat, endCoord.lon]);

        // Update current start coordinate after delivery
        currentStartCoord = [endCoord.lat, endCoord.lon];
        deliveryIndex++; // Move to the next delivery
    } else {
        alert('All deliveries completed!');
    }
}

// Function to remove the last route (polyline)
function removeLastRoute() {
    if (routes.length > 0) {
        // Remove the last route from the map
        map.removeLayer(routes[routes.length - 1]);
        // Remove the route from the array
        routes.pop();
    }
}

// Attach event listener to the "Complete Delivery" button
document.getElementById("completeDeliveryBtn").addEventListener("click", function() {
    startDeliverySimulation();

    // Remove the last route after completing the delivery
    removeLastRoute();
});

// Initial delivery simulation (first delivery)
startDeliverySimulation();

// Create a marker for the rider (initially placed at the start)
var riderMarker = L.marker([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], {
    icon: L.icon({
        iconUrl: 'https://img.icons8.com/ios-filled/50/000000/user-location.png', // Rider icon
        iconSize: [25, 25]
    })
}).addTo(map);

// User location marker
var userMarker = null;

document.getElementById("pinLocationBtn").addEventListener("click", function () {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;

            // If already placed, update the marker location
            if (userMarker) {
                userMarker.setLatLng([lat, lon]);
            } else {
                // Add new marker
                userMarker = L.marker([lat, lon], {
                    icon: L.icon({
                        iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
                        iconSize: [30, 30]
                    })
                }).addTo(map).bindPopup("📍 Your pinned location").openPopup();
            }

            // Optional: send it to the server using fetch
            fetch('save_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lat: lat, lon: lon })
            })
            .then(response => response.json())
            .then(data => console.log(data.message))
            .catch(error => console.error('Error saving location:', error));

        }, function (error) {
            alert("Geolocation failed: " + error.message);
        });
    } else {
        alert("Geolocation is not supported by your browser.");
    }
});
</script>

</body>
</html>
