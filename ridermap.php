<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");
    }else if ($role_id == 2){
        header("Location: home.php");
    }

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

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
        $sql = "SELECT orders.order_id, latitude, longitude FROM orders 
                JOIN user_details ON orders.user_id = user_details.user_id
                WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status_id = 3";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $endCoordinatesArray[] = [
                'order_id' => $row['order_id'],
                'lat' => $row['latitude'],
                'lon' => $row['longitude']
            ];
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
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <style>
        #map { height: 500px; width: 100%; }
        #completeBtn { margin: 10px; padding: 10px 20px; font-size: 16px; }
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
                    <a class="nav-link position-relative mt-2 " href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <li><a class="dropdown-item" href="riderprofile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <li><a id="installBtn" class="dropdown-item" style="display: none;">Install AquaDrop</a></li>
                        <?php 
                        $sql = "SELECT status FROM rider_status WHERE user_id = :user_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $status_rider = $row ? $row['status'] : 0;
                        ?>
                        <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="return confirmToggle(event, <?= $status_rider ?>)">
                            <?php echo ($status_rider) ? 'Off Duty' : 'On Duty'; ?>
                        </a>
                        </li>
                        <div id="loadingOverlay">
                            <div class="spinner"></div>
                        </div>
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
                            <a class="nav-link" href="riderdashboard.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Delivery Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="deliveryhistory.php">Delivered History</a>
                                    <a class="nav-link" href="ridermap.php">Maps</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-calendar-week"></i></i></div>
                            Attendance
                            </a>
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
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Maps</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Delivery Management</li>
                            <li class="breadcrumb-item active">Maps</li>
                        </ol>
                        <div id="map"></div>
                        <button id="completeBtn">Complete Delivery</button>               
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
            let deferredPrompt;

            // Listen for the beforeinstallprompt event
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault(); // Prevent automatic prompt
                deferredPrompt = e; // Save the event for later
                document.getElementById('installBtn').style.display = 'inline-block'; // Show the button
            });

            // Install button click handler
            document.getElementById('installBtn').addEventListener('click', async () => {
                if (!deferredPrompt) return;

                document.getElementById('loadingOverlay').style.display = 'flex';

                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;

                document.getElementById('loadingOverlay').style.display = 'none';

                if (outcome === 'accepted') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Installation Complete',
                        text: 'AquaDrop has been successfully installed!',
                        confirmButtonColor: '#0077b6'
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Installation Cancelled',
                        text: 'You can install AquaDrop anytime!',
                        confirmButtonColor: '#0077b6'
                    });
                }

                deferredPrompt = null;
                document.getElementById('installBtn').style.display = 'none';
            });
        </script>

        <!-- PWA: Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js') // ✅ Root-level path
                    .then(reg => console.log('✅ Service Worker registered:', reg))
                    .catch(err => console.error('❌ Service Worker registration failed:', err));
            }
        </script>
        <script>
            $(document).ready(function() {
                $("#editOrderBtn").click(function() {
                    var orderId = $(this).data("id");

                    $.ajax({
                        url: "process_getorderdata.php",
                        type: "POST",
                        data: { order_id: orderId },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                const orderItems = response.data2;

                                $("#editStatusId").val(response.data.status_id);


                                let itemsHtml = '<h5>Order Items:</h5>';
                                orderItems.forEach(item => {
                                    itemsHtml += `
                                        <div>
                                            <p>Item: ${item.product_name}</p>
                                            <p>Quantity: ${item.quantity}</p>
                                            <p>Price: ₱${item.price}</p>
                                        </div>
                                    `;
                                });
                                $('#orderItemsContainer').html(itemsHtml);
                                
                            } else {
                                alert("Error fetching product data.");
                            }
                        },
                        error: function() {
                            alert("Failed to fetch product details.");
                        }
                    });
                });
            });
        </script>
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

            // ✅ Send location to server
            sendLocationToServer(position.coords.latitude, position.coords.longitude);

            if (!currentStartMarker) {
                currentStartMarker = L.marker(currentStartCoord).addTo(map).bindPopup("You").openPopup();
            } else {
                currentStartMarker.setLatLng(currentStartCoord);
            }

            if (endCoordinates.length > 0) {
                currentDestinationIndex = findClosestEndCoordinate(currentStartCoord, endCoordinates);
                const next = endCoordinates[currentDestinationIndex];
                const destinationCoord = [next.lat, next.lon];

                // Define a red marker icon
                const redIcon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                if (!destinationMarker) {
                    destinationMarker = L.marker(destinationCoord, { icon: redIcon }).addTo(map).bindPopup("Destination").openPopup();
                } else {
                    destinationMarker.setLatLng(destinationCoord).setIcon(redIcon).openPopup();
                }
                fetchRoute(currentStartCoord, destinationCoord);
            }
        }

        function completeDelivery() {
            if (currentDestinationIndex === -1) return;

            const orderId = endCoordinates[currentDestinationIndex].order_id;

            Swal.fire({
                title: 'Complete this delivery?',
                html: `
                    <p>Please upload proof of delivery (photo, signature, etc.):</p>
                    <input type="file" id="deliveryProofFile" class="swal2-input" accept="image/*,.pdf">
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Upload & Complete',
                preConfirm: () => {
                    const fileInput = document.getElementById('deliveryProofFile');
                    const file = fileInput.files[0];
                    if (!file) {
                        Swal.showValidationMessage('Please select a file');
                        return false;
                    }
                    return file;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const file = result.value;
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('file', file);

                    $.ajax({
                        url: 'update_delivery_status.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
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
                                        confirmButtonText: 'Return to Shop'
                                    }).then(() => {
                                        const shopLatLng = [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>];
                                        L.marker(shopLatLng).addTo(map).bindPopup("Shop").openPopup();
                                        fetchRoute(currentStartCoord, shopLatLng);
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Delivery completed!',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    updateStartLocation({
                                        coords: {
                                            latitude: currentStartCoord[0],
                                            longitude: currentStartCoord[1]
                                        }
                                    });
                                }
                            } else {
                                Swal.fire('Error', response.error || 'Failed to update.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'AJAX upload failed.', 'error');
                        }
                    });
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
        <script>
            function sendLocationToServer(lat, lon) {
                $.post("update_rider_location.php", {
                    latitude: lat,
                    longitude: lon,
                    user_id: <?php echo $_SESSION['user_id']?>
                    
                }).fail(function(xhr, status, error) {
                    console.error("Failed to send location:", error);
                });
            }
        </script>
    </body>
</html>


