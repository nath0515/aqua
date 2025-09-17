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
    // Get notifications using helper for consistency
    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;

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
        $sql = "SELECT orders.order_id, ul.latitude, ul.longitude, ul.label, ul.address 
                FROM orders 
                LEFT JOIN user_locations ul ON orders.location_id = ul.location_id
                WHERE ul.latitude IS NOT NULL AND ul.longitude IS NOT NULL 
                AND orders.status_id = 3 
                AND orders.rider = :rider_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':rider_id', $user_id);
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
              <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
                        <?php if ($unread_count > 0): ?>
                            <span id="notificationBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <?php if (empty($recent_notifications)): ?>
                            <li><a class="dropdown-item text-muted" href="#">No notifications</a></li>
                        <?php else: ?>
                            <?php foreach($recent_notifications as $notification): ?>
                                <li><a class="dropdown-item" href="process_readnotification.php?id=<?php echo $notification['activitylogs_id']?>&destination=<?php echo $notification['destination']?>"><?php echo $notification['message'];?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                        // Commented out Off Duty toggle
                        /*
                        $sql = "SELECT status FROM rider_status WHERE user_id = :user_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $status_rider = $row ? $row['status'] : 0;
                        */
                        ?>
                        <!-- <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="return confirmToggle(event, <?= $status_rider ?>)">
                            <?php echo ($status_rider) ? 'Off Duty' : 'On Duty'; ?>
                        </a>
                        </li> -->
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
                            <a class="nav-link" href="rider_ratings.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-star"></i></div>
                            My Ratings
                            </a>
                            <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                            </a>
                            <a class="nav-link" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
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
                                
                        <!-- Delivery Orders Information -->
                        <div class="row mt-3">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-truck me-2"></i>
                                            My Delivery Orders
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($endCoordinatesArray)): ?>
                                            <p class="text-muted mb-0">No active delivery orders assigned to you.</p>
                                        <?php else: ?>
                                            <p class="text-muted mb-2">You have <strong><?php echo count($endCoordinatesArray); ?></strong> delivery order(s):</p>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Order ID</th>
                                                            <th>Status</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="deliveryTableBody">
                                                        <?php foreach ($endCoordinatesArray as $index => $delivery): ?>
                                                            <tr data-order-id="<?php echo $delivery['order_id']; ?>" data-lat="<?php echo $delivery['lat']; ?>" data-lon="<?php echo $delivery['lon']; ?>">
                                                                <td><span class="badge bg-primary"><?php echo $index + 1; ?></span></td>
                                                                <td>Order #<?php echo $delivery['order_id']; ?></td>
                                                                <td><span class="badge bg-warning text-dark">For Delivery</span></td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-outline-primary select-delivery-btn" data-order-id="<?php echo $delivery['order_id']; ?>">
                                                                        <i class="fas fa-map-marker-alt me-1"></i>Select
                                                                    </button>
                                                                    <button class="btn btn-sm btn-success complete-delivery-btn" data-order-id="<?php echo $delivery['order_id']; ?>" style="display: none;">
                                                                        <i class="fas fa-check me-1"></i>Complete
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            Map Instructions
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-info-circle text-primary me-2"></i>Blue line shows your delivery route</li>
                                            <li><i class="fas fa-truck text-dark me-2"></i>Black icon shows your current location</li>
                                            <li><i class="fas fa-map-pin text-danger me-2"></i>Colored pins show all delivery locations</li>
                                            <li><i class="fas fa-list-ol text-info me-2"></i>Click "Select" to view route to specific delivery</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i>Click "Complete" when you reach the destination</li>
                                            <li><i class="fas fa-mouse-pointer text-warning me-2"></i>Click any pin to select that delivery</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button id="completeBtn" class="btn btn-success btn-lg" <?php echo empty($endCoordinatesArray) ? 'disabled' : ''; ?>>
                                <i class="fas fa-check-circle me-2"></i>
                                Complete Delivery
                            </button>
                        </div>               
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

        function sendLocationToServer(lat, lon) {
            $.post("update_rider_location.php", {
                latitude: lat,
                longitude: lon,
                user_id: <?php echo $_SESSION['user_id']?>
                
            }).fail(function(xhr, status, error) {
                console.error("Failed to send location:", error);
            });
        }
        
        var map = L.map('map').setView([14.1916, 121.1378], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var currentStartCoord = null;
        var currentStartMarker = null;
        var currentRouteLine = null;
        var destinationMarkers = [];
        var selectedOrderId = null;

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
                const riderIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/11431/11431942.png', // You can replace this with your preferred image
                iconSize: [35, 35],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });

            if (!currentStartMarker) {
                currentStartMarker = L.marker(currentStartCoord, { icon: riderIcon }).addTo(map).bindPopup("You (Rider)").openPopup();
            } else {
                currentStartMarker.setLatLng(currentStartCoord).setIcon(riderIcon);
            }
            } else {
                currentStartMarker.setLatLng(currentStartCoord);
            }

            // Show all delivery points on the map
            if (endCoordinates.length > 0) {
                // Clear existing markers
                destinationMarkers.forEach(marker => map.removeLayer(marker));
                destinationMarkers = [];
                
                // Add markers for all delivery points with different colors
                endCoordinates.forEach((coord, index) => {
                    // Use different colors for different delivery points
                    const colors = ['red', 'orange', 'yellow', 'green', 'blue', 'purple'];
                    const color = colors[index % colors.length];
                    
                    const customIcon = L.icon({
                        iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-${color}.png`,
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });
                    
                    const marker = L.marker([coord.lat, coord.lon], { icon: customIcon })
                        .addTo(map)
                        .bindPopup(`<strong>Delivery #${index + 1}</strong><br>Order #${coord.order_id}<br><strong>${coord.label || 'Delivery Location'}</strong><br><small>${coord.address || ''}</small><br><button class='btn btn-sm btn-primary mt-2' onclick='selectDelivery(${coord.order_id})'>Select This Delivery</button>`)
                        .on('click', function() {
                            selectDelivery(coord.order_id);
                        });
                    
                    destinationMarkers.push(marker);
                });
                
                // If no delivery is selected, show route to closest one
                if (!selectedOrderId) {
                    const closestIndex = findClosestEndCoordinate(currentStartCoord, endCoordinates);
                    const closest = endCoordinates[closestIndex];
                    fetchRoute(currentStartCoord, [closest.lat, closest.lon]);
                    
                    // Highlight the closest delivery in the table
                    $('#deliveryTableBody tr').removeClass('table-primary');
                    $(`#deliveryTableBody tr[data-order-id="${closest.order_id}"]`).addClass('table-primary');
                }
            }
        }



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
            
        </script>

        <!-- Mark as Delivered Modal -->
        <div class="modal fade" id="markDeliveredModal" tabindex="-1" aria-labelledby="markDeliveredModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="markDeliveredModalLabel">
                            <i class="fas fa-check-circle me-2"></i>
                            Mark Order as Delivered
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="deliveryForm" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" id="modalOrderId">
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Order #<span id="modalOrderNumber"></span></strong> - Please upload proof of delivery to mark this order as delivered.
                            </div>
                            
                            <div class="mb-3">
                                <label for="proof_of_delivery" class="form-label">
                                    <i class="fas fa-camera me-2"></i>
                                    Proof of Delivery Image
                                </label>
                                <input type="file" class="form-control" id="proof_of_delivery" name="proof_of_delivery" 
                                       accept="image/*" required>
                                <div class="form-text">Upload a photo showing the delivery was completed (e.g., customer signature, delivered items, etc.)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery_notes" class="form-label">
                                    <i class="fas fa-sticky-note me-2"></i>
                                    Delivery Notes (Optional)
                                </label>
                                <textarea class="form-control" id="delivery_notes" name="delivery_notes" rows="3" 
                                          placeholder="Any additional notes about the delivery..."></textarea>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Once confirmed, this order will be marked as delivered and cannot be undone.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="confirmDeliveryBtn">
                            <i class="fas fa-check me-2"></i>
                            Confirm Delivery
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Multiple delivery management functions
            function selectDelivery(orderId) {
                selectedOrderId = orderId;
                
                // Update table UI
                $('.select-delivery-btn').show();
                $('.complete-delivery-btn').hide();
                $(`.select-delivery-btn[data-order-id="${orderId}"]`).hide();
                $(`.complete-delivery-btn[data-order-id="${orderId}"]`).show();
                
                // Find the selected delivery coordinates
                const selectedDelivery = endCoordinates.find(coord => coord.order_id == orderId);
                if (selectedDelivery && currentStartCoord) {
                    // Clear existing route
                    if (currentRouteLine) {
                        map.removeLayer(currentRouteLine);
                        currentRouteLine = null;
                    }
                    
                    // Show route to selected delivery
                    fetchRoute(currentStartCoord, [selectedDelivery.lat, selectedDelivery.lon]);
                    
                    // Center map on selected delivery
                    map.setView([selectedDelivery.lat, selectedDelivery.lon], 15);
                }
                
                // Highlight the selected row
                $('#deliveryTableBody tr').removeClass('table-primary');
                $(`#deliveryTableBody tr[data-order-id="${orderId}"]`).addClass('table-primary');
                
                // Show success message
                Swal.fire({
                    icon: 'info',
                    title: 'Delivery Selected!',
                    text: `Route to Order #${orderId} is now displayed on the map.`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
            
            function removeDeliveryFromMap(orderId) {
                // Remove from endCoordinates array
                const index = endCoordinates.findIndex(coord => coord.order_id == orderId);
                if (index > -1) {
                    endCoordinates.splice(index, 1);
                }
                
                // Remove marker from map
                if (destinationMarkers[index]) {
                    map.removeLayer(destinationMarkers[index]);
                    destinationMarkers.splice(index, 1);
                }
                
                // Remove from table
                $(`#deliveryTableBody tr[data-order-id="${orderId}"]`).remove();
                
                // Reset selection if this was the selected delivery
                if (selectedOrderId == orderId) {
                    selectedOrderId = null;
                    $('.select-delivery-btn').show();
                    $('.complete-delivery-btn').hide();
                    $('#deliveryTableBody tr').removeClass('table-primary');
                }
                
                // Update delivery count
                const remainingDeliveries = endCoordinates.length;
                if (remainingDeliveries === 0) {
                    $('#completeBtn').prop('disabled', true);
                    Swal.fire({
                        title: 'All deliveries completed!',
                        icon: 'success',
                        confirmButtonText: 'Return to Shop'
                    }).then(() => {
                        const shopLatLng = [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>];
                        L.marker(shopLatLng).addTo(map).bindPopup("Shop").openPopup();
                        fetchRoute(currentStartCoord, shopLatLng);
                    });
                }
            }
            
            // Mark as Delivered functionality for maps
            $(document).ready(function() {
                // Handle Select Delivery button clicks
                $(document).on('click', '.select-delivery-btn', function() {
                    const orderId = $(this).data('order-id');
                    selectDelivery(orderId);
                });
                
                // Show modal when "Complete Delivery" button is clicked
                $("#completeBtn").click(function() {
                    if (!selectedOrderId) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Delivery Selected',
                            text: 'Please select a delivery from the table first.',
                            confirmButtonColor: '#ffc107'
                        });
                        return;
                    }
                    
                    $("#modalOrderId").val(selectedOrderId);
                    $("#modalOrderNumber").text(selectedOrderId);
                    $("#markDeliveredModal").modal('show');
                });
                
                // Handle delivery confirmation
                $("#confirmDeliveryBtn").click(function() {
                    const form = document.getElementById('deliveryForm');
                    const formData = new FormData(form);
                    
                    // Validate file upload
                    const fileInput = document.getElementById('proof_of_delivery');
                    if (!fileInput.files[0]) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Proof of Delivery Required',
                            text: 'Please upload a proof of delivery image.',
                            confirmButtonColor: '#dc3545'
                        });
                        return;
                    }
                    
                    // Show loading
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                    
                    $.ajax({
                        url: 'process_mark_delivered.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log('Raw response:', response);
                            
                            // Check if response is empty or null
                            if (!response) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Empty Response',
                                    text: 'Server returned empty response. Please try again.',
                                    confirmButtonColor: '#dc3545'
                                });
                                return;
                            }
                            
                            // jQuery automatically parses JSON, so response is already an object
                            const result = response;
                            console.log('Parsed result:', result);
                            if (result.success) {
                                    // Close modal
                                    $("#markDeliveredModal").modal('hide');
                                    
                                    // Update map (remove delivery point)
                                    removeDeliveryFromMap(selectedOrderId);

                                    if (currentRouteLine) {
                                        map.removeLayer(currentRouteLine);
                                        currentRouteLine = null;
                                    }

                                    // Show success message
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Delivery Confirmed!',
                                        text: 'Order has been marked as delivered successfully.',
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        // Check if all deliveries are complete
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
                                            // Update to next delivery
                                            updateStartLocation({
                                                coords: {
                                                    latitude: currentStartCoord[0],
                                                    longitude: currentStartCoord[1]
                                                }
                                            });
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: result.message || 'Failed to mark order as delivered.',
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Network Error',
                                text: 'Failed to connect to server. Please try again.',
                                confirmButtonColor: '#dc3545'
                            });
                        },
                        complete: function() {
                            $("#confirmDeliveryBtn").prop('disabled', false).html('<i class="fas fa-check me-2"></i>Confirm Delivery');
                        }
                    });
                });
            });
        </script>
            <?php if ($notification_success > 0): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Notifications Marked as Read!',
                    text: '<?php echo $notification_success; ?> notification(s) have been marked as read.',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>
        <?php endif; ?>
</body>
</html>


