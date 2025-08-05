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
    if ($item['with_container']) {
        $cart_count += $item['container_quantity'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Home</title>
    
    <!-- Styles -->
    <link href="css/styles.css" rel="stylesheet" />
    <link rel="manifest" href="/manifest.json"> <!-- ✅ Correct path to manifest -->
    <style>
        #loadingOverlay {
            display: none;
            position: fixed;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.8);
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
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <!-- Icons -->
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">

<!-- Top Navbar -->
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">

    <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>     
    
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
            <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="userprofile.php">Profile</a></li>
                <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
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
        <!-- Header image -->
        <div class="text-center mt-3">
            <img src="assets/img/homepage.png" alt="Header Image" class="img-fluid" style="max-width: 100%; height: auto;">
        </div>

        <div class="text-center mt-3">
            <img src="assets/img/homedlapp.png" alt="Header Image" class="img-fluid" style="max-width: 80%; height: auto; border-radius: 10px;">
        </div>

        <!-- Main content -->
        <main class="text-center my-4">
            <button id="installBtn" class="btn btn-primary" style="display: none;">Install AquaDrop</button>
            <div id="loadingOverlay">
                <div class="spinner"></div>
            </div>
        </main>
        <section style="font-family: Arial, sans-serif; padding: 40px; background-color: #f8f8f8;">
            <div style="max-width: 900px; margin: auto; text-align: center;">
                <h2 style="color: #0077b6; font-size: 2.5em; margin-bottom: 10px;">Why Choose AquaDrop?</h2>
                <p style="font-size: 1.1em; color: #555;">
                Order and track deliveries easily through the AquaDrop app now serving your barangay! We're committed to fast, reliable service right where you live.
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; justify-content: center; margin-top: 40px; gap: 30px;">

                <div style="flex: 1 1 250px; background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <h3 style="color: #0077b6;">🌟 Hassle-Free Ordering</h3>
                <p>Use our web and mobile-based system to place orders and track them with ease all in one place.</p>
                </div>

                <div style="flex: 1 1 250px; background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <h3 style="color: #0077b6;">💧 Quality and Reliability</h3>
                <p>Enjoy peace of mind with clean, high-quality water delivered reliably by AquaDrop with care and consistency you can trust.</p>
                </div>

                <div style="flex: 1 1 250px; background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <h3 style="color: #0077b6;">🔬 Verified Products</h3>
                <p>All our water is carefully processed and handled with strict quality and safety standards so you always get clean, safe, and trustworthy service.</p>
                </div>

                <div style="flex: 1 1 250px; background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <h3 style="color: #0077b6;">💳 Easy Payments</h3>
                <p>Simplify your payments with GCash for fast and secure transactions, or choose the ease of Cash on Delivery (COD) for added convenience.</p>
                </div>

            </div>

            <div style="text-align: center; margin-top: 40px;">
                <a href="costumerorder.php" style="background-color: #ff7f50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-size: 1.1em;">Order Now</a>
            </div>
        </section>

        
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


</body>
</html>
