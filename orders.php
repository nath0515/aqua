<?php 
    require 'session.php';
    require 'db.php';
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

    $user_id = $_SESSION['user_id'];
    $dateNow = date('Y-m-d');

    $role_id = $_SESSION['role_id'];
    if($role_id == 2){
        header("Location: home.php");
    }else if ($role_id == 3){
        header("Location: riderdashboard.php");
    }

    $filter_range = $_GET['filter_range'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if ($filter_range) {
        $today = date('Y-m-d');

        switch ($filter_range) {
            case 'today':
                $start_date = $end_date = $today;
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

    $sql = "SELECT * FROM orders a WHERE 1";
    $params = [];

    if ($start_date && $end_date) {
        if (!strtotime($start_date) || !strtotime($end_date)) {
            throw new Exception("Invalid date format");
        }

        $sql .= " AND DATE(a.date) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);



    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if(isset($_GET['from_card'])){
        $sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.address, b.contact_number, 
               c.status_name, d.firstname AS rider_firstname, d.lastname AS rider_lastname, e.payment_name
        FROM orders a
        JOIN user_details b ON a.user_id = b.user_id
        JOIN orderstatus c ON a.status_id = c.status_id
        LEFT JOIN user_details d ON a.rider = d.user_id
        JOIN payment_method e ON a.payment_id = e.payment_id
        WHERE DATE(date) = :date
        ORDER BY date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':date', $dateNow);
    }
    else{
        $sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, ul.address, tb.barangay_name, b.contact_number, 
        c.status_name, d.firstname AS rider_firstname, d.lastname AS rider_lastname, e.payment_name
    FROM orders a
    JOIN user_details b ON a.user_id = b.user_id
    JOIN orderstatus c ON a.status_id = c.status_id
    LEFT JOIN user_details d ON a.rider = d.user_id
    LEFT JOIN user_locations ul ON a.location_id = ul.location_id
    LEFT JOIN table_barangay tb ON ul.barangay_id = tb.barangay_id
    JOIN payment_method e ON a.payment_id = e.payment_id
    ORDER BY date DESC";
    $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM orderstatus";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT a.user_id, firstname, lastname FROM users a
    JOIN user_details b ON a.user_id = b.user_id 
    WHERE a.role_id = 3";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rider_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <h1 class="mt-4">Orders</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Order Management</li>
                            <li class="breadcrumb-item active">Orders</li>
                        </ol>
                        <a class="btn btn-modern mb-2" href="create_purchase.php">
                            <i class="fa fa-plus"></i>
                            Add Order
                        </a>
                        <form id="filterForm" action="orders.php" method="GET">
                            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
                                <div>
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div>
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div>
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Filter</button>
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
                                <div class="text-end mt-3">
                                    <a href="sale_promo.php" class="btn btn-primary">Promo Sales</a>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="sales_reserve.php" class="btn btn-primary">Reserved</a>
                                </div>
                            </div>
                        </form>
                        <div class="card mb-4">
                            
                        </div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Orders
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
                                            <th>Payment Method</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_data as $row):?>
                                            <tr>
                                                <td><?php echo $row['order_id'];?></td>
                                                <td><?php echo date("F j, Y - h:iA", strtotime($row['date'])); ?></td>
                                                <td>₱<?php echo $row['amount'];?></td>
                                                <td><?php echo "".$row['firstname']." ".$row['lastname'];?></td>
                                                <td><?php echo $row['contact_number'];?></td>
                                                <td><?php echo $row['barangay_name']." ".$row['address'];?></td>
                                                <td><div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                                                    <?php
                                                        $status = htmlspecialchars($row['status_name']);
                                                        $badgeClass = 'bg-secondary'; // Default for unknown status

                                                        // Assign colors to each status
                                                        if ($status === 'Pending') {
                                                            $badgeClass = 'bg-warning text-dark'; // Light grey for pending
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
                                                </td>
                                                <td>
                                                <?php
                                                    $riderFirst = $row['rider_firstname'] ?? '';
                                                    $riderLast = $row['rider_lastname'] ?? '';
                                                    echo trim("$riderFirst $riderLast") ?: 'Unassigned';
                                                ?>
                                                </td>
                                                <td><?php echo $row['payment_name'];?></td>
                                                <td>
                                                    <a href="order_details.php?id=<?php echo $row['order_id']?>" class="btn btn-outline-secondary btn-sm me-1">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <button class="btn btn-outline-primary btn-sm editOrderBtn"
                                                        data-id="<?php echo $row['order_id']; ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editorder">
                                                            <i class="bi bi-pencil"></i> Edit
                                                    </button>
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

        <!-- Edit Order Modal -->
        <div class="modal fade" id="editorder" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="process_editorder_orders.php" method="POST" enctype="multipart/form-data">
                        <!-- Modal Body -->
                        <div class="modal-body">
                                <!-- Status -->
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Status</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-exclamation-circle-fill"></i></span>
                                        <select name="status_id" id="editStatusId" class="form-select">
                                            <?php foreach($status_data as $row):?>
                                                <option value="<?php echo $row['status_id']?>"><?php echo $row['status_name']?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Rider</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-exclamation-circle-fill"></i></span>
                                        <select name="rider" id="editRiderId" class="form-select">
                                            <option value="">Select Rider</option>
                                            <?php foreach($rider_data as $row):?>
                                                <option value="<?php echo $row['user_id'];?>"><?php echo $row['firstname'];?> <?php echo $row['lastname'];?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                </div>
                                <input type="text" name="order_id" id="editOrderId" hidden>

                                <!-- Order Items -->
                                <div class="mb-3" id="orderItemsContainer">
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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        <script>
            $(document).ready(function() {
                $(".editOrderBtn").on('click', function() {
                    var orderId = $(this).data("id");

                    $.ajax({
                        url: "process_getorderdata.php",
                        type: "POST",
                        data: { order_id: orderId },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $("#editStatusId").val(response.data.status_id);
                                $("#editOrderId").val(orderId);
                                
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
        <?php if (isset($_GET['editstatus'])): ?>
            <script>
                <?php if ($_GET['editstatus'] == 'success'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Order Edited!',
                        text: 'The order has been successfully edited.',
                    }).then((result) => {
                    });
                <?php elseif ($_GET['editstatus'] == 'error'): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong while editing the order.',
                    });
                <?php endif; ?>    
            </script>
        <?php endif; ?>
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
        </script>
    </body>
</html>
