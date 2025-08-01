<?php 
    require 'session.php';
    require 'db.php';
    $dateNow = date("Y-m-d");

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


    $sql = "SELECT SUM(amount) as total_sales FROM orders WHERE status_id = 5 AND DATE(date) = :dateNow";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $dateNow);
    $stmt->execute();
    $amount = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(amount) as total_expense FROM expense WHERE DATE(date) = :dateNow";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $dateNow);
    $stmt->execute();
    $amount1 = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(amount) as total_sales FROM orders WHERE status_id = 5 AND DATE(date) = :dateNow";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $dateNow);
    $stmt->execute();
    $amount2 = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT count(*) as order_count FROM orders WHERE DATE(date) = :dateNow ";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $dateNow);
    $stmt->execute();
    $orders = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE read_status = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = $unread_result['unread_count'];

    $dateToday = date('Y-m-d');
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days', strtotime($dateToday)));
$yesterday = date('Y-m-d', strtotime('-1 day', strtotime($dateToday)));

// Prepare a date map with zeros
$dateMap = [];
for ($i = 7; $i >= 1; $i--) {
    $day = date('Y-m-d', strtotime("-$i days", strtotime($dateToday)));
    $dateMap[$day] = 0;
}

// Fetch order quantities over the last 7 days
$sql = "SELECT DATE(orders.date) AS order_date, SUM(orderitems.quantity) AS total_quantity
        FROM orderitems
        JOIN orders ON orderitems.order_id = orders.order_id
        WHERE DATE(orders.date) BETWEEN DATE(:seven_days_ago) AND DATE(:yesterday)
        GROUP BY order_date";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':seven_days_ago' => $sevenDaysAgo,
    ':yesterday' => $yesterday
]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill in quantities into the date map
foreach ($data as $row) {
    $dateMap[$row['order_date']] = (int)$row['total_quantity'];
}

// Calculate SMA
$totalQuantity = array_sum($dateMap);
$sma = round($totalQuantity / 7);
    
    ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Dashboard</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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


            .notification-text{
                white-space: nowrap;
                overflow: hidden;D
                text-overflow: ellipsis;
                display: block;
                max-width: 200px;
                
            }
            .notification-text.fw-bold {
                font-weight: 600;
                color: #000;
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
                <?php 
                    $sql = "SELECT * FROM activity_logs ORDER BY date DESC LIMIT 3";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $activity_logs = $stmt->fetchAll();
                ?>
                
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
                        <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                    <?php echo $unread_count; ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                            <?php endif; ?>
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown" style="min-width: 250px;">
                        <li class="dropdown-header fw-bold text-dark">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach($activity_logs as $row):?>
                         <li><a class="dropdown-item notification-text" href="process_readnotification.php?id=<?php echo $row['activitylogs_id']?>&destination=<?php echo $row['destination']?>"><?php echo $row['message'];?></a></li>
                        <hr>
                        <?php endforeach; ?>
                        <li><a class="dropdown-item text-center text-muted small" href="activitylogs.php">View all notifications</a></li>
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
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        <?php echo "".$user_data['firstname']." ".$user_data['lastname'];?>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Dashboard</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                        <div class="row">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Sales Today<ol class="breadcrumb">₱ <?php echo number_format($amount['total_sales'],2); ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="sales.php">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div id="loadingOverlay">
                                <div class="spinner"></div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Net Income Today<ol class="breadcrumb">₱ <?php echo number_format(($amount2['total_sales']-$amount1['total_expense']),2); ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="income.php">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right text-success"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Expenses Today<ol class="breadcrumb">₱ <?php echo number_format($amount1['total_expense'],2); ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="expenses.php">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Orders Today<ol class="breadcrumb"><?php echo $orders['order_count']; ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="orders.php?from_card">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">Sales Chart</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container"><div class="chartjs-size-monitor" style="position: absolute; inset: 0px; overflow: hidden; pointer-events: none; visibility: hidden; z-index: -1;"><div class="chartjs-size-monitor-expand" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;"><div style="position:absolute;width:1000000px;height:1000000px;left:0;top:0"></div></div><div class="chartjs-size-monitor-shrink" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;"><div style="position:absolute;width:200%;height:200%;left:0; top:0"></div></div></div>
                                            <canvas id="multipleLineChart" width="491" height="300" style="display: block; width: 491px; height: 300px;" class="chartjs-render-monitor"></canvas>
                                        </div>
                                        <form action="expenses.php" method="GET">
                                            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
                                                <div>
                                                    <label for="start_date" class="form-label">Start Date</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                                                </div>
                                                <div>
                                                    <label for="end_date" class="form-label">End Date</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                                                </div>
                                                <div>
                                                    <label class="form-label d-block">&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">Income Chart</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container"><div class="chartjs-size-monitor" style="position: absolute; inset: 0px; overflow: hidden; pointer-events: none; visibility: hidden; z-index: -1;"><div class="chartjs-size-monitor-expand" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;"><div style="position:absolute;width:1000000px;height:1000000px;left:0;top:0"></div></div><div class="chartjs-size-monitor-shrink" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;"><div style="position:absolute;width:200%;height:200%;left:0; top:0"></div></div></div>
                                            <canvas id="incomeChart" width="491" height="300" style="display: block; width: 491px; height: 300px;" class="chartjs-render-monitor"></canvas>
                                        </div>
                                        <form action="expenses.php" method="GET">
                                            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
                                                <div>
                                                    <label for="start_date" class="form-label">Start Date</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                                                </div>
                                                <div>
                                                    <label for="end_date" class="form-label">End Date</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                                                </div>
                                                <div>
                                                    <label class="form-label d-block">&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-calculator me-1"></i>
                                        Predicted Production for Today
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Predicted Production for Today:</strong></p>
                                        <p><?php echo $sma !== null ? number_format($sma) . ' gallons' : 'Not enough data'; ?></p> 
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Bar Chart Card -->
                            <div class="col-xl-12">
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Bar Chart Example
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <div class="chartjs-size-monitor" style="position: absolute; inset: 0px; overflow: hidden; pointer-events: none; visibility: hidden; z-index: -1;">
                                                <div class="chartjs-size-monitor-expand" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;">
                                                    <div style="position:absolute;width:1000000px;height:1000000px;left:0;top:0"></div>
                                                </div>
                                                <div class="chartjs-size-monitor-shrink" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;">
                                                    <div style="position:absolute;width:200%;height:200%;left:0; top:0"></div>
                                                </div>
                                            </div>
                                            <canvas id="barChart" width="491" height="300" style="display: block; width: 491px; height: 300px;" class="chartjs-render-monitor"></canvas>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="js/datatables-simple-demo.js"></script>

        <script>
            let deferredPrompt;

            // Listen for the beforeinstallprompt event
            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('beforeinstallprompt fired'); // Add this
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
        

        <?php 
            // Fetch Sales Data
            $sql = "SELECT DATE(date) AS day, SUM(amount) AS total_price FROM orders
                    WHERE status_id = 5
                    GROUP BY DATE(date)
                    ORDER BY day ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Expenses Data (Target Sales)
            $sql = "SELECT DATE(date) AS day, SUM(amount) AS total_expense FROM expense
                    GROUP BY DATE(date)
                    ORDER BY day ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $labels = [];
            $sales_values = [];
            $expenses_values = [];
            $income_values = [];

            // Combine Sales and Expenses Data
            $sales_data = [];
            foreach ($sales as $row) {
                $sales_data[$row['day']] = (float) $row['total_price'];
            }

            $expenses_data = [];
            foreach ($expenses as $row) {
                $expenses_data[$row['day']] = (float) $row['total_expense'];
            }

            // Collect labels and data for both Sales and Expenses
            $all_dates = array_merge(array_keys($sales_data), array_keys($expenses_data));
            $all_dates = array_unique($all_dates);  // Remove duplicates

            foreach ($all_dates as $date) {
                $labels[] = date("M d, Y", strtotime($date));
                $sales_values[] = isset($sales_data[$date]) ? $sales_data[$date] : 0;
                $expenses_values[] = isset($expenses_data[$date]) ? $expenses_data[$date] : 0;
                $income_values[] = isset($sales_data[$date]) ? $sales_data[$date] - (isset($expenses_data[$date]) ? $expenses_data[$date] : 0) : 0;
            }

            // Fetch Product Data
            $sql = "SELECT product_name, stock FROM products";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $productNames = [];
            $productStocks = [];
            $colors = [];
            
            $lowStockThreshold = 10;

            foreach ($products as $product) {
                $productNames[] = $product['product_name'];
                $productStocks[] = $product['stock'];
                
                if ($product['stock'] < $lowStockThreshold) {
                    $colors[] = 'rgb(255, 99, 71)';  // Red color for low stock
                } else {
                    $colors[] = 'rgb(34, 193, 34)';  // Green color for sufficient stock
                }
            }
        ?>

        <script>
            // Prepare chart data for JavaScript
            var labels = <?php echo json_encode($labels); ?>;
            var salesValues = <?php echo json_encode($sales_values); ?>;
            var expensesValues = <?php echo json_encode($expenses_values); ?>;
            var incomeValues = <?php echo json_encode($income_values); ?>;

            var productNames = <?php echo json_encode($productNames); ?>;
            var productStocks = <?php echo json_encode($productStocks); ?>;
            var colors = <?php echo json_encode($colors); ?>;

            // Line Chart: Sales vs Target Sales (Expenses)
            var multipleLineChart = document.getElementById('multipleLineChart').getContext('2d');
            var myMultipleLineChart = new Chart(multipleLineChart, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: "Sales",
                            borderColor: "#1d7af3",
                            pointBorderColor: "#FFF",
                            pointBackgroundColor: "#1d7af3",
                            pointBorderWidth: 2,
                            pointHoverRadius: 4,
                            pointHoverBorderWidth: 1,
                            pointRadius: 4,
                            backgroundColor: 'transparent',
                            fill: true,
                            borderWidth: 2,
                            data: salesValues
                        },
                        {
                            label: "Expenses",
                            borderColor: "#f3545d",
                            pointBorderColor: "#FFF",
                            pointBackgroundColor: "#f3545d",
                            pointBorderWidth: 2,
                            pointHoverRadius: 4,
                            pointHoverBorderWidth: 1,
                            pointRadius: 4,
                            backgroundColor: 'transparent',
                            fill: true,
                            borderWidth: 2,
                            data: expensesValues
                        }
                    ]
                },
                options: {
                    responsive: true, 
                    maintainAspectRatio: false,
                    legend: {
                        position: 'top',
                    },
                    tooltips: {
                        bodySpacing: 4,
                        mode: "nearest",
                        intersect: 0,
                        position: "nearest",
                        xPadding: 10,
                        yPadding: 10,
                        caretPadding: 10
                    },
                    layout: {
                        padding: { left: 15, right: 15, top: 15, bottom: 15 }
                    }
                }
            });

            // Bar Chart: Product Stocks
            var barChart = document.getElementById('barChart').getContext('2d');
            var myBarChart = new Chart(barChart, {
                type: 'bar',
                data: {
                    labels: productNames,
                    datasets: [{
                        label: "Stocks",
                        backgroundColor: colors,
                        borderColor: colors,
                        data: productStocks,
                    }],
                },
                options: {
                    responsive: true, 
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                }
            });

            var incomeChart = document.getElementById('incomeChart').getContext('2d');
            var myIncomeChart = new Chart(incomeChart, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: "Income",
                            borderColor: "#28a745",
                            pointBorderColor: "#FFF",
                            pointBackgroundColor: "#1d7af3",
                            pointBorderWidth: 2,
                            pointHoverRadius: 4,
                            pointHoverBorderWidth: 1,
                            pointRadius: 4,
                            backgroundColor: 'transparent',
                            fill: true,
                            borderWidth: 2,
                            data: incomeValues
                        }]
                },
                options: {
                    responsive: true, 
                    maintainAspectRatio: false,
                    legend: {
                        position: 'top',
                    },
                    tooltips: {
                        bodySpacing: 4,
                        mode: "nearest",
                        intersect: 0,
                        position: "nearest",
                        xPadding: 10,
                        yPadding: 10,
                        caretPadding: 10
                    },
                    layout: {
                        padding: { left: 15, right: 15, top: 15, bottom: 15 }
                    }
                }
            });
        </script>

        <script>
            function confirmCloseShop(event) {
                event.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to close the shop and report the daily sales.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, close it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = event.target.href;
                    }
                });

                return false;
            }
        </script>
        <script>
            function markAsReadAndRedirect(id, url) {
            fetch('mark_notification_read.php?id=' + id)
                .then(() => {
                    window.location.href = url;
                });
        }
        </script>
    </body>
</html>
