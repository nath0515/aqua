<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Stock</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#!">Settings</a></li>
                        <li><a class="dropdown-item" href="#!">Activity Log</a></li>
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
                                    <a class="nav-link" href="Orderhistory.php">Order History</a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Order</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                            <li class="breadcrumb-item active">Order Management</li>
                            <li class="breadcrumb-item active">Order</li>
                        </ol>
                        <div class="row">
                            <!-- Card -->
                            <?php foreach($products_data as $row):?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card bg-primary text-white position-relative h-100">
                                        <!-- Invisible link covering the whole card -->
                                        <a href="costumer_createpurchase.php?id=<?php echo $row['product_id']; ?>" class="stretched-link" style="z-index: 1;"></a>

                                        <div class="card-header text-center" style="font-size: 20px;">
                                            <?php echo $row['product_name']; ?>
                                        </div>

                                        <div class="card-body bg-white text-center d-flex justify-content-center align-items-center" style="font-size: 25px;">
                                            <img src="<?php echo $row['product_photo']; ?>" width="100px" height="100px" class="rounded">
                                        </div>

                                        <div class="card-footer d-flex align-items-center justify-content-between">
                                            <div>
                                                Water Price: ₱<?php echo $row['water_price']; ?><br>
                                                Container Price: ₱<?php echo $row['container_price']; ?><br>
                                                Stock: <?php echo $row['stock']; ?>
                                            </div>
                                            <div class="small text-white">
                                                <i class="bi bi-cart-plus"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;?>
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

        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Your action was completed successfully.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Unexpected error.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php elseif(isset($_GET['status']) && $_GET['status'] == 'filetype'): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Invalid file type. Please upload a valid file.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php elseif(isset($_GET['status']) && $_GET['status'] == 'exist'): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Product name already exists.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php elseif(isset($_GET['stock']) && $_GET['stock'] == 'success'): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Stock added successfully.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php elseif(isset($_GET['edit']) && $_GET['edit'] == 'success'): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Item edited successfully.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php endif; ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                $(".editProductBtn").click(function() {
                    var productId = $(this).data("id");

                    $.ajax({
                        url: "process_getproductdata.php",
                        type: "POST",
                        data: { product_id: productId },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $("#editProductId").val(productId);
                                $("#editProductName").val(response.data.product_name);
                                $("#editWaterPrice").val(response.data.water_price);
                                $("#editContainerPrice").val(response.data.container_price);
                                $("#editStock").val(response.data.stock);
                                $("#editProductImagePreview").attr("src", response.data.product_photo);
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
            
    </body>
</html>
