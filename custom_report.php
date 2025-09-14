<?php 
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    try {
        require 'session.php';
        require 'db.php';

        $user_id = $_SESSION['user_id'];
        $role_id = $_SESSION['role_id'];
        if($role_id == 2){
            header("Location: home.php");
            exit();
        }else if ($role_id == 3){
            header("Location: riderdashboard.php");
            exit();
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }

    // Get date range from URL parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    // Validate date range
    if (empty($start_date) || empty($end_date)) {
        header('Location: report.php');
        exit();
    }

    if (strtotime($start_date) > strtotime($end_date)) {
        header('Location: report.php');
        exit();
    }

    try {
        $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
        JOIN user_details ud ON u.user_id = ud.user_id
        WHERE u.user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }

    // Get orders within date range
    try {
        $sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.contact_number, 
                c.status_name, d.firstname AS rider_firstname, d.lastname AS rider_lastname, e.payment_name
                FROM orders a
                JOIN user_details b ON a.user_id = b.user_id
                JOIN orderstatus c ON a.status_id = c.status_id
                LEFT JOIN user_details d ON a.rider = d.user_id
                JOIN payment_method e ON a.payment_id = e.payment_id
                WHERE DATE(a.date) BETWEEN :start_date AND :end_date
                AND (a.status_id = 4 OR a.status_id = 5)
                ORDER BY a.date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Orders Query Error: " . $e->getMessage());
    }

    // Get expenses within date range
    try {
        $sql = "SELECT a.expense_id, a.date, a.amount, a.comment, b.expensetype_name
                FROM expense a
                JOIN expensetype b ON a.expensetype_id = b.expensetype_id
                WHERE DATE(a.date) BETWEEN :start_date AND :end_date
                ORDER BY a.date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $expense_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Expenses Query Error: " . $e->getMessage());
    }

    // Calculate totals
    $total_sales = array_sum(array_column($order_data, 'amount'));
    $total_expenses = array_sum(array_column($expense_data, 'amount'));
    $net_income = $total_sales - $total_expenses;

    try {
        $sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE read_status = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $unread_count = $unread_result['unread_count'];
    } catch (PDOException $e) {
        $unread_count = 0; // Default value if query fails
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
        <title>Custom Report</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
                        try {
                            $sql = "SELECT status FROM store_status WHERE ss_id = 1";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                            $status = $stmt->fetchColumn();
                        } catch (PDOException $e) {
                            $status = 0; // Default to closed if query fails
                        }
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
                        <h1 class="mt-4">Custom Report</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="report.php">Report</a></li>
                            <li class="breadcrumb-item active">Custom Report</li>
                        </ol>
                        <!-- Back Button -->
                        <div class="d-flex justify-content-end mb-3">
                            <a href="report.php" class="btn btn-secondary me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Back to Reports">
                                <i class="fas fa-arrow-left"></i>
                            </a>

                            <button id="downloadPDF" class="btn btn-danger me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Report as PDF">
                                <i class="fas fa-download"></i>
                            </button>


                            <button id="printReport" onclick="printReport()" class="btn btn-primary me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Print Report">
                                <i class="fas fa-print"></i>
                            </button>

                            <button id="viewReportBtn" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="View Report">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <!-- Report Summary -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-calendar-alt"></i> Report Period: <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?></h5>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <strong>Total Sales:</strong> ₱<?php echo number_format($total_sales, 2); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Expenses:</strong> ₱<?php echo number_format($total_expenses, 2); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Net Income:</strong> ₱<?php echo number_format($net_income, 2); ?>
                                </div>
                            </div>
                        </div>

                        <h1>💧 DoodsNer Water Refilling Station</h1>
                        <h5>📅 Custom Sales & Expense Report - <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?></h5>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Sales (<?php echo count($order_data); ?> orders)
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Rider</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_data as $row):?>
                                            <tr>
                                                <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                                <td><?php echo $row['status_name']; ?></td>
                                                <td><?php echo $row['rider_firstname'] . ' ' . $row['rider_lastname']; ?></td>
                                            </tr>
                                        <?php endforeach;?>
                                        <tr class="table-success">
                                            <td colspan="2"><strong>Total Sales</strong></td>
                                            <td><strong>₱<?php echo number_format($total_sales, 2); ?></strong></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Expenses (<?php echo count($expense_data); ?> entries)
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple1">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Purpose</th>
                                            <th>Comment</th>
                                            <th>Amount (₱)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($expense_data as $row):?>
                                            <tr>
                                                <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['expensetype_name']; ?></td>
                                                <td><?php echo $row['comment']; ?></td>
                                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach;?>
                                        <tr class="table-danger">
                                            <td colspan="3"><strong>Total Expenses</strong></td>
                                            <td><strong>₱<?php echo number_format($total_expenses, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Income Summary
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple2">
                                    <thead>
                                        <tr>
                                            <th>Total Sales</th>
                                            <th>Less : Expenses</th>
                                            <th>Net Income</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>₱<?php echo number_format($total_sales, 2); ?></td>
                                            <td>₱<?php echo number_format($total_expenses, 2); ?></td>
                                            <td class="table-<?php echo $net_income >= 0 ? 'success' : 'danger'; ?>"><strong>₱<?php echo number_format($net_income, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>    
            </div>
        </div>
        <div id="reportContent" class="d-none">
            <!-- Your HTML report goes here -->
            <h2>DoodsNer Water Refilling Station</h2>
            <h3>Custom Sales & Expense Report</h3>
            <p>Period: <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?></p>
            
            <h4>Summary</h4>
            <p>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></p>
            <p>Total Expenses: ₱<?php echo number_format($total_expenses, 2); ?></p>
            <p><strong>Net Income: ₱<?php echo number_format($net_income, 2); ?></strong></p>

            <h4>Sales</h4>
            <table class="table table-bordered table-sm">
                <thead>
                    <tr><th>Date</th><th>Customer</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <?php foreach($order_data as $row): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-end"><strong>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></strong></p>       
            <hr>    
            <h4>Expenses</h4>
            <table class="table table-bordered table-sm">
                <thead>
                    <tr><th>Date</th><th>Purpose</th><th>Comment</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <?php foreach($expense_data as $row): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['expensetype_name']; ?></td>
                        <td><?php echo $row['comment']; ?></td>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-end"><strong>Total Expenses: ₱<?php echo number_format($total_expenses, 2); ?></strong></p>
            <hr>           
            <footer>
                <p><i>Generated by AquaDrop Water Ordering System</i></p>
            </footer>
        </div>

        <div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewReportModalLabel">Report Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reportPreviewContent">
                    <!-- Dynamic content injected here -->
                </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
            window.addEventListener('DOMContentLoaded', event => {
                // Simple-DataTables
                const datatablesSimple = document.getElementById('datatablesSimple');
                if (datatablesSimple) {
                    new simpleDatatables.DataTable(datatablesSimple);
                }
                const datatablesSimple2 = document.getElementById('datatablesSimple2');
                if (datatablesSimple2) {
                    new simpleDatatables.DataTable(datatablesSimple2);
                }
                const datatablesSimple1 = document.getElementById('datatablesSimple1');
                if (datatablesSimple1) {
                    new simpleDatatables.DataTable(datatablesSimple1);
                }
            });
        </script>

        <script>
            document.getElementById("downloadPDF").addEventListener("click", function () {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF();
                let y = 15;

                // Title
                pdf.setFont("helvetica", "bold");
                pdf.setFontSize(18);
                pdf.text("DoodsNer Water Refilling Station", 105, y, { align: "center" });
                y += 8;

                pdf.setFontSize(14);
                pdf.setFont("helvetica", "normal");
                pdf.text("Custom Sales & Expense Report", 105, y, { align: "center" });
                y += 6;

                pdf.setFontSize(12);
                pdf.text("Period: <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?>", 105, y, { align: "center" });
                y += 15;

                // --- SUMMARY ---
                pdf.setFont("helvetica", "bold");
                pdf.text("Summary", 14, y);
                y += 6;

                pdf.setFont("helvetica", "normal");
                pdf.text("Total Sales: Php <?php echo number_format($total_sales, 2); ?>", 14, y); y += 6;
                pdf.text("Total Expenses: Php <?php echo number_format($total_expenses, 2); ?>", 14, y); y += 6;
                pdf.setFont("helvetica", "bold");
                pdf.text("Net Income: Php <?php echo number_format($net_income, 2); ?>", 14, y); y += 12;

                // --- SALES TABLE ---
                pdf.setFont("helvetica", "bold");
                pdf.text("Sales (<?php echo count($order_data); ?> orders)", 14, y);
                y += 6;

                pdf.setFontSize(11);
                pdf.text("Date", 14, y);
                pdf.text("Customer", 70, y);
                pdf.text("Amount (Php)", 170, y, { align: "right" });
                y += 6;

                pdf.setFont("helvetica", "normal");
                <?php foreach($order_data as $row): ?>
                    if (y > 270) { pdf.addPage(); y = 15; }
                    pdf.text("<?php echo date('M d, Y', strtotime($row['date'])); ?>", 14, y);
                    pdf.text("<?php echo $row['firstname'] . ' ' . $row['lastname']; ?>", 70, y);
                    pdf.text("<?php echo number_format($row['amount'], 2); ?>", 170, y, { align: "right" });
                    y += 6;
                <?php endforeach; ?>

                pdf.setFont("helvetica", "bold");
                pdf.text("Total Sales: Php <?php echo number_format($total_sales, 2); ?>", 170, y, { align: "right" });
                y += 12;

                // --- EXPENSES TABLE ---
                pdf.setFont("helvetica", "bold");
                pdf.text("Expenses (<?php echo count($expense_data); ?> entries)", 14, y);
                y += 6;

                pdf.setFontSize(11);
                pdf.text("Date", 14, y);
                pdf.text("Purpose", 70, y);
                pdf.text("Amount (Php)", 170, y, { align: "right" });
                y += 6;

                pdf.setFont("helvetica", "normal");
                <?php foreach($expense_data as $row): ?>
                    if (y > 270) { pdf.addPage(); y = 15; }
                    pdf.text("<?php echo date('M d, Y', strtotime($row['date'])); ?>", 14, y);
                    pdf.text("<?php echo $row['expensetype_name']; ?>", 70, y);
                    pdf.text("<?php echo number_format($row['amount'], 2); ?>", 170, y, { align: "right" });
                    y += 6;
                <?php endforeach; ?>

                pdf.setFont("helvetica", "bold");
                pdf.text("Total Expenses: Php <?php echo number_format($total_expenses, 2); ?>", 170, y, { align: "right" });
                y += 12;

                // --- NET INCOME SUMMARY ---
                pdf.setFontSize(12);
                pdf.setFont("helvetica", "bold");
                pdf.text("Net Income Summary", 14, y);
                y += 6;

                pdf.setFont("helvetica", "normal");
                pdf.text("Total Sales: Php <?php echo number_format($total_sales, 2); ?>", 14, y); y += 6;
                pdf.text("Total Expenses: Php <?php echo number_format($total_expenses, 2); ?>", 14, y); y += 6;
                pdf.setFont("helvetica", "bold");
                pdf.text("Net Income: Php <?php echo number_format($net_income, 2); ?>", 14, y); y += 15;

                // Footer
                pdf.setFont("helvetica", "italic");
                pdf.setFontSize(10);
                pdf.text("Generated by AquaDrop Water Ordering System", 105, 285, { align: "center" });

                // Save PDF
                const filename = `Custom_Report_<?php echo date('Ymd', strtotime($start_date)); ?>-<?php echo date('Ymd', strtotime($end_date)); ?>.pdf`;
                pdf.save(filename);
            });
        </script>

        <script>
            function printReport() {
                var printContents = document.getElementById("reportContent").innerHTML;
                var originalContents = document.body.innerHTML;

                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;

                // Optional: Reload the page to reattach event listeners
                location.reload();
            }
        </script>
        <script>
            document.getElementById('viewReportBtn').addEventListener('click', function () {
            const reportHTML = `
                <div class="text-center">
                <h3><strong>DoodsNer Water Refilling Station</strong></h3>
                <h5>Custom Sales & Expense Report</h5>
                <p>Period: <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?></p>
                </div>

                <hr>

                <h5><strong>Summary</strong></h5>
                <p>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></p>
                <p>Total Expenses: ₱<?php echo number_format($total_expenses, 2); ?></p>
                <p><strong>Net Income: ₱<?php echo number_format($net_income, 2); ?></strong></p>

                <hr>

                <h5><strong>Sales (<?php echo count($order_data); ?> orders)</strong></h5>
                <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Amount (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($order_data as $row): ?>
                    <tr>
                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                    <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                    <td class="text-end">₱<?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
                <p class="text-end"><strong>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></strong></p>

                <hr>

                <h5><strong>Expenses (<?php echo count($expense_data); ?> entries)</strong></h5>
                <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                    <th>Date</th>
                    <th>Purpose</th>
                    <th>Comment</th>
                    <th class="text-end">Amount (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($expense_data as $row): ?>
                    <tr>
                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                    <td><?php echo $row['expensetype_name']; ?></td>
                    <td><?php echo $row['comment']; ?></td>
                    <td class="text-end">₱<?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
                <p class="text-end"><strong>Total Expenses: ₱<?php echo number_format($total_expenses, 2); ?></strong></p>

                <hr>

                <h5><strong>Net Income Summary</strong></h5>
                <p>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></p>
                <p>Total Expenses: ₱<?php echo number_format($total_expenses, 2); ?></p>
                <p><strong>Net Income: ₱<?php echo number_format($net_income, 2); ?></strong></p>

                <hr>
                <p class="text-center text-muted"><em>Generated by AquaDrop Water Ordering System</em></p>
            `;

            document.getElementById('reportPreviewContent').innerHTML = reportHTML;

            const modal = new bootstrap.Modal(document.getElementById('viewReportModal'));
            modal.show();
            });
            </script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                });
            </script>
    </body>
</html>
