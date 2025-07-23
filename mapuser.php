<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $location_id = $_GET['location_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, longitude, latitude,barangay_id, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM user_locations WHERE location_id = :location_id");
    $stmt->execute(['location_id' => $location_id]);
    $user_location = $stmt->fetch();

    $savedLat = isset($user_location['latitude']) ? floatval($user_location['latitude']) : null;
    $savedLng = isset($user_location['longitude']) ? floatval($user_location['longitude']) : null;

    // Check if both coordinates are missing or invalid (e.g., 0 or null)
    if (!$savedLat && !$savedLng) {
        // Fallback to start address
        $startAddress = 'Santa Cruz public market, Laguna';
        $startCoordinates = getCoordinates($startAddress);

        // Ensure fallback coordinates are used as the "saved" ones
        $savedLat = $startCoordinates['lat'];
        $savedLng = $startCoordinates['lon'];
    } else {
        // Use user's saved coordinates as the default map center
        $startCoordinates = ['lat' => $savedLat, 'lon' => $savedLng];
    }


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

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Orders</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
             <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><a class="dropdown-item" href="#">Notification 1</a></li>
                        <li><a class="dropdown-item" href="#">Notification 2</a></li>
                        <li><a class="dropdown-item" href="#">Notification 3</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                        <div class="sb-sidenav-menu-heading">Menu</div>
                        <a class="nav-link" href="home.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                            Order Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="costumerorder.php">Order</a>
                                <a class="nav-link" href="orderhistory.php">Order History</a>
                            </nav>
                        </div>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        <?php echo "".$user_data['firstname']." ".$user_data['lastname'];?>
                    </div>  
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                <h2>📍 Pin Your Location</h2>
                    <div id="map"></div>
                    <div class="mt-3 w-50 ms-3">
                        <label for="locationLabel" class="form-label">🏷️ Add a Label for this Location</label>
                        <input type="text" id="locationLabel" class="form-control mb-2" placeholder="e.g. Home, Work, Apartment 3B" value="<?php echo $user_location['label']?>" />
                    </div>
                    <div class="mt-3 w-50 ms-3">
                        <label for="locationLabel" class="form-label">🏷️ Add Address</label>
                        <div class="row">
                            <div class="col-3">
                                <select name="province" class="form-select">
                                    <?php 
                                        $stmt = $conn->prepare("SELECT * FROM table_province WHERE province_id = 20");
                                        $stmt->execute();
                                        $province = $stmt->fetch();
                                    ?>
                                    <option value="<?php echo $province['province_id']?>"><?php echo $province['province_name']?></option>
                                </select>
                            </div>
                            <div class="col-3">
                                <select name="municipality" class="form-select">
                                    <?php 
                                        $stmt = $conn->prepare("SELECT * FROM table_municipality WHERE municipality_id = 431");
                                        $stmt->execute();
                                        $municipality = $stmt->fetch();
                                    ?>
                                    <option value="<?php echo $municipality['municipality_id']?>"><?php echo $municipality['municipality_name']?></option>
                                </select>
                            </div>
                            <div class="col-3">
                                <select name="barangay_id" class="form-select">
                                    <?php 
                                        $stmt = $conn->prepare("SELECT * FROM table_barangay WHERE municipality_id = 431");
                                        $stmt->execute();
                                        $barangay = $stmt->fetchAll();
                                    ?>
                                    <option value="">Select Barangay</option>
                                    <?php foreach($barangay as $row):?>
                                        <option value="<?php echo $row['barangay_id']?>"><?php echo $row['barangay_name']?></option>
                                    <?php endforeach;?>
                                    
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="address" placeholder="Street/House Number" required>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button id="confirmLocationBtn" class="btn btn-success">✅ Confirm Location</button>
                        <button id="saveLocationBtn" class="btn btn-primary" disabled>💾 Save Location</button>
                        <button id="editLocationBtn" class="btn btn-warning" style="display: none;">✏️ Edit Location</button>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            let locationLabel = document.getElementById("locationLabel").value;
            let barangay_id = document.getElementById("barangay_id").value;
            let locationId = <?php echo $location_id?>;
            if (selectedLat && selectedLng) {
                fetch("save_location.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `lat=${selectedLat}&lng=${selectedLng}&label=${locationLabel}&address=${address}&id=${locationId}`
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
