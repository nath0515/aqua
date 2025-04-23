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

    if($user_data['role_id'] != 1){
        header('Location: home.php');
    }
    else{
        //asd
    }

    $sql = "SELECT SUM(amount) as total_sales FROM orders WHERE status_id = 4 AND DATE(date) = CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $amount = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(amount) as total_expense FROM expense";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $amount1 = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(amount) as total_sales FROM orders WHERE status_id = 4";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $amount2 = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT count(*) as order_count FROM orders WHERE DATE(date) = CURDATE() ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
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
                <li class="nav-item dropdown me-3">
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
                                    <a class="nav-link" href="#">Stock</a>
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
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Net Income<ol class="breadcrumb">₱ <?php echo number_format(($amount2['total_sales']-$amount1['total_expense']),2); ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <div class="small text-white"><i class="fas fa-angle-right text-success"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Expenses<ol class="breadcrumb">₱ <?php echo number_format($amount1['total_expense'],2); ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body" style="font-size:25px">Orders Today<ol class="breadcrumb"><?php echo $orders['order_count']; ?></ol></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="orders.php">View Details</a>
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
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Bar Chart Example
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container"><div class="chartjs-size-monitor" style="position: absolute; inset: 0px; overflow: hidden; pointer-events: none; visibility: hidden; z-index: -1;"><div class="chartjs-size-monitor-expand" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;"><div style="position:absolute;width:1000000px;height:1000000px;left:0;top:0"></div></div><div class="chartjs-size-monitor-shrink" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;"><div style="position:absolute;width:200%;height:200%;left:0; top:0"></div></div></div>
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
        <script src="js/datatables-simple-demo.js"></script>

        <?php 
            // Fetch Sales Data
            $sql = "SELECT DATE(date) AS day, SUM(amount) AS total_price FROM orders
                    WHERE status_id = 4
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

        
        
    </body>
</html>
