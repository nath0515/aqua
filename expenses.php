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
    $start_date_val = $_GET['start_date'] ?? '';
    $end_date_val = $_GET['end_date'] ?? '';
    $filter_range_val = $_GET['filter_range'] ?? '';

    // Step 1: Handle quick filter first (this sets start/end values)
    if ($filter_range_val !== '') {
        switch ($filter_range_val) {
            case 'today':
                $start_date_val = $end_date_val = date('Y-m-d');
                break;
            case 'week':
                $start_date_val = date('Y-m-d', strtotime('monday this week'));
                $end_date_val = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'month':
                $start_date_val = date('Y-m-01');
                $end_date_val = date('Y-m-t');
                break;
            case 'year':
                $start_date_val = date('Y-01-01');
                $end_date_val = date('Y-12-31');
                break;
        }
    }

    // Step 2: If start & end dates are valid, run filter
    if (validateDate($start_date_val) && validateDate($end_date_val)) {
        $start_datetime = $start_date_val . ' 00:00:00';
        $end_datetime = $end_date_val . ' 23:59:59';

        $sql = "SELECT date, expensetype_name, comment, amount 
                FROM expense e1 
                JOIN expensetype e2 ON e1.expensetype_id = e2.expensetype_id 
                WHERE date BETWEEN :start_date AND :end_date
                ORDER BY date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start_date', $start_datetime, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $end_datetime, PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Default: show all
        $sql = "SELECT date, expensetype_name, comment, amount 
                FROM expense e1 
                JOIN expensetype e2 ON e1.expensetype_id = e2.expensetype_id
                ORDER BY date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

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
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown" style="min-width: 250px;">
                        <li class="dropdown-header fw-bold text-dark">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php echo renderNotificationDropdown($notifications['recent_notifications'], $unread_count, $user_id, $role_id); ?>
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
                                    <a class="nav-link" href="addstaff.php">Add Staff</a>
                                    <a class="nav-link" href="applications.php">Applications</a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Expenses</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Analytics</li>
                            <li class="breadcrumb-item active">Expenses</li>
                        </ol>
                        <div class="action-buttons">
                            <button class="btn btn-modern" data-bs-toggle="modal" data-bs-target="#addexpense">
                                <i class="fa fa-plus me-2"></i> Add Expense
                            </button>
                            <button class="btn btn-success-modern" data-bs-toggle="modal" data-bs-target="#addexpensetype">
                                <i class="fa fa-plus me-2"></i>
                                Add Expense Type 
                            </button>
                        </div>
                        <form id="filterForm" action="expenses.php" method="GET">
                            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
                                <!-- Manual date filters -->
                                <div>
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date_val) ?>" class="form-control">
                                </div>
                                <div>
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date_val) ?>" class="form-control">
                                </div>

                                <!-- Submit button -->
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
                                            echo $labels[$filter_range_val] ?? 'Select Range';
                                            ?>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php
                                            $ranges = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
                                            foreach ($ranges as $key => $label): ?>
                                                <li>
                                                    <a class="dropdown-item <?= ($filter_range_val === $key) ? 'active bg-primary text-white' : '' ?>" href="#" data-value="<?= $key ?>">
                                                        <?= $label ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>

                                        <!-- Hidden input for dropdown value -->
                                        <input type="hidden" name="filter_range" id="filter_range_input" value="<?= htmlspecialchars($filter_range_val) ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label d-block">&nbsp;</label>
                                    <a href="expenses.php" class="btn btn-outline-danger">Clear Filters</a>
                                </div>
                            </div>
                        </form>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Expenses
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Expenses</th>
                                            <th>Comment</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($data as $row):?>
                                        <tr>
                                            <td><?php echo date("F j, Y - h:iA", strtotime($row['date'])); ?></td>
                                            <td><?php echo $row['expensetype_name']; ?></td>
                                            <td><?php echo $row['comment']; ?></td>
                                            <td>₱ <?php echo number_format($row['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
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
        <div class="modal fade" id="addexpense" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"><i class="bi bi-plus-circle"></i> Add Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="addexpense.php" method="POST" enctype="multipart/form-data" id="addExpenseForm">
                        <!-- Modal Body -->
                        <div class="modal-body">
                                <!-- Status -->
                            <div class="mb-3">
                                <label for="stock" class="form-label">Expense</label>
                                <div class="input-group">
                                    <?php 
                                        $sql = "SELECT * FROM expensetype";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute();
                                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                <select class="form-select" name="expensetype_id" required>
                                    <option value="">Select Expense</option>
                                    <?php foreach($data as $row):?>
                                        <option value="<?php echo $row['expensetype_id']?>"><?php echo $row['expensetype_name']?></option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="commet" class="form-label">Comment</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                    <input type="text" name="comment" class="form-control" placeholder="Comment" required>
                                </div>
                            </div><!-- -->
                           <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text pe-3">₱</span>
                                    <input type="number"
                                        id="amount"
                                        name="amount"
                                        step="0.01"
                                        min="0"
                                        max="100000"
                                        class="form-control"
                                        placeholder="Amount"
                                        required>
                                </div>
                            </div>
                            <div class="mb-3">
                            <input type="date" name="startDate" id="startDate" class="form-control" 
                                value="<?php echo date('Y-m-d'); ?>">

                            <input type="time" name="startTime" id="startTime" class="form-control" 
                                value="<?php echo date('H:i'); ?>">
                            </div>
                            <div class="mb-3" id="orderItemsContainer">
                            </div>   
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Add Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Order Modal -->
        <div class="modal fade" id="addexpensetype" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"><i class="bi bi-plus-circle"></i> Add Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="addexpensetype.php" method="POST" enctype="multipart/form-data">
                        <!-- Modal Body -->
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="commet" class="form-label">Expense Type Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                    <input type="text" name="expensetype" class="form-control" placeholder="Expense Type" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Add Expense</button>
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
        <?php if (isset($_GET['status'])): ?>
        <script>
            <?php if ($_GET['status'] === 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Expense Added',
                    text: 'Your expense was successfully recorded!',
                    confirmButtonColor: '#0077b6'
                });
            <?php elseif ($_GET['status'] === 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Add Expense',
                    text: 'There was an error while saving the expense. Please try again.',
                    confirmButtonColor: '#d33'
                });
            <?php endif; ?>
        </script>
        <?php endif; ?>
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
        </script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const amountInput = document.getElementById('amount');

            // Format on blur (after user finishes typing)
            amountInput.addEventListener('blur', function () {
                let value = parseFloat(amountInput.value);
                if (isNaN(value)) {
                    amountInput.value = '';
                    return;
                }
                if (value > 100000) {
                    value = 100000;
                }
                amountInput.value = value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });

            // Remove formatting on focus for easy editing
            amountInput.addEventListener('focus', function () {
                let value = amountInput.value.replace(/,/g, '');
                amountInput.value = value;
            });

            // Prevent manual input greater than max
            amountInput.addEventListener('input', function () {
                let value = parseFloat(amountInput.value);
                if (!isNaN(value) && value > 100000) {
                    amountInput.value = '100000';
                }
            });

            // On form submit, convert formatted value back to plain number
            document.getElementById("addExpenseForm").addEventListener('submit', function (e) {
                let value = amountInput.value.replace(/,/g, '');
                let floatVal = parseFloat(value);
                if (isNaN(floatVal) || floatVal > 100000) {
                    e.preventDefault();
                    alert('Please enter a valid amount not exceeding ₱100,000.');
                    amountInput.focus();
                    return false;
                }
                amountInput.value = floatVal.toFixed(2);
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
