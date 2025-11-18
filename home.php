<?php 
require ('db.php');
require ('session.php');

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

// Calculate total items in cart
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

// Get notifications using helper
require 'notification_helper.php';
$notifications = getNotifications($conn, $user_id, $role_id);
$unread_count = $notifications['unread_count'];

// Check for notification success message
$notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>AquaDrop - Premium Water Delivery Service</title>
    
    <!-- Styles -->
    <link href="css/styles.css" rel="stylesheet" />
    <link rel="manifest" href="/manifest.json">
    <style>
        /* Professional Business Styling */
        .hero-section {
            background: linear-gradient(135deg, #0077b6 0%, #005a8b 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .hero-cta {
            background: #ff6b35;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .hero-cta:hover {
            background: #e55a2b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }
        
        .features-section {
            padding: 80px 0;
            margin-top: 80px;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 60px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0077b6, #005a8b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 2rem;
        }
        
        .feature-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .feature-description {
            color: #6c757d;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .cta-section {
            background: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .cta-button {
            background: linear-gradient(135deg, #0077b6, #005a8b);
            color: white;
            padding: 18px 50px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 10px 25px rgba(0, 119, 182, 0.3);
        }
        
        .cta-button:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 119, 182, 0.4);
        }
        
        .app-banner {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 40px 0;
            text-align: center;
        }
        
        .banner-image {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            margin: 0 auto;
        }
        
        #loadingOverlay {
            display: none;
            position: fixed;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.9);
            top: 0; left: 0;
            width: 100%; height: 100%;
            justify-content: center;
            align-items: center;
        }
        
        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #0077b6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 0.8s linear infinite;
        }
        .notification-text{
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
                max-width: 200px;
                
            }
            .notification-text.fw-bold {
                font-weight: 600;
                color: #000;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            

            
            .feature-card {
                padding: 30px 20px;
            }
        }
           .custom-navbar {
            background: linear-gradient(135deg, #0077b6, #005a8b) !important;
        }
    </style>

    <!-- Icons -->
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">

<!-- Top Navbar -->
<nav class="sb-topnav navbar navbar-expand navbar-dark custom-navbar">
    <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 170px; height: 60px;">
            </a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>     
    
    <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
        <li class="nav-item me-2">
            <a class="nav-link position-relative mt-2" href="cart.php">
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
                            <a class="dropdown-item text-center text-muted small" href="activitylogsuser.php">View all notifications</a>
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
                                        // Step 1: Confirm Application
                                        Swal.fire({
                                            title: 'Apply as Reseller',
                                            text: 'Are you sure you want to submit a reseller application? Our team will review it promptly.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonText: 'Yes, apply now',
                                            cancelButtonText: 'Cancel'
                                        }).then((result) => {
                                            if (result.isConfirmed) {

                                                // Step 2: Show ID Upload Guidelines
                                                Swal.fire({
                                                    title: 'ID Upload Guidelines',
                                                    html: `
                                                        <p style="text-align:left;">
                                                            ✔ Upload a clear, well-lit photo showing the entire ID<br>
                                                            ✔ Ensure all four corners & edges are visible<br>
                                                            ✔ Text must be sharp and readable<br>
                                                            ✔ Avoid glare, shadows, or reflections<br><br>
                                                            <b>Accepted IDs:</b><br>
                                                            • Passport<br>
                                                            • Driver’s License<br>
                                                            • National ID<br>
                                                            • SSS ID<br>
                                                            • GSIS ID<br>
                                                            • Voter’s ID<br>
                                                            • Postal ID<br>
                                                            • Senior Citizen ID<br>
                                                            • UMID<br>
                                                            • PhilHealth ID<br>
                                                            • PRC ID<br>
                                                            • Other Government ID
                                                        </p>
                                                    `,
                                                    confirmButtonText: 'Proceed to Upload',
                                                    showCancelButton: true,
                                                }).then((guideResult) => {
                                                    if (guideResult.isConfirmed) {

                                                        // Step 3: Upload Valid ID
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

<!-- Sidebar + Content -->
<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Menu</div>
                    <a class="nav-link active" href="home.php">
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
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <h1 class="hero-title">Premium Water Delivery</h1>
                <p class="hero-subtitle">Clean, safe, and reliable water delivered right to your doorstep. Experience the convenience of modern water delivery service.</p>
                <a href="costumerorder.php" class="hero-cta">Start Ordering Now</a>
            </div>
        </section>

        <!-- App Banner -->
        <section class="app-banner">
            <div class="container">
                <img src="assets/img/homedlapp.png" alt="AquaDrop Mobile App" class="banner-image">
                <div class="text-center mt-4">
                    <button id="installBtn" class="btn btn-primary btn-lg" style="display: none;">
                        <i class="fas fa-download me-2"></i>Install AquaDrop
                    </button>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title">Why Choose AquaDrop?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Hassle-Free Ordering</h3>
                        <p class="feature-description">Use our web and mobile-based system to place orders and track them with ease all in one place.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <h3 class="feature-title">Quality & Reliability</h3>
                        <p class="feature-description">Enjoy peace of mind with clean, high-quality water delivered reliably with care and consistency you can trust.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Verified Products</h3>
                        <p class="feature-description">All our water is carefully processed and handled with strict quality and safety standards for your peace of mind.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3 class="feature-title">Easy Payments</h3>
                        <p class="feature-description">Simplify your payments with GCash for fast and secure transactions, or choose Cash on Delivery for convenience.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Section -->
        <section class="cta-section">
            <div class="container">
                <h2 class="section-title">Ready to Get Started?</h2>
                <p class="text-muted mb-4">Join thousands of satisfied customers who trust AquaDrop for their water delivery needs.</p>
                <a href="costumerorder.php" class="cta-button">Order Now</a>
            </div>
        </section>

        <div id="loadingOverlay">
            <div class="spinner"></div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/scripts.js"></script>

<!-- PWA: Install Button Logic -->
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
