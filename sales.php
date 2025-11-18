<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    $filter_range = $_GET['filter_range'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if ($filter_range) {
        // Determine dates based on quick filter
        $today = date('Y-m-d');
        
        switch ($filter_range) {
            case 'today':
                $start_date = $today;
                $end_date = $today;
                break;
            case 'week':
                $start_date = date('Y-m-d', strtotime('monday this week'));
                $end_date = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'month':
                $start_date = date('Y-m-01');
                $end_date = date('Y-m-t');
                break;
            case 'year':
                $start_date = date('Y-01-01');
                $end_date = date('Y-12-31');
                break;
        }
    }

    $sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.address, b.contact_number, c.status_name, CONCAT(r.firstname, ' ', r.lastname) as rider FROM orders a
    JOIN user_details b ON a.user_id = b.user_id
    LEFT JOIN user_details r ON a.rider = r.user_id
    JOIN orderstatus c ON a.status_id = c.status_id WHERE a.status_id IN (4, 5)";

    $params = [];

    if ($start_date && $end_date) {
        $start_date_time = $start_date . ' 00:00:00';
        $end_date_time = $end_date . ' 23:59:59';
        
        $sql .= " AND a.date BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date_time;
        $params[':end_date'] = $end_date_time;
    }
    
    $sql .= " ORDER BY a.date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total amount for filtered data
    $total_amount = 0;
    $total_orders = count($order_data);
    foreach ($order_data as $order) {
        $total_amount += floatval($order['amount']);
    }

    $sql = "SELECT * FROM orderstatus";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE read_status = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = $unread_result['unread_count'];

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
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 170px; height: 60px;">
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
                                    <a class="nav-link <?php echo $current_page == 'addstaff.php' ? 'active' : ''; ?>" href="addstaff.php">Add Staff</a>
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
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Sales</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Analytics</li>
                            <li class="breadcrumb-item active">Sales</li>
                        </ol>
                        <form id="filterForm" action="sales.php" method="GET">
                            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
                                <div>
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($start_date ?? ''); ?>" required>
                                </div>
                                <div>
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($end_date ?? ''); ?>" required>
                                </div>
                                <div>
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary" onclick="return validateDateRange()">Filter</button>
                                </div>
                                <!-- Quick filter dropdown -->
                                <div id="quickFilterDropdown">
                                    <label class="form-label d-block">Quick Filter</label>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <?php
                                            $labels = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
                                            echo $labels[$filter_range] ?? 'Select Range';
                                            ?>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php
                                            $ranges = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
                                            foreach ($ranges as $key => $label): ?>
                                                <li>
                                                    <a class="dropdown-item <?= ($filter_range === $key) ? 'active bg-primary text-white' : '' ?>" href="#" data-value="<?= $key ?>">
                                                        <?= $label ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>

                                        <!-- Hidden input for dropdown value -->
                                        <input type="hidden" name="filter_range" id="filter_range_input" value="<?= htmlspecialchars($filter_range) ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label d-block">&nbsp;</label>
                                    <a href="sales.php" class="btn btn-outline-danger">Clear Filters</a>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Error Message -->
                        <div id="dateError" class="alert alert-danger" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Invalid Date Range:</strong> Start date cannot be after end date. Please select a valid date range.
                        </div>
                        
                        <!-- Total Summary -->
                        <?php if ($total_orders > 0): ?>
                        <div class="alert alert-info mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Total Orders:</strong> <?php echo $total_orders; ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Amount:</strong> ₱<?php echo number_format($total_amount, 2); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Date Range:</strong> 
                                    <?php 
                                    if ($start_date && $end_date) {
                                        echo date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                                    } else {
                                        echo 'All Time';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Sales
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Order ID:#</th>
                                            <th>Date</th>
                                            <th>Amount (₱)</th>
                                            <th>Full Name</th>
                                            <th>Contact #</th>
                                            <th>Address</th>
                                            <th>Status</th>
                                            <th>Rider</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_data as $row):?>
                                            <tr>
                                                <td><?php echo $row['order_id'];?></td>
                                                <td><?php echo date("F j, Y - h:iA", strtotime($row['date'])); ?></td>
                                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                                <td><?php echo "".$row['firstname']." ".$row['lastname'];?></td>
                                                <td><?php echo $row['contact_number'];?></td>
                                                <td><?php echo $row['address'];?></td>
                                                <td><div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                                                    <?php
                                                        $status = htmlspecialchars($row['status_name']);
                                                        $badgeClass = 'bg-secondary'; // Default for unknown status

                                                        // Assign colors to each status
                                                        if ($status === 'Pending') {
                                                            $badgeClass = 'bg-light'; // Light grey for pending
                                                        } elseif ($status === 'Accepted') {
                                                            $badgeClass = 'bg-primary'; // Blue for accepted
                                                        } elseif ($status === 'Delivering') {
                                                            $badgeClass = 'bg-warning text-dark'; // Yellow with dark text for delivering
                                                        } elseif ($status === 'Delivered') {
                                                            $badgeClass = 'bg-success'; // Green for delivered
                                                        } elseif ($status === 'Completed') {
                                                            $badgeClass = 'bg-info'; // Light blue for completed
                                                        } elseif ($status === 'Cancel') {
                                                            $badgeClass = 'bg-danger'; // Red for cancelled
                                                        }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= $status ?>
                                                    </span>
                                                </div>
                                                <td><?php echo !empty($row['rider']) ? $row['rider'] : 'Unassigned';?></td>
                                                <td>
                                                    <a href="order_details.php?id=<?php echo $row['order_id']?>" class="btn btn-outline-secondary btn-sm me-1">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>
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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
        <!-- JavaScript to handle dropdown selection -->
        <script>
        document.querySelectorAll('#quickFilterDropdown .dropdown-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const value = this.getAttribute('data-value');

                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';

                document.getElementById('filter_range_input').value = value;
                document.getElementById('filterForm').submit();
            });
        });
        
        // Real-time date validation
        document.getElementById('start_date').addEventListener('change', validateDateRange);
        document.getElementById('end_date').addEventListener('change', validateDateRange);
        
        // Date Range Validation Function
        function validateDateRange() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const errorDiv = document.getElementById('dateError');
            
            // Hide error message first
            errorDiv.style.display = 'none';
            
            // Check if both dates are selected
            if (startDate && endDate) {
                // Convert to Date objects for comparison
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                // Check if start date is after end date
                if (start > end) {
                    // Show error message
                    errorDiv.style.display = 'block';
                    
                    // Scroll to error message
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Prevent form submission
                    return false;
                }
            }
            
            // Allow form submission if validation passes
            return true;
        }
        
        // PDF Download Function
        function downloadPDF() {
            // Get current filter parameters
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const filterRange = document.getElementById('filter_range_input').value;
            
            // Debug: Log the values
            console.log('Download PDF - Start Date:', startDate);
            console.log('Download PDF - End Date:', endDate);
            console.log('Download PDF - Filter Range:', filterRange);
            
            // Create download URL with current filters
            let downloadUrl = 'download_sales_pdf.php?';
            if (startDate) downloadUrl += 'start_date=' + startDate + '&';
            if (endDate) downloadUrl += 'end_date=' + endDate + '&';
            if (filterRange) downloadUrl += 'filter_range=' + filterRange + '&';
            
            // Remove trailing & if exists
            downloadUrl = downloadUrl.replace(/&$/, '');
            
            console.log('Download URL:', downloadUrl);
            
            // Create a temporary link and trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'Sales_Report_' + new Date().toISOString().slice(0,10) + '.html';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
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
