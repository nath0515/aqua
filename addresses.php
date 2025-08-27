<?php 
    require 'session.php';
    require 'db.php';
    require 'notification_helper.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
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

    // Get notifications
    $notifications = getNotifications($conn, $user_id, $role_id);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>My Delivery Addresses - AquaDrop</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <style>
            .address-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 15px;
                color: white;
                padding: 2rem;
                margin-bottom: 2rem;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            
            .address-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
                pointer-events: none;
            }
            
            .address-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            }
            
            .address-card .card-content {
                position: relative;
                z-index: 2;
            }
            
            .address-card .location-icon {
                font-size: 2.5rem;
                margin-bottom: 1rem;
                opacity: 0.9;
            }
            
            .address-card .location-label {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            
            .address-card .address-text {
                font-size: 1rem;
                line-height: 1.6;
                margin-bottom: 1.5rem;
                opacity: 0.95;
            }
            
            .address-card .coordinates {
                background: rgba(255,255,255,0.2);
                border-radius: 8px;
                padding: 0.5rem;
                font-family: monospace;
                font-size: 0.85rem;
                margin-bottom: 1rem;
            }
            
            .address-card .action-buttons {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
            }
            
            .btn-address {
                border-radius: 25px;
                padding: 0.5rem 1rem;
                font-weight: 600;
                border: none;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .btn-address:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .btn-view {
                background: rgba(255,255,255,0.9);
                color: #667eea;
            }
            
            .btn-view:hover {
                background: white;
                color: #667eea;
            }
            
            .btn-delete {
                background: rgba(220, 53, 69, 0.9);
                color: white;
            }
            
            .btn-delete:hover {
                background: #dc3545;
                color: white;
            }
            
            .empty-state {
                text-align: center;
                padding: 4rem 2rem;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 15px;
                border: 2px dashed #dee2e6;
            }
            
            .empty-state .icon {
                font-size: 4rem;
                color: #6c757d;
                margin-bottom: 1rem;
            }
            
            .empty-state h3 {
                color: #495057;
                margin-bottom: 1rem;
            }
            
            .empty-state p {
                color: #6c757d;
                margin-bottom: 2rem;
            }
            
            .btn-add-address {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                border: none;
                border-radius: 25px;
                padding: 1rem 2rem;
                font-weight: 600;
                color: white;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            }
            
            .btn-add-address:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
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
            
            .stats-card {
                background: white;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                text-align: center;
                transition: all 0.3s ease;
            }
            
            .stats-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
            }
            
            .stats-card .number {
                font-size: 2rem;
                font-weight: 700;
                color: #667eea;
                margin-bottom: 0.5rem;
            }
            
            .stats-card .label {
                color: #6c757d;
                font-weight: 600;
            }
            
            .fade-in {
                animation: fadeIn 0.5s ease-in;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                display: none;
            }
            
            .spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
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
                        <li><a class="dropdown-item text-center text-muted small" href="activitylogsuser.php">View all notifications</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="userprofile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogsuser.php">Activity Log</a></li>
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
                                        My Delivery Addresses
                                    </h1>
                                    <p class="mb-0 opacity-75">
                                        Manage your saved delivery locations for quick and easy checkout
                                    </p>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <a href="add_location.php" class="btn-add-address">
                                        <i class="fas fa-plus"></i>
                                        Add New Address
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <?php
                        $loc_stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_locations WHERE user_id = :user_id");
                        $loc_stmt->execute([':user_id' => $user_id]);
                        $total_addresses = $loc_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                        ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="number"><?php echo $total_addresses; ?></div>
                                    <div class="label">Total Addresses</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="number">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="label">Ready for Delivery</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="number">
                                        <i class="fas fa-truck text-primary"></i>
                                    </div>
                                    <div class="label">Fast Delivery</div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Cards -->
                        <div class="row" id="addressesContainer">
                            <?php
                            $loc_stmt = $conn->prepare("SELECT location_id, label, latitude, longitude, address FROM user_locations WHERE user_id = :user_id ORDER BY location_id DESC");
                            $loc_stmt->execute([':user_id' => $user_id]);
                            $user_locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!empty($user_locations)): ?>
                                <?php foreach ($user_locations as $index => $loc): ?>
                                    <div class="col-lg-6 col-xl-4 mb-4 fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                        <div class="address-card" id="location-card-<?php echo $loc['location_id']; ?>">
                                            <div class="card-content">
                                                <div class="location-icon">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                                <div class="location-label">
                                                    <?php echo htmlspecialchars($loc['label']); ?>
                                                </div>
                                                <div class="address-text">
                                                    <?php echo htmlspecialchars($loc['address']); ?>
                                                </div>
                                                <div class="coordinates">
                                                    <small>
                                                        <i class="fas fa-crosshairs me-1"></i>
                                                        <?php echo number_format($loc['latitude'], 6); ?>, <?php echo number_format($loc['longitude'], 6); ?>
                                                    </small>
                                                </div>
                                                <div class="action-buttons">
                                                    <a href="mapuser.php?location_id=<?php echo $loc['location_id']; ?>" 
                                                       class="btn-address btn-view">
                                                        <i class="fas fa-eye"></i>
                                                        View on Map
                                                    </a>
                                                    <button type="button" 
                                                            class="btn-address btn-delete" 
                                                            onclick="confirmDelete(<?php echo $loc['location_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <div class="icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <h3>No Delivery Addresses Yet</h3>
                                        <p>You haven't added any delivery addresses yet. Add your first address to get started with faster checkout!</p>
                                        <a href="add_location.php" class="btn-add-address">
                                            <i class="fas fa-plus"></i>
                                            Add Your First Address
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
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
        
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
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
            function showLoading() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            }
            
            function hideLoading() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
            
            function confirmDelete(locationId) {
                Swal.fire({
                    title: 'Delete Address?',
                    text: "This address will be permanently removed from your account.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoading();
                        
                        $.ajax({
                            url: 'delete_location.php',
                            type: 'POST',
                            data: { id: locationId },
                            success: function (response) {
                                hideLoading();
                                
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'The address has been successfully removed.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                // Remove the card with animation
                                const card = document.getElementById('location-card-' + locationId);
                                if (card) {
                                    card.style.transition = 'all 0.5s ease';
                                    card.style.transform = 'scale(0.8)';
                                    card.style.opacity = '0';
                                    
                                    setTimeout(() => {
                                        card.closest('.col-lg-6').remove();
                                        
                                        // Check if no more addresses
                                        const remainingCards = document.querySelectorAll('.address-card');
                                        if (remainingCards.length === 0) {
                                            location.reload(); // Reload to show empty state
                                        }
                                    }, 500);
                                }
                            },
                            error: function () {
                                hideLoading();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Something went wrong. Please try again.',
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                        });
                    }
                });
            }
            
            // Add some interactive effects
            document.addEventListener('DOMContentLoaded', function() {
                // Add hover effects to stats cards
                const statsCards = document.querySelectorAll('.stats-card');
                statsCards.forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-5px) scale(1.02)';
                    });
                    
                    card.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0) scale(1)';
                    });
                });
                
                // Add click effect to address cards
                const addressCards = document.querySelectorAll('.address-card');
                addressCards.forEach(card => {
                    card.addEventListener('click', function(e) {
                        // Don't trigger if clicking on buttons
                        if (e.target.closest('.btn-address')) {
                            return;
                        }
                        
                        // Add a subtle click effect
                        this.style.transform = 'translateY(-2px) scale(0.98)';
                        setTimeout(() => {
                            this.style.transform = 'translateY(-5px)';
                        }, 150);
                    });
                });
            });
        </script>
            
    </body>
</html>
