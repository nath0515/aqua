<?php 
    require 'session.php';
    require 'db.php';
    error_reporting(E_ALL);
ini_set('display_errors', 1);

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");
    }else if ($role_id == 3){
        header("Location: riderdashboard.php");
    }

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname,longitude,latitude, address, contact_number FROM users u
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

    $needsPinning = (
        empty($user_data['latitude']) || empty($user_data['longitude']) ||
        $user_data['latitude'] == 0 || $user_data['longitude'] == 0
    );

    $sql = "SELECT * FROM products ORDER BY container_price ASC, product_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT status FROM store_status WHERE ss_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $store_status = $stmt->fetchColumn();

    $storeClosed = false;
    if ($store_status == 0) {
        $storeClosed = true; // set flag, don't echo script here
    }

    // Calculate total items in cart (only product quantity, not containers)
    $sql = "SELECT a.cart_id, a.product_id, b.product_name, a.with_container, a.quantity, a.container_quantity 
        FROM cart a 
        JOIN products b ON a.product_id = b.product_id 
        WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cart_count = 0;
    foreach ($cart_data as $item) {
        $cart_count += $item['quantity'];
    }

    $sql = "SELECT rs FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $rs = $stmt->fetchColumn();

    $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Products - AquaDrop</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            /* Professional Product Page Styling */
            .products-header {
                background: linear-gradient(135deg, #0077b6 0%, #005a8b 100%);
                color: white;
                padding: 40px 0;
                margin-bottom: 40px;
            }
            
            .products-title {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 10px;
            }
            
            .products-subtitle {
                font-size: 1.1rem;
                opacity: 0.9;
            }
            
            .product-card {
                background: white;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                border: 1px solid #e9ecef;
                overflow: hidden;
                height: 100%;
            }
            
            .product-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            }
            
            .product-header {
                background: linear-gradient(135deg, #0077b6, #005a8b);
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .product-image {
                padding: 30px;
                text-align: center;
                background: #f8f9fa;
                min-height: 200px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .product-details {
                padding: 25px;
            }
            
            .price-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #f1f3f4;
            }
            
            .price-item:last-child {
                border-bottom: none;
            }
            
            .price-label {
                font-weight: 500;
                color: #6c757d;
            }
            
            .price-value {
                font-weight: 600;
                color: #0077b6;
            }
            
            .stock-info {
                background: #e8f5e8;
                color: #2d5a2d;
                padding: 10px;
                border-radius: 8px;
                text-align: center;
                margin-top: 15px;
                font-weight: 500;
            }
            
            .add-to-cart-btn {
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 25px;
                font-weight: 600;
                transition: all 0.3s ease;
                width: 100%;
                margin-top: 15px;
            }
            
            .add-to-cart-btn:hover {
                background: linear-gradient(135deg, #218838, #1ea085);
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
            }
            
            .products-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
                margin-top: 30px;
            }
            
            @media (max-width: 768px) {
                .products-title {
                    font-size: 2rem;
                }
                
                .products-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
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
                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cart_count; ?>
                            <span class="visually-hidden">items in cart</span>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationDropdown">

                        <!-- Header with "Mark All as Read" -->
                        <li class="dropdown-header d-flex justify-content-between align-items-center fw-bold text-dark">
                            Notifications
                            <?php 
                                if ($unread_count > 0) {
                                    echo '<a href="process_readnotification.php?action=mark_all_read&user_id=' . $user_id . '&role_id=' . $role_id . '&redirect=' . urlencode($current_page) . '" class="text-primary small fw-bold"><i class="fas fa-check-double"></i> Mark All</a>';
                                }
                            ?>
                        </li>
                        <?php echo renderNotificationDropdown($notifications['recent_notifications'], $unread_count, $user_id, $role_id); ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center text-muted small" href="activitylogs.php">View all notifications</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="userprofile.php">Profile</a></li>

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
                        <li><a class="dropdown-item" href="activitylogsuser.php">Activity Log</a></li>
                        <li><a class="dropdown-item" href="addresses.php">Addresses</a></li>
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
                            <a class="nav-link collapsed <?php echo ($current_page == 'costumerorder.php' || $current_page == 'orderhistory.php') ? '' : 'collapsed'; ?>" 
                            href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="<?php echo ($current_page == 'costumerorder.php' || $current_page == 'orderhistory.php') ? 'true' : 'false'; ?>" 
                            aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Order Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>

                            <div class="collapse <?php echo ($current_page == 'costumerorder.php' || $current_page == 'orderhistory.php') ? 'show' : ''; ?>" 
                                id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link <?php echo $current_page == 'costumerorder.php' ? 'active' : ''; ?>" href="costumerorder.php">Order</a>
                                    <a class="nav-link <?php echo $current_page == 'orderhistory.php' ? 'active' : ''; ?>" href="orderhistory.php">Order History</a>
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
                <!-- Products Header -->
                <section class="products-header">
                    <div class="container-fluid px-4">
                        <h1 class="products-title">Our Products</h1>
                        <p class="products-subtitle">Choose from our premium selection of clean, safe drinking water</p>
                    </div>
                </section>

                <!-- Products Grid -->
                <main class="container-fluid px-4">
                    <div class="products-grid">
                        <?php foreach($products_data as $row):?>
                            <div class="product-card">
                                <div class="product-header">
                                    <?php echo $row['product_name']; ?>
                                </div>
                                
                                <div class="product-image">
                                    <?php if (!empty($row['product_photo']) && file_exists($row['product_photo'])): ?>
                                        <img src="<?php echo $row['product_photo']; ?>" width="120px" height="120px" class="rounded">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background-color: #f8f9fa; border-radius: 12px;">
                                            <i class="fas fa-water text-primary" style="font-size: 50px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-details">
                                    <div class="price-item">
                                        <span class="price-label">Water Price:</span>
                                        <?php if ($rs == 1): ?>
                                            <span class="price-value" style="text-decoration: line-through; color: #888;">
                                                ₱<?php echo number_format($row['water_price'], 2); ?>
                                            </span>
                                            <span class="price-value" style="color: #198754; font-weight: bold; margin-left: 10px;">
                                                ₱<?php echo number_format($row['water_price_promo'], 2); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="price-value">
                                                ₱<?php echo number_format($row['water_price'], 2); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($row['container_price'] > 0): ?>
                                    <div class="price-item">
                                        <span class="price-label">Container Price:</span>
                                        <span class="price-value">₱<?php echo $row['container_price']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="stock-info">
                                        <i class="fas fa-boxes me-2"></i>
                                        Stock: <?php echo $row['stock']; ?> available
                                    </div>
                                    
                                    <a href="costumer_createpurchase.php?id=<?php echo $row['product_id']; ?>" class="btn add-to-cart-btn">
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        Add to Cart
                                    </a>
                                </div>
                            </div>
                        <?php endforeach;?>
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
