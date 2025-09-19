<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    if($role_id == 2){
        header("Location: home.php");
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

    
    // Get notifications using helper for consistency
    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;
    $sql = "SELECT * FROM products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE read_status = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = $unread_result['unread_count'];
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

        <style>
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
            
            /* Professional Stock Page Styling */
            .stock-header {
                background: linear-gradient(135deg, #0077b6 0%, #005a8b 100%);
                color: white;
                padding: 40px 0;
                border-radius: 15px;
                margin-bottom: 30px;
                box-shadow: 0 4px 20px rgba(0, 119, 182, 0.15);
            }
            
            .stock-title {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 10px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            
            .stock-subtitle {
                font-size: 1.1rem;
                opacity: 0.9;
                margin-bottom: 0;
            }
            
            .product-card {
                background: white;
                border-radius: 15px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: none;
                overflow: hidden;
                height: 100%;
            }
            
            .product-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }
            
            .product-header {
                background: linear-gradient(135deg, #0077b6 0%, #005a8b 100%);
                color: white;
                padding: 20px;
                text-align: center;
                position: relative;
            }
            
            .product-name {
                font-size: 1.1rem;
                font-weight: 600;
                margin: 0;
                line-height: 1.3;
            }
            
            .product-image-container {
                padding: 30px 20px;
                background: #f8f9fa;
                text-align: center;
                min-height: 150px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .product-placeholder {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .product-details {
                padding: 20px;
                background: white;
            }
            
            .price-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
                padding: 8px 0;
                border-bottom: 1px solid #f1f3f4;
            }
            
            .price-row:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            
            .price-label {
                font-weight: 500;
                color: #495057;
                font-size: 0.9rem;
            }
            
            .price-value {
                font-weight: 600;
                color: #0077b6;
                font-size: 0.95rem;
            }
            
            .stock-badge {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                padding: 6px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 0.9rem;
                display: inline-block;
                margin-top: 10px;
            }
            
            .edit-button {
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 35px;
                height: 35px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
            }
            
            .edit-button:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: scale(1.1);
            }
            
            .action-buttons {
                display: flex;
                gap: 10px;
                margin-bottom: 30px;
            }
            
            .btn-modern {
                background: linear-gradient(135deg, #0077b6 0%, #005a8b 100%);
                border: none;
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 119, 182, 0.3);
            }
            
            .btn-modern:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 119, 182, 0.4);
                color: white;
            }
            
            .btn-success-modern {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                border: none;
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            }
            
            .btn-success-modern:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
                color: white;
            }
            
            .stats-card {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 30px;
                border: none;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }
            
            .stats-number {
                font-size: 2rem;
                font-weight: 700;
                color: #0077b6;
                margin-bottom: 5px;
            }
            
            .stats-label {
                color: #6c757d;
                font-weight: 500;
                font-size: 0.9rem;
            }
            .low-stock {
                border: 2px solid #ff4d4d;
                background-color: #ffe6e6;
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
                <?php 
                    $sql = "SELECT * FROM activity_logs ORDER BY date DESC LIMIT 3";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $activity_logs = $stmt->fetchAll();
                ?>
                
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown" style="min-width: 250px;">
                        <li class="dropdown-header fw-bold text-dark">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach($activity_logs as $row):?>
                        <li><a class="dropdown-item notification-text" href="process_readnotification.php?id=<?php echo $row['activitylogs_id']?>&destination=<?php echo $row['destination']?>"><?php echo $row['message'];?></a></li>
                        <hr>
                        <?php endforeach; ?>
                        <li><a class="dropdown-item text-center text-muted small" href="activitylogs.php">View all notificationsa</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <li><a id="installBtn" class="dropdown-item" style="display: none;">Install AquaDrop</a></li>
                        <?php 
                        $sql = "SELECT status FROM store_status WHERE ss_id = 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        $status = $stmt->fetchColumn();
                        ?>
                        <li>
                            <a 
                                href="process_dailyreport.php" 
                                class="dropdown-item"
                                <?php if ($status == 1): ?>
                                    onclick="return confirmCloseShop(event)"
                                <?php endif; ?>
                            >
                                <?php echo ($status == 1) ? 'Close Shop' : 'Open Shop'; ?>
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
                            <a class="nav-link" href="index.php">
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
                                    <a class="nav-link" href="orders.php">Orders</a>
                                    <a class="nav-link" href="stock.php">Stock</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Analytics
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="sales.php">Sales</a>
                                    <a class="nav-link" href="expenses.php">Expenses</a>
                                    <a class="nav-link" href="income.php">Income</a>
                                    <a class="nav-link" href="report.php">Report</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts2" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Account Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts2" aria-labelledby="headingThree" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="accounts.php">Accounts</a>
                                    <a class="nav-link" href="rideraccount.php">Add Rider</a>
                                    <a class="nav-link" href="adminaccount.php">Add Admin</a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <!-- Professional Header -->
                        <div class="stock-header text-center">
                            <h1 class="stock-title">Product Inventory</h1>
                            <p class="stock-subtitle">Manage your water products and stock levels</p>
                        </div>
                        
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number"><?php echo count($products_data); ?></div>
                                    <div class="stats-label">Total Products</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number"><?php echo array_sum(array_column($products_data, 'stock')); ?></div>
                                    <div class="stats-label">Total Stock</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number">₱<?php echo number_format(array_sum(array_column($products_data, 'water_price'))); ?></div>
                                    <div class="stats-label">Total Value</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number"><?php echo count(array_filter($products_data, function($p) { return $p['stock'] < 10; })); ?></div>
                                    <div class="stats-label">Low Stock Items</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="btn btn-modern" data-bs-toggle="modal" data-bs-target="#additem">
                                <i class="fa fa-plus me-2"></i>
                                Add Product
                            </button>
                            <button class="btn btn-success-modern" data-bs-toggle="modal" data-bs-target="#addstock">
                                <i class="fa fa-plus me-2"></i>
                                Add Stock
                            </button>
                        </div>

                        <!-- Product Grid -->
                        <div class="row">
                            <?php foreach ($products_data as $row): ?>
                                <?php 
                                    $lowStock = $row['stock'] < 10 ? 'low-stock' : ''; 
                                ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div 
                                        class="product-card editProductCard <?php echo $lowStock; ?>" 
                                        style="cursor: pointer;" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#edititem"
                                        data-id="<?php echo $row['product_id']; ?>"
                                    >
                                        <div class="product-header">
                                            <h5 class="product-name"><?php echo $row['product_name']; ?></h5>
                                            <button class="edit-button">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="product-image-container">
                                            <?php if (!empty($row['product_photo']) && file_exists($row['product_photo'])): ?>
                                                <img src="<?php echo $row['product_photo']; ?>" class="product-image" alt="<?php echo $row['product_name']; ?>">
                                            <?php else: ?>
                                                <div class="product-placeholder">
                                                    <i class="fas fa-water" style="font-size: 30px; color: #0077b6;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="product-details">
                                            <div class="price-row">
                                                <span class="price-label">Water Price:</span>
                                                <span class="price-value">₱<?php echo number_format($row['water_price'], 2); ?></span>
                                            </div>
                                            <div class="price-row">
                                                <span class="price-label">Container Price:</span>
                                                <span class="price-value">₱<?php echo number_format($row['container_price'], 2); ?></span>
                                            </div>
                                            <div class="text-center">
                                                <span class="stock-badge">
                                                    Stock: <?php echo $row['stock']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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

        <!-- Add Item Modal -->
        <div class="modal fade" id="additem" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="process_addproduct.php" method="POST" enctype="multipart/form-data">
                        <!-- Modal Body -->
                        <div class="modal-body">
                                <!-- Product Name -->
                                <div class="mb-3">
                                    <label for="productName" class="form-label">Product Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                        <input type="text" class="form-control" id="productName" name="product_name" required>
                                    </div>
                                </div>

                                <!-- Photo Upload -->
                                <div class="mb-3">
                                    <label for="productPhoto" class="form-label">Product Photo</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-image"></i></span>
                                        <input type="file" class="form-control" id="productPhoto" name="product_photo" accept="image/png, image/jpeg" required>
                                    </div>
                                </div>

                                <!-- Water Price -->
                                <div class="mb-3">
                                    <label for="waterPrice" class="form-label">Water Price (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="waterPrice" name="water_price" step="0.01" required>
                                    </div>
                                </div>

                                <!-- Stock -->
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                        <input type="number" class="form-control" id="stock" name="stock" required>
                                    </div>
                                </div>

                            
                        </div>
            
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Stock Modal -->
        <div class="modal fade" id="addstock" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="process_addstock.php" method="POST" enctype="multipart/form-data">
                        <!-- Modal Body -->
                        <div class="modal-body">
                                <!-- Product Name -->
                                <div class="mb-3">
                                    <label for="productName" class="form-label">Product Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                        <select name="product_id" class="form-select">
                                            <option value="">Select Product</option>
                                            <?php foreach($products_data as $row):?>
                                                <option value="<?php echo $row['product_id']?>"><?php echo $row['product_name']?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Stock -->
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                        <input type="number" class="form-control" id="stock" name="stock" required>
                                    </div>
                                </div>

                            
                        </div>
            
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <div class="modal fade" id="edititem" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="process_editproduct.php" method="POST" enctype="multipart/form-data">
                        <input type="text" name="product_id" id="editProductId" required hidden>
                        <!-- Modal Body -->
                        <div class="modal-body">

                            <!-- Product Image -->
                            <div class="mb-3 text-center">
                                <label class="form-label">Product Image</label>
                                <div class="d-flex justify-content-center position-relative">
                                    <img id="editProductImagePreview" 
                                        src="default-image.jpg" 
                                        alt="Product Image" 
                                        class="img-fluid rounded border"
                                        style="width: 150px; height: 150px; object-fit: cover; cursor: pointer;"
                                        onclick="document.getElementById('editProductImage').click();">
                                    <div class="position-absolute top-50 start-50 translate-middle d-none bg-dark bg-opacity-50 rounded-circle p-2"
                                        id="editIcon"
                                        style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-pencil text-white"></i>
                                    </div>
                                </div>
                                <input type="file" class="form-control mt-2" id="editProductImage" name="product_photo" accept="image/*" hidden>
                            </div>

                            <script>
                                const imagePreview = document.getElementById('editProductImagePreview');
                                const editIcon = document.getElementById('editIcon');
                                const fileInput = document.getElementById('editProductImage');

                                imagePreview.addEventListener('mouseenter', () => {
                                    editIcon.classList.remove('d-none');
                                });

                                imagePreview.addEventListener('mouseleave', () => {
                                    editIcon.classList.add('d-none');
                                });

                                fileInput.addEventListener('change', (event) => {
                                    const file = event.target.files[0]; // Get the selected file
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            imagePreview.src = e.target.result; // Set new image source
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                            </script>


                                <!-- Product Name -->
                                <div class="mb-3">
                                    <label for="productName" class="form-label">Product Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                        <input type="text" class="form-control" id="editProductName" name="product_name" required>
                                    </div>
                                </div>

                                <!-- Water Price -->
                                <div class="mb-3">
                                    <label for="waterPrice" class="form-label">Water Price (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="editWaterPrice" name="water_price" step="0.01" required>
                                    </div>
                                </div>

                                <!-- Container Price -->
                                <div class="mb-3">
                                    <label for="containerPrice" class="form-label">Container Price (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="editContainerPrice" name="container_price" step="0.01" required>
                                    </div>
                                </div>

                                <!-- Stock -->
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                        <input type="number" class="form-control" id="editStock" name="stock" required>
                                    </div>
                                </div>

                        </div>
            
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(document.getElementById('editProductId').value)">Delete Product</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
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

            function confirmDelete(productId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the product!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to delete script
                    window.location.href = `delete_product.php?id=${productId}`;
                }
            });
}
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
            <?php elseif(isset($_GET['deleted']) && $_GET['deleted'] == 0): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Something went wrong.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
            <?php elseif(isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Product deleted successfully.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php endif; ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {
                $('.editProductCard').on('click', function () {
                    var productId = $(this).data('id');

                    $.ajax({
                        url: 'process_getproductdata.php',
                        method: 'POST',
                        data: { product_id: productId },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                $('#editProductId').val(productId);
                                $('#editProductName').val(response.data.product_name);
                                $('#editWaterPrice').val(response.data.water_price);
                                $('#editContainerPrice').val(response.data.container_price);
                                $('#editStock').val(response.data.stock);
                                $('#editProductImagePreview').attr('src', response.data.product_photo);
                            } else {
                                alert('Product not found.');
                            }
                        },
                        error: function () {
                            alert('Failed to load product details.');
                        }
                    });
                });
            });
        </script>
         <script>
        document.getElementById('markAllReadBtn').addEventListener('click', function (e) {
            e.preventDefault(); // Stop the link from navigating

            fetch('mark_all_read.php')
                .then(response => {
                    if (response.ok) {2
                        // Hide or clear the badge
                        const badge = document.getElementById('notificationBadge');
                        if (badge) {
                            badge.style.display = 'none'; // or badge.classList.add('d-none');
                        }

                        // Optionally clear notifications list in the dropdown
                        const dropdownList = document.getElementById('notificationList'); // example id
                        if (dropdownList) {
                            dropdownList.innerHTML = '<span class="dropdown-item text-center small text-muted">No new notifications</span>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Failed to mark all as read:', error);
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
