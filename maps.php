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

$startAddress = 'Santa Cruz public market, Laguna';
$startCoordinates = getCoordinates($startAddress);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pin Your Location</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        #map {
            height: 500px;
            width: 100%;
        }
        .button-group {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<h2>📍 Pin Your Location</h2>
<div id="map"></div>
<div class="button-group">
    <button id="confirmLocationBtn">✅ Confirm Location</button>
    <button id="saveLocationBtn" disabled>💾 Save Location</button>
</div>

<script>
var map = L.map('map').setView([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 17);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

L.marker([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>])
    .addTo(map)
    .bindPopup("📍 Reference: Santa Cruz public market, Laguna")
    .openPopup();

let selectedLat = null;
let selectedLng = null;
let userMarker = null;
let isLocationConfirmed = false;

// User clicks on map to pin location
map.on('click', function(e) {
    if (isLocationConfirmed) {
        alert("You've already confirmed your location.");
        return;
    }

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
        }).addTo(map).bindPopup("📍 Your Chosen Location").openPopup();
    }

    console.log("Pinned location:", selectedLat, selectedLng);
});

// Confirm Button
document.getElementById("confirmLocationBtn").addEventListener("click", function() {
    if (selectedLat && selectedLng) {
        isLocationConfirmed = true;
        alert("✅ Location confirmed!");
        document.getElementById("confirmLocationBtn").disabled = true;
        document.getElementById("saveLocationBtn").disabled = false;
    } else {
        alert("Please pin a location first.");
    }
});

// Save Button
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
            alert("📌 Location saved! Server response: " + result);
        })
        .catch(error => {
            console.error("Error saving location:", error);
        });
    }
});
</script>

</body>
</html>
