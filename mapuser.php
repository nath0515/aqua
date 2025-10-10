<?php 
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require 'session.php';
    require 'db.php';
    require 'notification_helper.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    
    // Get notifications
    $notifications = getNotifications($conn, $user_id, $role_id);
    $location_id = $_GET['location_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, longitude, latitude,address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!empty($location_id)){
        $stmt = $conn->prepare("SELECT * FROM user_locations WHERE location_id = :location_id");
        $stmt->execute(['location_id' => $location_id]);
        $user_location = $stmt->fetch();
    }
    

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
        <title>View Location - AquaDrop</title>
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
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .location-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 15px;
                color: white;
                padding: 2rem;
                margin-bottom: 2rem;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }
            
            .location-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
                pointer-events: none;
                border-radius: 15px;
            }
            
            .location-card .card-content {
                position: relative;
                z-index: 2;
            }
            
            .location-card .location-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
                opacity: 0.9;
            }
            
            .location-card .location-label {
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 1rem;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            
            .location-card .address-text {
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 1.5rem;
                opacity: 0.95;
            }
            
            .location-card .coordinates {
                background: rgba(255,255,255,0.2);
                border-radius: 8px;
                padding: 0.75rem;
                font-family: monospace;
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }
            
            .location-card .action-buttons {
                display: flex;
                gap: 1rem;
                flex-wrap: wrap;
            }
            
            .btn-location {
                border-radius: 25px;
                padding: 0.75rem 1.5rem;
                font-weight: 600;
                border: none;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .btn-location:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .btn-edit {
                background: rgba(255,255,255,0.9);
                color: #667eea;
            }
            
            .btn-edit:hover {
                background: white;
                color: #667eea;
            }
            
            .btn-back {
                background: rgba(108, 117, 125, 0.9);
                color: white;
            }
            
            .btn-back:hover {
                background: #6c757d;
                color: white;
            }
            
            .header-section {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2rem;
                border-radius: 15px;
                margin-bottom: 2rem;
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            }
            
            .info-section {
                background: white;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 1.5rem;
            }
            
            .info-section h5 {
                color: #667eea;
                margin-bottom: 1rem;
                font-weight: 600;
            }
            
            .info-item {
                display: flex;
                align-items: center;
                margin-bottom: 0.75rem;
                padding: 0.5rem;
                background: #f8f9fa;
                border-radius: 8px;
            }
            
            .info-item i {
                color: #667eea;
                margin-right: 0.75rem;
                width: 20px;
            }
            
            .fade-in {
                animation: fadeIn 0.5s ease-in;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 220px; height: 60px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
             <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php echo renderNotificationBadge($notifications['unread_count']); ?>
                        <span class="visually-hidden">unread messages</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                         <?php echo renderNotificationDropdown($notifications['recent_notifications'], $unread_count, $user_id, $role_id); ?>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <?php 
                    $sql = "SELECT rs FROM users WHERE user_id = :user_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $rs = $stmt->fetchColumn();
                    if($rs == 0):?>
                        <li><a class="dropdown-item" id="apply-reseller">Apply as Reseller</a></li>
                        <script>

                            document.addEventListener('DOMContentLoaded', function () {
                            const applyBtn = document.getElementById('apply-reseller');

                            if (!applyBtn) return;

                            applyBtn.addEventListener('click', function (e) {
                                e.preventDefault();

                                fetch('check_application_status.php', {
                                    method: 'GET',
                                    credentials: 'same-origin'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        Swal.fire('Error', data.error, 'error');
                                        return;
                                    }

                                    if (data.exists) {
                                        switch (data.status.toLowerCase()) {
                                            case 'pending':
                                                Swal.fire('Application Pending', 'You already have a pending reseller application under review.', 'info');
                                                break;
                                            case 'rejected':
                                                Swal.fire({
                                                    title: 'Application Rejected',
                                                    html: 'Unfortunately, your reseller application was not approved.' +
                                                        (data.reason ? '<br><strong>Reason:</strong> ' + data.reason : ''),
                                                    icon: 'warning'
                                                });
                                                break;
                                            case 'approved':
                                                Swal.fire('Already Approved', 'You are already a reseller.', 'success');
                                                break;
                                            default:
                                                Swal.fire('Notice', 'Your application status: ' + data.status, 'info');
                                        }
                                    } else {
                                        Swal.fire({
                                            title: 'Apply as Reseller',
                                            text: 'Are you sure you want to submit a reseller application? Our team will review it promptly.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonText: 'Yes, apply now',
                                            cancelButtonText: 'Cancel'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Show second Swal with file upload
                                                Swal.fire({
                                                    title: 'Upload Valid ID',
                                                    text: 'Please upload a clear image of a valid government-issued ID.',
                                                    input: 'file',
                                                    inputAttributes: {
                                                        accept: 'image/*',
                                                        'aria-label': 'Upload your ID'
                                                    },
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Submit Application',
                                                    cancelButtonText: 'Cancel'
                                                }).then((uploadResult) => {
                                                    if (uploadResult.isConfirmed) {
                                                        const file = uploadResult.value;

                                                        if (!file) {
                                                            Swal.fire('Error', 'No file selected. Please try again.', 'error');
                                                            return;
                                                        }

                                                        const formData = new FormData();
                                                        formData.append('id_image', file);

                                                        fetch('apply.php', {
                                                            method: 'POST',
                                                            body: formData
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success) {
                                                                Swal.fire('Success', 'Your reseller application has been submitted successfully.', 'success');
                                                            } else {
                                                                Swal.fire('Error', data.message || 'Failed to submit application.', 'error');
                                                            }
                                                        })
                                                        .catch(() => {
                                                            Swal.fire('Error', 'An unexpected error occurred while submitting your application.', 'error');
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire('Error', 'Failed to check your application status. Please try again later.', 'error');
                                });
                            });
                        });

                        </script>
                <?php endif; ?>
                        <li><a class="dropdown-item" href="addresses.php">My Addresses</a></li>
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
                    <div class="container-fluid px-4">
                        <!-- Header Section -->
                        <div class="header-section">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h1 class="h2 mb-2">
                                        <i class="fas fa-map-marker-alt me-3"></i>
                                        View Delivery Location
                                    </h1>
                                    <p class="mb-0 opacity-75">
                                        Review and manage your saved delivery location
                                    </p>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <a href="addresses.php" class="btn btn-outline-light">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Addresses
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Map Section -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-map me-2"></i>
                                            Location Map
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="map"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Location Details Card -->
                                <div class="location-card fade-in" style="animation-delay: 0.2s;">
                                    <div class="card-content">
                                        <div class="location-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="location-label">
                                            <?php echo htmlspecialchars($user_location['label'] ?? 'My Location'); ?>
                                        </div>
                                        <div class="address-text">
                                            <?php echo htmlspecialchars($user_location['address'] ?? 'Address not specified'); ?>
                                        </div>
                                        <div class="coordinates">
                                            <small>
                                                <i class="fas fa-crosshairs me-1"></i>
                                                <?php echo number_format($user_location['latitude'] ?? 0, 6); ?>, <?php echo number_format($user_location['longitude'] ?? 0, 6); ?>
                                            </small>
                                        </div>
                                        <div class="action-buttons">
                                            <a href="add_location.php?location_id=<?php echo $location_id; ?>" class="btn-location btn-edit">
                                                <i class="fas fa-edit"></i>
                                                Edit Location
                                            </a>
                                            <a href="addresses.php" class="btn-location btn-back">
                                                <i class="fas fa-arrow-left"></i>
                                                Back to List
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Information -->
                                <div class="info-section fade-in" style="animation-delay: 0.4s;">
                                    <h5><i class="fas fa-info-circle me-2"></i>Location Details</h5>
                                    
                                    <div class="info-item">
                                        <i class="fas fa-tag"></i>
                                        <div>
                                            <strong>Label:</strong><br>
                                            <?php echo htmlspecialchars($user_location['label'] ?? 'Not specified'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <i class="fas fa-home"></i>
                                        <div>
                                            <strong>Address:</strong><br>
                                            <?php echo htmlspecialchars($user_location['address'] ?? 'Not specified'); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($user_location['barangay_id'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-building"></i>
                                        <div>
                                            <strong>Barangay:</strong><br>
                                            <?php 
                                                $stmt = $conn->prepare("SELECT barangay_name FROM table_barangay WHERE barangay_id = :barangay_id");
                                                $stmt->execute(['barangay_id' => $user_location['barangay_id']]);
                                                $barangay = $stmt->fetch();
                                                echo htmlspecialchars($barangay['barangay_name'] ?? 'Not specified');
                                            ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-item">
                                        <i class="fas fa-calendar"></i>
                                        <div>
                                            <strong>Created:</strong><br>
                                            <?php echo isset($user_location['created_at']) ? date('M d, Y g:i A', strtotime($user_location['created_at'])) : 'Not available'; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="info-section fade-in" style="animation-delay: 0.6s;">
                                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="add_location.php" class="btn btn-success">
                                            <i class="fas fa-plus me-2"></i>Add New Address
                                        </a>
                                        <a href="costumerorder.php" class="btn btn-primary">
                                            <i class="fas fa-shopping-cart me-2"></i>Place Order
                                        </a>
                                        <a href="addresses.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-list me-2"></i>View All Addresses
                                        </a>
                                    </div>
                                </div>
                            </div>
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
        const map = L.map('map').setView(
            [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 
            17
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add marker for the saved location
        if (<?php echo $savedLat !== null ? 'true' : 'false'; ?>) {
            const savedMarker = L.marker([<?php echo $savedLat; ?>, <?php echo $savedLng; ?>], {
                icon: L.icon({
                    iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
                    iconSize: [35, 35]
                })
            }).addTo(map).bindPopup("📍 <?php echo htmlspecialchars($user_location['label'] ?? 'Your Location'); ?>").openPopup();

            // Center map on the saved location
            map.setView([<?php echo $savedLat; ?>, <?php echo $savedLng; ?>], 17);
        }

        // Add some interactive features
        map.on('click', function(e) {
            // Show coordinates when clicking on map
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            
            L.popup()
                .setLatLng(e.latlng)
                .setContent(`Coordinates: ${lat}, ${lng}`)
                .openOn(map);
        });
        </script>
    </body>
</html>
