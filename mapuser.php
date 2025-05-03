<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, latitude, longitude, address, contact_number 
            FROM users u
            JOIN user_details ud ON u.user_id = ud.user_id
            WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Saved coordinates from database
    $savedLat = !empty($user_data['latitude']) ? $user_data['latitude'] : null;
    $savedLng = !empty($user_data['longitude']) ? $user_data['longitude'] : null;

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
    <meta charset="utf-8" />
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
        <button id="editLocationBtn" style="display: none;">✏️ Edit Location</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const map = L.map('map').setView(
            [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 
            17
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Reference marker
        L.marker([<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>])
            .addTo(map)
            .bindPopup("📍 Reference: Santa Cruz public market, Laguna")
            .openPopup();

        let savedLat = <?php echo $savedLat !== null ? json_encode(floatval($savedLat)) : 'null'; ?>;
        let savedLng = <?php echo $savedLng !== null ? json_encode(floatval($savedLng)) : 'null'; ?>;

        let selectedLat = null;
        let selectedLng = null;
        let userMarker = null;
        let isLocationConfirmed = false;

        // Show existing location if available
        if (savedLat !== null && savedLng !== null) {
            userMarker = L.marker([savedLat, savedLng], {
                icon: L.icon({
                    iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
                    iconSize: [30, 30]
                })
            }).addTo(map).bindPopup("📍 Your Saved Location").openPopup();

            map.setView([savedLat, savedLng], 17);

            selectedLat = savedLat;
            selectedLng = savedLng;
            isLocationConfirmed = true;

            document.getElementById("confirmLocationBtn").disabled = true;
            document.getElementById("saveLocationBtn").disabled = false;
            document.getElementById("editLocationBtn").style.display = "inline-block";
        }

        map.on('click', function(e) {
            if (isLocationConfirmed) {
                alert("You've already confirmed your location.");
                return;
            }

            selectedLat = e.latlng.lat;
            selectedLng = e.latlng.lng;

            if (userMarker) {
                userMarker.setLatLng([selectedLat, selectedLng]).bindPopup("📍 Your Chosen Location").openPopup();
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

        document.getElementById("confirmLocationBtn").addEventListener("click", function() {
            if (selectedLat && selectedLng) {
                isLocationConfirmed = true;
                Swal.fire({
                    icon: 'success',
                    title: '✅ Location Confirmed!',
                    text: 'Your location has been confirmed. You can now save it.'
                });
                document.getElementById("confirmLocationBtn").disabled = true;
                document.getElementById("saveLocationBtn").disabled = false;
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'No location selected',
                    text: 'Please pin a location first.'
                });
            }
        });

        document.getElementById("saveLocationBtn").addEventListener("click", function () {
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
                    Swal.fire({
                        icon: 'success',
                        title: '📌 Location Saved!',
                        text: 'Your delivery location has been saved successfully.',
                        confirmButtonText: 'Proceed to Order'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            window.location.href = "costumerorder.php";
                        }
                    });

                    document.getElementById("editLocationBtn").style.display = "inline-block";
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Error saving location: ' + error
                    });
                });
            }
        });

        document.getElementById("editLocationBtn").addEventListener("click", function () {
            isLocationConfirmed = false;
            selectedLat = null;
            selectedLng = null;

            if (userMarker) {
                map.removeLayer(userMarker);
                userMarker = null;
            }

            document.getElementById("confirmLocationBtn").disabled = false;
            document.getElementById("saveLocationBtn").disabled = true;

            Swal.fire({
                icon: 'info',
                title: 'Edit Location Mode',
                text: 'You can now re-pin a new location on the map.'
            });
        });
    </script>
</body>
</html>
