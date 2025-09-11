<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    if(!isset($_GET['id'])){
        header('Location: report.php');
        exit();
    }
    else{
        $report_id = $_GET['id'];
    }
    
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

    $sql = "SELECT a.order_id, a.report_id, b.product_id, c.product_name, d.amount, d.date FROM report_content a 
    JOIN orderitems b ON a.order_id = b.order_id
    JOIN products c ON b.product_id = c.product_id
    JOIN orders d ON a.order_id = d.order_id
    WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(b.amount) as total_amount FROM report_content a JOIN orders b ON a.order_id = b.order_id WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $total_amount_data = $stmt->fetch();
    $total_amount = $total_amount_data['total_amount'];

    $sql = "SELECT a.report_id, a.expense_id, b.date, c.expensetype_name, b.amount FROM report_expense a 
    JOIN expense b ON a.expense_id = b.expense_id 
    JOIN expensetype c ON b.expensetype_id = c.expensetype_id
    WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $expense_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(b.amount) as total_amount FROM report_expense a JOIN expense b ON a.expense_id = b.expense_id WHERE a.report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $total_expense_data = $stmt->fetch();
    $total_expense = $total_expense_data['total_amount'];

    $sql = "SELECT income FROM report_income WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $total_income = $stmt->fetchColumn();

    $sql = "SELECT date FROM reports WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $date_data = $stmt->fetchColumn();

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
        <title>Orders</title>
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
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
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
                                    <a class="nav-link" href="stock.php">Stock</a>
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
                    <div id="reportContent">
                    <h1>💧 DoodsNer Water Refilling Station</h1>
                    <h5>📅 Daily Sales & Expense Report - <?php echo date('F j, Y', strtotime($date_data)); ?></h5>
                        <div class="d-flex justify-content-end mb-4">
                            <button id="downloadPDF" class="btn btn-danger me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Receipt as PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button id="printReceipt" class="btn btn-primary me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </button>
                            <button id="viewReceipt" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="View Receipt">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>             
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Sales
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th> Date</th>
                                            <th> Product Name</th>
                                            <th> Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_data as $row):?>
                                            <tr>
                                                <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['product_name'];?></td>
                                                <td>₱<?php echo $row['amount'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td colspan="2" style="text-align: right;"><strong>Total: ₱<?php echo number_format($total_amount, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Expenses
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple1">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Purpose</th>
                                            <th>Amount (₱)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($expense_data as $row):?>
                                            <tr>
                                                <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['expensetype_name'];?></td>
                                                <td>₱<?php echo $row['amount'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td colspan="2" style="text-align: right;"><strong>Total: ₱<?php echo number_format($total_expense, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Income
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
                                            <td><?php echo number_format($total_amount, 2); ?></td>
                                            <td><?php echo number_format($total_expense, 2); ?></td>
                                            <td colspan="2" style="text-align: right;"><strong>Total: ₱<?php echo number_format($total_income, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>    
            </div>
        </div>
        <div id="printableReceipt" style="display: none;">
            <style>
                #printableReceipt {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                }
                h2, h3 {
                    text-align: center;
                    margin: 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                }
                th, td {
                    border-bottom: 1px solid #ccc;
                    padding: 5px;
                    text-align: left;
                }
                .right {
                    text-align: right;
                }
                .section-title {
                    margin-top: 20px;
                    font-weight: bold;
                }
            </style>

            <h2>DoodsNer Water Refilling Station</h2>
            <h3>Daily Sales & Expense Report</h3>
            <p style="text-align: center;">Date: <?php echo date('F j, Y', strtotime($date_data)); ?></p>

            <div class="section-title">Sales</div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th class="right">Amount (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($order_data as $row): ?>
                    <tr>
                        <td><?php echo date('M d, Y g:iA', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['product_name']; ?></td>
                        <td class="right"><?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="2"><strong>Total Sales</strong></td>
                        <td class="right"><strong>₱<?php echo number_format($total_amount, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="section-title">Expenses</div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Purpose</th>
                        <th class="right">Amount (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($expense_data as $row): ?>
                    <tr>
                        <td><?php echo date('M d, Y g:iA', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['expensetype_name']; ?></td>
                        <td class="right"><?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="2"><strong>Total Expenses</strong></td>
                        <td class="right"><strong>₱<?php echo number_format($total_expense, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="section-title">Net Income Summary</div>
            <table>
                <tbody>
                    <tr>
                        <td>Total Sales</td>
                        <td class="right">₱<?php echo number_format($total_amount, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Expenses</td>
                        <td class="right">₱<?php echo number_format($total_expense, 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Net Income</strong></td>
                        <td class="right"><strong>₱<?php echo number_format($total_income, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <p style="text-align: center; font-style: italic; margin-top: 40px;">
                Generated by AquaDrop Water Ordering System
            </p>
        </div>
         <!-- Receipt Modal -->
        <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptContent">
                <!-- Receipt HTML will be injected here -->
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                $("#editOrderBtn").click(function() {
                    var orderId = $(this).data("id");

                    $.ajax({
                        url: "process_getorderdata.php",
                        type: "POST",
                        data: { order_id: orderId },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                const orderItems = response.data2;

                                $("#editStatusId").val(response.data.status_id);


                                let itemsHtml = '<h5>Order Items:</h5>';
                                orderItems.forEach(item => {
                                    itemsHtml += `
                                        <div>
                                            <p>Item: ${item.product_name}</p>
                                            <p>Quantity: ${item.quantity}</p>
                                            <p>Price: ₱${item.price}</p>
                                        </div>
                                    `;
                                });
                                $('#orderItemsContainer').html(itemsHtml);
                                
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
        <script>
            window.addEventListener('DOMContentLoaded', event => {
            // Simple-DataTablesa
            // https://github.com/fiduswriter/Simple-DataTables/wikia

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
            pdf.text("Daily Sales & Expense Report", 105, y, { align: "center" });
            y += 6;

            pdf.setFontSize(12);
            pdf.text("Date: <?php echo date('F j, Y', strtotime($date_data)); ?>", 105, y, { align: "center" });
            y += 15;

            // --- SALES TABLE ---
            pdf.setFont("helvetica", "bold");
            pdf.text("Sales", 14, y);
            y += 6;

            pdf.setFontSize(11);
            pdf.text("Date", 14, y);
            pdf.text("Product", 70, y);
            pdf.text("Amount (Php)", 170, y, { align: "right" });
            y += 6;
            pdf.setFont("helvetica", "normal");

            <?php foreach($order_data as $row): ?>
                pdf.text("<?php echo date('M d, Y g:iA', strtotime($row['date'])); ?>", 14, y);
                pdf.text("<?php echo $row['product_name']; ?>", 70, y);
                pdf.text("<?php echo number_format($row['amount'], 2); ?>", 170, y, { align: "right" });
                y += 6;
            <?php endforeach; ?>

            pdf.setFont("helvetica", "bold");
            pdf.text("Total Sales: Php <?php echo number_format($total_amount, 2); ?>", 170, y, { align: "right" });
            y += 12;

            // --- EXPENSES TABLE ---
            pdf.setFont("helvetica", "bold");
            pdf.text("Expenses", 14, y);
            y += 6;

            pdf.setFontSize(11);
            pdf.text("Date", 14, y);
            pdf.text("Purpose", 70, y);
            pdf.text("Amount (Php)", 170, y, { align: "right" });
            y += 6;
            pdf.setFont("helvetica", "normal");

            <?php foreach($expense_data as $row): ?>
                pdf.text("<?php echo date('M d, Y g:iA', strtotime($row['date'])); ?>", 14, y);
                pdf.text("<?php echo $row['expensetype_name']; ?>", 70, y);
                pdf.text("<?php echo number_format($row['amount'], 2); ?>", 170, y, { align: "right" });
                y += 6;
            <?php endforeach; ?>

            pdf.setFont("helvetica", "bold");
            pdf.text("Total Expenses: Php <?php echo number_format($total_expense, 2); ?>", 170, y, { align: "right" });
            y += 12;

            // --- INCOME SUMMARY ---
            pdf.setFontSize(12);
            pdf.text("Net Income Summary", 14, y);
            y += 6;

            pdf.setFont("helvetica", "normal");
            pdf.text("Total Sales: Php <?php echo number_format($total_amount, 2); ?>", 14, y);
            y += 6;
            pdf.text("Total Expenses: Php <?php echo number_format($total_expense, 2); ?>", 14, y);
            y += 6;
            pdf.setFont("helvetica", "bold");
            pdf.text("Net Income: Php <?php echo number_format($total_income, 2); ?>", 14, y);
            y += 15;

            // Footer
            pdf.setFont("helvetica", "italic");
            pdf.setFontSize(10);
            pdf.text("Generated by AquaDrop Water Ordering System", 105, 285, { align: "center" });

            // Save PDF
            const filename = `Report_<?php echo date('Ymd', strtotime($date_data)); ?>.pdf`;
            pdf.save(filename);
        });

        </script>
        <script>
            document.getElementById("printReceipt").addEventListener("click", function () {
                const content = document.getElementById("printableReceipt").innerHTML;
                const printWindow = window.open("", "", "width=800,height=600");
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print Receipt</title>
                        </head>
                        <body onload="window.print(); window.close();">
                            ${content}
                        </body>
                    </html>
                `);
                printWindow.document.close();
            });
        </script>
        <script>
            document.getElementById("viewReceipt").addEventListener("click", function () {
                const receiptContent = document.getElementById("printableReceipt").innerHTML;
                document.getElementById("receiptContent").innerHTML = receiptContent;

                // Show the Bootstrap modal
                const modal = new bootstrap.Modal(document.getElementById("receiptModal"));
                modal.show();
            });
        </script>

    </body>
</html>
