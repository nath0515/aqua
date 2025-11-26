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

    $sql = "SELECT a.order_id, a.report_id, b.product_id,b.quantity,b.isDiscounted,c.product_name,c.water_price,c.water_price_promo,d.amount, d.date FROM report_content a 
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

    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;

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
             .custom-navbar {
            background: linear-gradient(135deg, #0077b6, #005a8b) !important;
        }
    </style>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark custom-navbar">
            <!-- Navbar Brand-->
           <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 220px; height: 60px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
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
                            <a class="nav-link <?php echo ($current_page == 'orders.php' || $current_page == 'stock.php') ? '' : 'collapsed'; ?>" 
                            href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="<?php echo ($current_page == 'orders.php' || $current_page == 'stock.php') ? 'true' : 'false'; ?>" 
                            aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Order Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse <?php echo ($current_page == 'orders.php' || $current_page == 'stock.php') ? 'show' : ''; ?>" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" href="orders.php">Orders</a>
                                    <a class="nav-link <?php echo $current_page == 'stock.php' ? 'active' : ''; ?>" href="stock.php">Stock</a>
                                </nav>
                            </div>

                            <!-- Analytics -->
                            <a class="nav-link <?php echo ($current_page == 'sales.php' || $current_page == 'expenses.php' || $current_page == 'report.php') ? '' : 'collapsed'; ?>" 
                            href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="<?php echo ($current_page == 'sales.php' || $current_page == 'expenses.php' || $current_page == 'report.php') ? 'true' : 'false'; ?>" 
                            aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Analytics
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse <?php echo ($current_page == 'sales.php' || $current_page == 'expenses.php' || $current_page == 'report.php') ? 'show' : ''; ?>" id="collapsePages" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link <?php echo $current_page == 'sales.php' ? 'active' : ''; ?>" href="sales.php">Sales</a>
                                    <a class="nav-link <?php echo $current_page == 'expenses.php' ? 'active' : ''; ?>" href="expenses.php">Expenses</a>
                                    <a class="nav-link <?php echo $current_page == 'report.php' ? 'active' : ''; ?>" href="report.php">Report</a>
                                </nav>
                            </div>

                            <!-- Account Management -->
                            <a class="nav-link <?php echo ($current_page == 'accounts.php' || $current_page == 'rideraccount.php' || $current_page == 'adminaccount.php' || $current_page == 'addstaff.php' || $current_page == 'applications.php') ? '' : 'collapsed'; ?>" 
                            href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts2" aria-expanded="<?php echo ($current_page == 'accounts.php' || $current_page == 'rideraccount.php' || $current_page == 'adminaccount.php' || $current_page == 'addstaff.php' || $current_page == 'applications.php') ? 'true' : 'false'; ?>" 
                            aria-controls="collapseLayouts2">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Account Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse <?php echo ($current_page == 'accounts.php' || $current_page == 'rideraccount.php' || $current_page == 'adminaccount.php' || $current_page == 'addstaff.php' || $current_page == 'applications.php') ? 'show' : ''; ?>" id="collapseLayouts2" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link <?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>" href="accounts.php">Accounts</a>
                                    <a class="nav-link <?php echo $current_page == 'rideraccount.php' ? 'active' : ''; ?>" href="rideraccount.php">Add Rider</a>
                                    <a class="nav-link <?php echo $current_page == 'adminaccount.php' ? 'active' : ''; ?>" href="adminaccount.php">Add Admin</a>
                                    <a class="nav-link <?php echo $current_page == 'applications.php' ? 'active' : ''; ?>" href="applications.php">Applications</a>
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
                    <h1 class="ms-3">DoodsNer Water Refilling Station</h1>
                    <h5 class="ms-3">Daily Sales & Expense Report - <?php echo date('F j, Y', strtotime($date_data)); ?></h5>
                        <div class="d-flex justify-content-end mb-4">
                            <button id="downloadExcel" class="btn btn-success me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Excel">
                                <i class="fas fa-file-excel"></i>
                            </button>
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
                                            <th>Date</th>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_amount = 0;
                                        foreach($order_data as $row):

                                            // Determine correct unit price
                                            $unit_price = ($row['isDiscounted'] == 1 && $row['water_price_promo'] > 0)
                                                            ? $row['water_price_promo']
                                                            : $row['water_price'];

                                            // Compute amount properly
                                            $line_total = $unit_price * $row['quantity'];

                                            // Add container cost if applicable
                                            if (!empty($row['with_container']) && $row['with_container'] == 1) {
                                                $line_total += $row['container_price'] * $row['container_quantity'];
                                            }

                                            // Add to report total
                                            $total_amount += $line_total;
                                        ?>
                                        <tr>
                                            <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                            <td><?php echo $row['product_name']; ?></td>
                                            <td><?php echo $row['quantity']; ?></td>

                                            <td>
                                                ₱<?php echo number_format($unit_price, 2); ?>
                                            </td>

                                            <td>
                                                ₱<?php echo number_format($line_total, 2); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <tr>
                                            <td></td><td></td><td></td><td style="text-align:right;"><strong>Total:</strong></td>
                                            <td><strong>₱<?php echo number_format($total_amount, 2); ?></strong></td>
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
                                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
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
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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

            function checkPageBreak(y) {
                if (y > 280) {
                    pdf.addPage();
                    return 20;
                }
                return y;
            }

            // =====================
            //  PAGE 1 — TITLE & SALES
            // =====================

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
            pdf.setFontSize(16);
            pdf.text("Sales", 14, y);
            y += 6;

            pdf.setFontSize(11);
            pdf.text("Date", 14, y);
            pdf.text("Product", 60, y);
            pdf.text("Quantity", 115, y);
            pdf.text("Unit Price", 135, y);
            pdf.text("Amount (Php)", 170, y, { align: "right" });
            y += 6;

            pdf.setFont("helvetica", "normal");

            <?php foreach($order_data as $row): ?>

                let qty = "<?php echo $row['quantity']; ?>";
                let unitPrice = "<?php echo ($row['water_price_promo'] > 0 ? number_format($row['water_price_promo'], 2) : number_format($row['water_price'], 2)); ?>";

                pdf.text("<?php echo date('M d, Y g:iA', strtotime($row['date'])); ?>", 14, y);
                pdf.text("<?php echo $row['product_name']; ?>", 60, y);
                pdf.text(qty, 115, y);
                pdf.text(unitPrice, 135, y);
                pdf.text("<?php echo number_format($row['amount'], 2); ?>", 170, y, { align: "right" });

                y += 6;
                y = checkPageBreak(y);
            <?php endforeach; ?>

            pdf.setFont("helvetica", "bold");
            pdf.text("Total Sales: Php <?php echo number_format($total_amount, 2); ?>", 170, y, { align: "right" });
            y += 12;


            // =====================
            //   FORCE PAGE BREAK HERE
            // =====================
            pdf.addPage();
            y = 20;

            // =====================
            //  PAGE 2 — EXPENSES
            // =====================

            pdf.setFont("helvetica", "bold");
            pdf.setFontSize(16);
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
                y = checkPageBreak(y);
            <?php endforeach; ?>

            pdf.setFont("helvetica", "bold");
            pdf.text("Total Expenses: Php <?php echo number_format($total_expense, 2); ?>", 170, y, { align: "right" });
            y += 12;

            // =====================
            //  NET INCOME SUMMARY
            // =====================

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

            // =====================
            // FOOTER
            // =====================

            pdf.setFont("helvetica", "italic");
            pdf.setFontSize(10);

            pdf.text(
                "Generated by: <?php echo $user_data['firstname'] . ' ' . $user_data['lastname']; ?>",
                105,
                280,
                { align: "center" }
            );

            pdf.text(
                "Generated by AquaDrop Water Ordering System",
                105,
                290,
                { align: "center" }
            );

            // SAVE PDF
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
        <script>
        document.getElementById("downloadExcel").addEventListener("click", function () {

            // =====================
            //  SALES DATA (UPDATED)
            // =====================

            let salesData = [
                ["Date", "Product Name", "Quantity", "Unit Price (Php)", "Amount (Php)"],
                <?php foreach($order_data as $row): 

                    $unit_price = ($row['water_price_promo'] > 0)
                        ? $row['water_price_promo']
                        : $row['water_price'];

                    $line_total = $unit_price * $row['quantity'];
                ?>
                [
                    "<?php echo date('M d, Y g:iA', strtotime($row['date'])); ?>",
                    "<?php echo $row['product_name']; ?>",
                    "<?php echo $row['quantity']; ?>",
                    "Php <?php echo number_format($unit_price, 2); ?>",
                    "Php <?php echo number_format($line_total, 2); ?>"
                ],
                <?php endforeach; ?>

                ["", "", "", "Total Sales", "Php <?php echo number_format($total_amount, 2); ?>"]
            ];

            // =====================
            // EXPENSE DATA
            // =====================

            let expenseData = [
                ["Date", "Purpose", "Amount (Php)"],
                <?php foreach($expense_data as $row): ?>
                [
                    "<?php echo date('M d, Y g:iA', strtotime($row['date'])); ?>",
                    "<?php echo $row['expensetype_name']; ?>",
                    "Php <?php echo number_format($row['amount'], 2); ?>"
                ],
                <?php endforeach; ?>
                ["", "Total Expenses", "Php <?php echo number_format($total_expense, 2); ?>"]
            ];

            // =====================
            // SUMMARY
            // =====================

            let summaryData = [
                ["Summary", "Amount"],
                ["Total Sales", "Php <?php echo number_format($total_amount, 2); ?>"],
                ["Total Expenses", "Php <?php echo number_format($total_expense, 2); ?>"],
                ["Net Income", "Php <?php echo number_format($total_income, 2); ?>"]
            ];

            // =====================
            // CREATE WORKBOOK
            // =====================

            const wb = XLSX.utils.book_new();

            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(salesData), "Sales");
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expenseData), "Expenses");
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(summaryData), "Summary");

            const filename = `Report_<?php echo date('Ymd', strtotime($date_data)); ?>.xlsx`;
            XLSX.writeFile(wb, filename);

        });
        </script>

    </body>
</html>
