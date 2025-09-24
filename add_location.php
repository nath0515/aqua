<?php 
    require 'session.php';
    require 'db.php';
    require 'notification_helper.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    
    // Get notifications
    $notifications = getNotifications($conn, $user_id, $role_id);
    $location_id = isset($_GET['location_id']) ? $_GET['location_id'] : null;

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, longitude, latitude,address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if(isset($location_id)){
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
        <title>Add Delivery Address - AquaDrop</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
        <style>
            #map {
                height: 400px;
                width: 100%;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .address-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 15px;
                color: white;
                padding: 2rem;
                margin-bottom: 2rem;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }
            
            .form-control, .form-select {
                border-radius: 10px;
                border: 2px solid #e9ecef;
                transition: all 0.3s ease;
            }
            
            .form-control:focus, .form-select:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            
            .btn-custom {
                border-radius: 10px;
                padding: 12px 24px;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none;
            }
            
            .btn-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            }
            
            .search-container {
                position: relative;
                margin-bottom: 1.5rem;
            }
            
            .search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                max-height: 200px;
                overflow-y: auto;
                z-index: 1000;
                display: none;
            }
            
            .search-result-item {
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
                transition: background-color 0.2s;
            }
            
            .search-result-item:hover {
                background-color: #f8f9fa;
            }
            
            .search-result-item:last-child {
                border-bottom: none;
            }
            
            .location-preview {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 1rem;
                margin-top: 1rem;
                border-left: 4px solid #28a745;
            }
            
            .coordinate-display {
                background: #e9ecef;
                border-radius: 8px;
                padding: 0.5rem;
                font-family: monospace;
                font-size: 0.9rem;
                color: #495057;
            }
            
            .step-indicator {
                display: flex;
                justify-content: space-between;
                margin-bottom: 2rem;
                position: relative;
            }
            
            .step {
                display: flex;
                flex-direction: column;
                align-items: center;
                position: relative;
                z-index: 2;
            }
            
            .step-circle {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #e9ecef;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                color: #6c757d;
                margin-bottom: 0.5rem;
                transition: all 0.3s ease;
            }
            
            .step.active .step-circle {
                background: #667eea;
                color: white;
            }
            
            .step.completed .step-circle {
                background: #28a745;
                color: white;
            }
            
            .step-line {
                position: absolute;
                top: 20px;
                left: 20px;
                right: 20px;
                height: 2px;
                background: #e9ecef;
                z-index: 1;
            }
            
            .step-line-fill {
                height: 100%;
                background: #667eea;
                width: 0%;
                transition: width 0.3s ease;
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
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step-line">
                                <div class="step-line-fill" id="stepLineFill"></div>
                            </div>
                            <div class="step active" id="step1">
                                <div class="step-circle">1</div>
                                <small>Search Address</small>
                            </div>
                            <div class="step" id="step2">
                                <div class="step-circle">2</div>
                                <small>Pin Location</small>
                            </div>
                            <div class="step" id="step3">
                                <div class="step-circle">3</div>
                                <small>Save Address</small>
                            </div>
                        </div>

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h3 mb-0 text-gray-800">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    Add Delivery Address
                                </h1>
                                <p class="text-muted">Set up your delivery location for faster checkout</p>
                            </div>
                            <a href="addresses.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Addresses
                            </a>
                        </div>

                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Address Search Section -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-search me-2"></i>
                                            Step 1: Search for Your Address
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="search-container">
                                            <label for="addressSearch" class="form-label">
                                                <i class="fas fa-search me-2"></i>Search Address
                                            </label>
                                            <input type="text" 
                                                   id="addressSearch" 
                                                   class="form-control form-control-lg" 
                                                   placeholder="Enter your address in Santa Cruz, Laguna (e.g., 123 Main St)"
                                                   autocomplete="off">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>
                                                We only deliver within Santa Cruz, Laguna area
                                            </div>
                                            <div class="search-results" id="searchResults"></div>
                                        </div>
                                        
                                        <div class="location-preview" id="locationPreview" style="display: none;">
                                            <h6><i class="fas fa-map-pin me-2"></i>Selected Location</h6>
                                            <p id="selectedAddress" class="mb-2"></p>
                                            <div class="coordinate-display">
                                                <small>Coordinates: <span id="selectedCoordinates"></span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Map Section -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-map me-2"></i>
                                            Step 2: Fine-tune Your Location
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="map"></div>
                                                                                    <div class="mt-3">
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Click on the map to adjust your exact delivery location
                                                </p>
                                                <div class="alert alert-info" id="reverseGeocodeInfo" style="display: none;">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                                    <strong>Auto-detecting address...</strong> We're automatically filling in the address details for you.
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Address Details Form -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-edit me-2"></i>
                                            Step 3: Address Details
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="addressForm">
                                            <div class="mb-3">
                                                <label for="locationLabel" class="form-label">
                                                    <i class="fas fa-tag me-2"></i>Location Label
                                                </label>
                                                <input type="text" 
                                                       id="locationLabel" 
                                                       class="form-control" 
                                                       placeholder="e.g., Home, Work, Apartment 3B"
                                                       value="<?php echo isset($user_location['label']) ? htmlspecialchars($user_location['label']) : ''; ?>"
                                                       required>
                                                <div class="form-text">Give this location a memorable name</div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-map me-2"></i>Province
                                                </label>
                                                <select name="province" class="form-select" disabled>
                                                    <?php 
                                                        $stmt = $conn->prepare("SELECT * FROM table_province WHERE province_id = 20");
                                                        $stmt->execute();
                                                        $province = $stmt->fetch();
                                                    ?>
                                                    <option value="<?php echo $province['province_id']?>"><?php echo $province['province_name']?></option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-city me-2"></i>Municipality
                                                </label>
                                                <select name="municipality" class="form-select" disabled>
                                                    <?php 
                                                        $stmt = $conn->prepare("SELECT * FROM table_municipality WHERE municipality_id = 431");
                                                        $stmt->execute();
                                                        $municipality = $stmt->fetch();
                                                    ?>
                                                    <option value="<?php echo $municipality['municipality_id']?>"><?php echo $municipality['municipality_name']?></option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="barangay_id" class="form-label">
                                                    <i class="fas fa-building me-2"></i>Barangay
                                                </label>
                                                <select name="barangay_id" id="barangay_id" class="form-select" required>
                                                    <option value="">Select Barangay</option>
                                                    <?php 
                                                        $stmt = $conn->prepare("SELECT * FROM table_barangay WHERE municipality_id = 431 ORDER BY barangay_name");
                                                        $stmt->execute();
                                                        $barangay = $stmt->fetchAll();
                                                    ?>
                                                    <?php foreach($barangay as $row): ?>
                                                        <option value="<?php echo $row['barangay_id']; ?>" 
                                                                <?php if (isset($user_location['barangay_id']) && $user_location['barangay_id'] == $row['barangay_id']) echo 'selected'; ?>>
                                                            <?php echo $row['barangay_name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="address" class="form-label">
                                                    <i class="fas fa-home me-2"></i>Street Address
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="address" 
                                                       id="address" 
                                                       value="<?php echo isset($user_location['address']) ? htmlspecialchars($user_location['address']) : ''; ?>" 
                                                       placeholder="House/Unit Number, Street Name"
                                                       required>
                                                <div class="form-text">Your specific street address or building details</div>
                                            </div>


                                        </form>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button id="saveLocationBtn" class="btn btn-success btn-custom" disabled>
                                                <i class="fas fa-save me-2"></i>
                                                Save Address
                                            </button>
                                            <button id="editLocationBtn" class="btn btn-warning btn-custom" style="display: none;">
                                                <i class="fas fa-edit me-2"></i>
                                                Edit Location
                                            </button>
                                            <a href="addresses.php" class="btn btn-outline-secondary btn-custom">
                                                <i class="fas fa-times me-2"></i>
                                                Cancel
                                            </a>
                                        </div>
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
        // Initialize map
        const map = L.map('map').setView(
            [<?php echo $startCoordinates['lat']; ?>, <?php echo $startCoordinates['lon']; ?>], 
            17
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Variables
        let savedLat = <?php echo $savedLat !== null ? json_encode(floatval($savedLat)) : 'null'; ?>;
        let savedLng = <?php echo $savedLng !== null ? json_encode(floatval($savedLng)) : 'null'; ?>;
        let selectedLat = null;
        let selectedLng = null;
        let userMarker = null;
        let isLocationConfirmed = false;
        let searchTimeout = null;

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
            updateStepProgress(3);
            document.getElementById("saveLocationBtn").disabled = false;
        }

        // Address search functionality
        document.getElementById('addressSearch').addEventListener('input', function(e) {
            const query = e.target.value;
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            if (query.length < 3) {
                document.getElementById('searchResults').style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchAddress(query);
            }, 500);
        });

        function searchAddress(query) {
            // Add Santa Cruz, Laguna to the search query
            const searchQuery = query + ', Santa Cruz, Laguna, Philippines';
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=5`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Filter results to only show Santa Cruz, Laguna addresses
                    const filteredData = data.filter(result => {
                        const displayName = result.display_name.toLowerCase();
                        return displayName.includes('santa cruz') && displayName.includes('laguna');
                    });
                    
                    displaySearchResults(filteredData);
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        }

        function displaySearchResults(results) {
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = '';
            
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="search-result-item">No results found</div>';
            } else {
                results.forEach(result => {
                    const item = document.createElement('div');
                    item.className = 'search-result-item';
                    item.textContent = result.display_name;
                    item.addEventListener('click', () => selectSearchResult(result));
                    resultsContainer.appendChild(item);
                });
            }
            
            resultsContainer.style.display = 'block';
        }

        function selectSearchResult(result) {
            // Check delivery range first
            const lat = parseFloat(result.lat);
            const lon = parseFloat(result.lon);
            
            if (!isWithinDeliveryRange(lat, lon)) {
                Swal.fire({
                    icon: 'warning',
                    title: '📍 Outside Delivery Range',
                    text: 'This location is outside our delivery area. We only deliver within Santa Cruz, Laguna. Please select a different address.',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }
            
            document.getElementById('addressSearch').value = result.display_name;
            document.getElementById('searchResults').style.display = 'none';
            
            // Update map
            map.setView([lat, lon], 17);
            
            if (userMarker) {
                map.removeLayer(userMarker);
            }
            
            userMarker = L.marker([lat, lon], {
                icon: L.icon({
                    iconUrl: 'https://img.icons8.com/color/48/000000/marker.png',
                    iconSize: [30, 30]
                })
            }).addTo(map).bindPopup("📍 Selected Location").openPopup();
            
            selectedLat = lat;
            selectedLng = lon;
            
            // Update preview
            document.getElementById('selectedAddress').textContent = result.display_name;
            document.getElementById('selectedCoordinates').textContent = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
            document.getElementById('locationPreview').style.display = 'block';
            
            // Reverse geocode to get address details
            reverseGeocode(lat, lon);
            
            updateStepProgress(2);
        }

        // Map click handler
        map.on('click', function(e) {
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

            // Update coordinates display
            document.getElementById('selectedCoordinates').textContent = `${selectedLat.toFixed(6)}, ${selectedLng.toFixed(6)}`;
            
            // Reverse geocode to get address details
            reverseGeocode(selectedLat, selectedLng);
            
            updateStepProgress(2);
        });

        // Check if location is within Santa Cruz, Laguna delivery range
        function isWithinDeliveryRange(lat, lng) {
            // Santa Cruz, Laguna coordinates (approximate center)
            const santaCruzLat = 14.2783;
            const santaCruzLng = 121.4157;
            
            // Calculate distance in kilometers
            const distance = calculateDistance(lat, lng, santaCruzLat, santaCruzLng);
            
            // Allow delivery within 20km radius
            return distance <= 20;
        }
        
        // Calculate distance between two points
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in kilometers
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // Reverse geocoding function
        function reverseGeocode(lat, lng) {
            // Check delivery range first
            if (!isWithinDeliveryRange(lat, lng)) {
                Swal.fire({
                    icon: 'warning',
                    title: '📍 Outside Delivery Range',
                    text: 'This location is outside our delivery area. We only deliver within Santa Cruz, Laguna. Please select a location within our service area.',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }
            
            // Show loading indicator
            const addressField = document.getElementById('address');
            const barangayField = document.getElementById('barangay_id');
            const infoAlert = document.getElementById('reverseGeocodeInfo');
            
            // Show info alert
            infoAlert.style.display = 'block';
            
            // Add loading state to fields
            addressField.style.background = '#f8f9fa';
            barangayField.style.background = '#f8f9fa';
            
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Reverse geocoding result:', data);
                    
                    // Auto-populate address fields
                    if (data.address) {
                        let populatedFields = [];
                        
                        // Try to populate street address
                        if (data.address.road) {
                            let streetAddress = '';
                            if (data.address.house_number) {
                                streetAddress += data.address.house_number + ' ';
                            }
                            streetAddress += data.address.road;
                            document.getElementById('address').value = streetAddress;
                            populatedFields.push('street address');
                        }
                        
                        // Try to populate barangay
                        console.log('Full reverse geocoding response:', data.address);
                        
                        // Try multiple possible fields for barangay name
                        const possibleBarangayFields = [
                            data.address.village,
                            data.address.suburb,
                            data.address.neighbourhood,
                            data.address.city_district,
                            data.address.district,
                            data.address.quarter,
                            data.address.residential
                        ].filter(Boolean); // Remove empty values
                        
                        let barangayPopulated = false;
                        for (const barangayName of possibleBarangayFields) {
                            console.log('Trying barangay name:', barangayName);
                            const wasPopulated = populateBarangayByName(barangayName);
                            if (wasPopulated) {
                                populatedFields.push('barangay');
                                barangayPopulated = true;
                                break;
                            }
                        }
                        
                        if (!barangayPopulated) {
                            console.log('Failed to populate barangay. Tried:', possibleBarangayFields);
                            console.log('Available barangay options:', Array.from(document.getElementById('barangay_id').options).map(opt => opt.text));
                        }
                        
                        // Remove loading state
                        addressField.style.background = '';
                        barangayField.style.background = '';
                        infoAlert.style.display = 'none';
                        
                        // Show success message if fields were populated
                        if (populatedFields.length > 0) {
                            Swal.fire({
                                icon: 'success',
                                title: '📍 Address Detected!',
                                text: `We've automatically filled in: ${populatedFields.join(', ')}`,
                                timer: 3000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                    } else {
                        // Remove loading state
                        addressField.style.background = '';
                        barangayField.style.background = '';
                        infoAlert.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Reverse geocoding error:', error);
                    // Remove loading state
                    addressField.style.background = '';
                    barangayField.style.background = '';
                    infoAlert.style.display = 'none';
                });
        }
        
        // Function to populate barangay by name
        function populateBarangayByName(barangayName) {
            const barangaySelect = document.getElementById('barangay_id');
            const options = barangaySelect.options;
            
            // Convert barangay name to lowercase for comparison
            const searchName = barangayName.toLowerCase();
            
            // Common barangay name mappings
            const barangayMappings = {
                'pagsawitan': ['pagsawitan'],
                'poblacion': ['poblacion 1', 'poblacion 2', 'poblacion 3', 'poblacion 4', 'poblacion 5', 'poblacion 6', 'poblacion 7', 'poblacion 8', 'poblacion 9'],
                'san jose': ['san jose'],
                'san antonio': ['san antonio'],
                'san isidro': ['san isidro'],
                'san francisco': ['san francisco'],
                'san lorenzo': ['san lorenzo'],
                'san miguel': ['san miguel'],
                'san pablo': ['san pablo'],
                'san pedro': ['san pedro'],
                'santa rosa': ['santa rosa'],
                'santa maria': ['santa maria'],
                'santa ana': ['santa ana'],
                'santa cruz': ['santa cruz'],
                'santa isabel': ['santa isabel'],
                'santa lucia': ['santa lucia'],
                'santa monica': ['santa monica'],
                'santa rita': ['santa rita'],
                'santa teresa': ['santa teresa'],
                'santa ursula': ['santa ursula'],
                'santa veronica': ['santa veronica']
            };
            
            // Check for exact matches first
            for (let i = 0; i < options.length; i++) {
                const optionText = options[i].text.toLowerCase();
                if (optionText.includes(searchName) || searchName.includes(optionText)) {
                    barangaySelect.selectedIndex = i;
                    console.log('Found exact match:', optionText, 'for search:', searchName);
                    return true;
                }
            }
            
            // Check for partial matches (more flexible)
            for (let i = 0; i < options.length; i++) {
                const optionText = options[i].text.toLowerCase();
                const searchWords = searchName.split(' ');
                const optionWords = optionText.split(' ');
                
                // Check if any word from search matches any word from option
                for (const searchWord of searchWords) {
                    for (const optionWord of optionWords) {
                        if (searchWord.length > 2 && optionWord.length > 2 && 
                            (searchWord.includes(optionWord) || optionWord.includes(searchWord))) {
                            barangaySelect.selectedIndex = i;
                            console.log('Found partial match:', optionText, 'for search:', searchName);
                            return true;
                        }
                    }
                }
            }
            
            // Check for mapped barangay names
            for (const [key, values] of Object.entries(barangayMappings)) {
                if (searchName.includes(key)) {
                    for (const value of values) {
                        for (let i = 0; i < options.length; i++) {
                            const optionText = options[i].text.toLowerCase();
                            if (optionText.includes(value)) {
                                barangaySelect.selectedIndex = i;
                                return true;
                            }
                        }
                    }
                }
            }
            
            return false; // No match found
        }
        
        // Step progress function
        function updateStepProgress(step) {
            const steps = document.querySelectorAll('.step');
            const lineFill = document.getElementById('stepLineFill');
            
            steps.forEach((stepEl, index) => {
                stepEl.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    stepEl.classList.add('completed');
                } else if (index + 1 === step) {
                    stepEl.classList.add('active');
                }
            });
            
            const progress = ((step - 1) / (steps.length - 1)) * 100;
            lineFill.style.width = progress + '%';
        }

        // Form validation
        document.getElementById('addressForm').addEventListener('input', function() {
            validateForm();
        });

        function validateForm() {
            const label = document.getElementById('locationLabel').value;
            const barangay = document.getElementById('barangay_id').value;
            const address = document.getElementById('address').value;
            const hasLocation = selectedLat && selectedLng;
            
            const isValid = label && barangay && address && hasLocation;
            document.getElementById('saveLocationBtn').disabled = !isValid;
            
            if (isValid) {
                updateStepProgress(3);
            }
        }

        // Save location
        document.getElementById("saveLocationBtn").addEventListener("click", function () {
            const locationLabel = document.getElementById("locationLabel").value;
            const address = document.getElementById("address").value;
            const barangay_id = document.getElementById("barangay_id").value;
            
            if (selectedLat && selectedLng) {
                // Show loading state
                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                btn.disabled = true;
                
                fetch("process_createlocation.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `lat=${selectedLat}&lng=${selectedLng}&label=${encodeURIComponent(locationLabel)}&address=${encodeURIComponent(address)}&barangay_id=${barangay_id}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '📍 Address Saved Successfully!',
                            text: result.message || 'Your delivery address has been saved and is ready to use.',
                            confirmButtonText: 'View My Addresses',
                            confirmButtonColor: '#28a745'
                        }).then((res) => {
                            if (res.isConfirmed) {
                                window.location.href = "addresses.php";
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Saving Address',
                            text: result.message || 'Failed to save address. Please try again.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Error saving address: ' + error
                    });
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        });

        // Edit location
        document.getElementById("editLocationBtn").addEventListener("click", function () {
            isLocationConfirmed = false;
            selectedLat = null;
            selectedLng = null;

            if (userMarker) {
                map.removeLayer(userMarker);
                userMarker = null;
            }

            document.getElementById("saveLocationBtn").disabled = true;
            updateStepProgress(1);
            
            Swal.fire({
                icon: 'info',
                title: 'Edit Location Mode',
                text: 'You can now search for a new address or click on the map to select a new location.'
            });
        });

        // Initialize step progress
        updateStepProgress(1);
        </script>
    </body>
</html>