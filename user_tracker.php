<?php 
    require 'session.php';
    require 'db.php';
    require 'notification_helper.php';

    $role_id = $_SESSION['role_id'];
    $user_id = $_SESSION['user_id'];
    
    // Get notifications
    $notifications = getNotifications($conn, $user_id, $role_id);
    if(isset($_GET['id'])){
        $order_id = $_GET['id'];
    }
    else{
        header("Location: orders.php");
    }
    if($role_id == 1){
        header("Location: index.php");
    }else if ($role_id == 3){
        header("Location: riderdashboard.php");
    }
    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT ul.latitude, ul.longitude, ul.label, ul.address, o.rider,
                   tb.barangay_name, tm.municipality_name, tp.province_name
        FROM orders o
        LEFT JOIN user_locations ul ON o.location_id = ul.location_id
        LEFT JOIN table_barangay tb ON ul.barangay_id = tb.barangay_id
        LEFT JOIN table_municipality tm ON tb.municipality_id = tm.municipality_id
        LEFT JOIN table_province tp ON tm.province_id = tp.province_id
        WHERE o.order_id = :order_id AND o.status_id = 3 LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        $destination = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <title>Track My Delivery</title>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
        <style>
            #map { height: 500px; width: 100%; margin-top: 20px; }
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
                <li class="nav-item me-2">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                </li>
             <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php echo renderNotificationBadge($notifications['unread_count']); ?>
                        <span class="visually-hidden">unread messages</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <?php echo renderNotificationDropdown($notifications['recent_notifications']); ?>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="userprofile.php">Profile</a></li>
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
                    <h2 style="text-align:center;">Live Delivery Tracking</h2>
                        <div id="map"></div>
                </main>   
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
            var map = L.map('map').setView([14.1916, 121.1378], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var riderMarker = null;
            var destinationMarker = null;
            var routeLine = null;

            const userDestination = <?php echo json_encode($destination); ?>;

            if (userDestination && userDestination.latitude && userDestination.longitude) {
                // Build complete address for popup
                let completeAddress = '';
                if (userDestination.label) {
                    completeAddress += `<strong>${userDestination.label}</strong><br>`;
                }
                if (userDestination.address) {
                    completeAddress += userDestination.address;
                }
                if (userDestination.barangay_name) {
                    completeAddress += `, ${userDestination.barangay_name}`;
                }
                if (userDestination.municipality_name) {
                    completeAddress += `, ${userDestination.municipality_name}`;
                }
                if (userDestination.province_name) {
                    completeAddress += `, ${userDestination.province_name}`;
                }
                if (!completeAddress) {
                    completeAddress = 'Your Delivery Address';
                }

                destinationMarker = L.marker(
                    [userDestination.latitude, userDestination.longitude],
                    { title: "Your Delivery Address" }
                ).addTo(map).bindPopup(completeAddress).openPopup();
            }

            function fetchRiderLocation() {
                fetch('get_rider_location.php?rider=<?php echo $destination['rider']?>')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const lat = parseFloat(data.data.latitude);
                            const lon = parseFloat(data.data.longitude);

                            // Update or create rider marker
                            if (!riderMarker) {
                                riderMarker = L.marker([lat, lon], {
                                    icon: L.icon({
                                        iconUrl: 'https://cdn-icons-png.flaticon.com/512/11431/11431942.png',
                                        iconSize: [35, 35],
                                        iconAnchor: [17, 34],
                                        popupAnchor: [0, -30]
                                    })
                                }).addTo(map).bindPopup("Your Rider").openPopup();
                            } else {
                                riderMarker.setLatLng([lat, lon]);
                            }

                            // Draw route from rider to destination
                            if (userDestination && userDestination.latitude && userDestination.longitude) {
                                drawRoute([lon, lat], [userDestination.longitude, userDestination.latitude]);
                            }
                        } else {
                            console.warn("No rider location found.");
                            if (routeLine) {
                                map.removeLayer(routeLine);
                                routeLine = null;
                            }
                        }
                    })
                    .catch(err => {
                        console.error("Error fetching rider location:", err);
                    });
            }

            function drawRoute(start, end) {
                const apiKey = '5b3ce3597851110001cf62482a9443360351456aad8e8b2d7e75c259'; // OpenRouteService demo key

                const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=${apiKey}&start=${start[0]},${start[1]}&end=${end[0]},${end[1]}`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        const coords = data.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);

                        if (routeLine) {
                            map.removeLayer(routeLine);
                        }

                        routeLine = L.polyline(coords, { color: 'blue', weight: 4 }).addTo(map);
                        map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
                    })
                    .catch(error => {
                        console.error('Error fetching route:', error);
                    });
            }

            fetchRiderLocation();
            setInterval(fetchRiderLocation, 5000);
        </script>
    </body>
</html>
