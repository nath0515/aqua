<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname,longitude,latitude, address, contact_number FROM users u
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

    $sql = "SELECT b.product_name, a.with_container, b.water_price, b.water_price_promo, a.quantity FROM cart a 
    JOIN products b ON a.product_id = b.product_id 
    WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <li class="nav-item me-2">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                </li>
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
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                        <li><a class="dropdown-item" href="mapuser.php">Pinned Location</a></li>
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
                    <div class="container py-5">
                        <div class="card shadow-lg">
                            <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">🛒 Your Shopping Cart</h5>
                            </div>

                            <div class="card-body">
                            <!-- Product Card -->
                            <?php foreach($cart_data as $row):?>
                            <div class="card mb-3 shadow-sm product-item">
                                <div class="card-body d-flex align-items-center">
                                    <input class="form-check-input me-3 product-checkbox" type="checkbox">

                                    <img src="https://via.placeholder.com/80" class="me-3" alt="Product" style="width: 80px; height: auto;">

                                    <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo $row['product_name'];?></h6>
                                    <p class="mb-1 text-muted small">With Container: <?php if($row['with_container'] == 0){echo 'None';}else{echo 'Yes';}?></p>
                                    </div>
                                    <?php
                                        $quantity = $row['quantity'];
                                        $price = $quantity >= 10 ? $quantity * $row['water_price_promo'] : $quantity * $row['water_price'];
                                    ?>
                                    <div class="text-end me-3">
                                    <p class="mb-2 fw-bold price" data-price="<?php echo $price;?>">₱<?php echo $price;?></p>
                                    <div class="input-group input-group-sm w-auto">
                                        <button class="btn btn-outline-secondary">-</button>
                                        <input type="text" class="form-control text-center" value="1" style="width: 40px;">
                                        <button class="btn btn-outline-secondary">+</button>
                                    </div>
                                    </div>

                                    <div>
                                    <button class="btn btn-link text-danger btn-sm">Delete</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="card-footer d-flex justify-content-between align-items-center bg-white border-top">
                                <div>
                                    <input class="form-check-input me-2" type="checkbox" id="select-all">
                                    <label for="select-all" class="mb-0">Select All</label>
                                </div>
                                <div class="text-end">
                                    <p class="mb-1">Total (<span id="selected-count">0</span> item): <strong>₱<span id="total-price">0</span></strong></p>
                                    <button class="btn btn-warning">Check Out</button>
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

        <script>
        const selectAllCheckbox = document.getElementById('select-all');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const totalPriceEl = document.getElementById('total-price');
        const selectedCountEl = document.getElementById('selected-count');

        function updateTotal() {
            let total = 0;
            let count = 0;

            productCheckboxes.forEach((cb, index) => {
            if (cb.checked) {
                count++;
                const productCard = cb.closest('.product-item');
                const price = parseFloat(productCard.querySelector('.price').dataset.price);
                total += price;
            }
            });

            selectedCountEl.textContent = count;
            totalPriceEl.textContent = total.toLocaleString();
        }

        // Toggle all checkboxes when "Select All" is clicked
        selectAllCheckbox.addEventListener('change', function () {
            productCheckboxes.forEach(cb => cb.checked = this.checked);
            updateTotal();
        });

        // Update "Select All" state and total when any checkbox changes
        productCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
            const allChecked = Array.from(productCheckboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
            updateTotal();
            });
        });

        </script>

        
    </body>
</html>
