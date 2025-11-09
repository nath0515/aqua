<?php 
    require 'session.php';
    require 'db.php';
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    $sql = "SELECT 
            a.id, 
            CONCAT(ud.firstname, ' ', ud.lastname) AS full_name, 
            ud.contact_number, 
            a.application_date, 
            a.valid_id,
            a.status,
            a.reason
        FROM applications a
        LEFT JOIN users u ON a.user_id = u.user_id
        LEFT JOIN user_details ud ON u.user_id = ud.user_id
        ORDER BY 
            FIELD(a.status, 'pending', 'approved', 'rejected'),
            a.application_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $alluserdata = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <title>Applications</title>
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
                        <h1 class="mt-4">Applications</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Account Manager</li>
                            <li class="breadcrumb-item active">Applications</li>
                        </ol>
                    </div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Accounts
                            </div>
                            <div class="card-body">
                                <table id="accountTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Contact Number</th>
                                            <th>Application Date</th>
                                            <th>Valid ID</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>                                   
                                        <?php foreach($alluserdata as $row):?>
                                        <tr>
                                            <td><?php echo $row['full_name']; ?></td>
                                            <td><?php echo $row['contact_number']; ?></td>
                                            <td><?php echo date('F j, Y - g:iA', strtotime($row['application_date'])); ?></td>
                                            <td><a class="btn btn-success btn-sm" target="_blank" href="<?php echo $row['valid_id']?>">View</a></td>
                                            <td>
                                                <?php
                                                    $status = strtolower($row['status']);
                                                    $badgeClass = '';

                                                    switch ($status) {
                                                        case 'approved':
                                                            $badgeClass = 'bg-success';
                                                            break;
                                                        case 'rejected':
                                                            $badgeClass = 'bg-danger';
                                                            break;
                                                        case 'pending':
                                                        default:
                                                            $badgeClass = 'bg-warning text-dark';
                                                            break;
                                                    }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                                <?php if ($status === 'rejected'): ?>
                                                    <button class="btn btn-info btn-sm ms-2" id="show-reason-btn"><i class="bi bi-info-circle"></i></button>
                                                    <script>
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        const reasonBtn = document.getElementById('show-reason-btn');
                                                        if(reasonBtn) {
                                                            reasonBtn.addEventListener('click', function() {
                                                                Swal.fire({
                                                                    title: 'Reason',
                                                                    text: <?php echo json_encode($row['reason'] ?? 'No reason provided.'); ?>,
                                                                    icon: 'info',
                                                                    confirmButtonText: 'Close'
                                                                });
                                                            });
                                                        }
                                                    });
                                                    </script>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (strtolower($row['status']) === 'pending'): ?>
                                                    <button class="btn btn-success btn-sm action-btn" data-id="<?php echo $row['id']; ?>" data-status="approved">Approve</button>
                                                    <button class="btn btn-danger btn-sm action-btn" data-id="<?php echo $row['id']; ?>" data-status="rejected">Reject</button>
                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', function () {
                                                        document.querySelectorAll('.action-btn').forEach(button => {
                                                            button.addEventListener('click', function () {
                                                                const appId = this.getAttribute('data-id');
                                                                const newStatus = this.getAttribute('data-status');

                                                                const actionText = newStatus === 'approved' ? 'approve' : 'reject';
                                                                const confirmBtnText = newStatus === 'approved' ? 'Yes, approve' : 'Yes, reject';

                                                                Swal.fire({
                                                                    title: `Confirm ${actionText}?`,
                                                                    text: `Are you sure you want to ${actionText} this application?`,
                                                                    icon: 'warning',
                                                                    showCancelButton: true,
                                                                    confirmButtonText: confirmBtnText,
                                                                    cancelButtonText: 'Cancel'
                                                                }).then((result) => {
                                                                    if (result.isConfirmed) {

                                                                        // If rejecting, prompt for reason
                                                                        if (newStatus === 'rejected') {
                                                                            Swal.fire({
                                                                                title: 'Reason for Rejection',
                                                                                input: 'text',
                                                                                inputPlaceholder: 'Enter reason...',
                                                                                inputAttributes: {
                                                                                    'aria-label': 'Reason for rejection'
                                                                                },
                                                                                showCancelButton: true,
                                                                                confirmButtonText: 'Submit',
                                                                                cancelButtonText: 'Cancel',
                                                                                inputValidator: (value) => {
                                                                                    if (!value) {
                                                                                        return 'You must provide a reason!';
                                                                                    }
                                                                                }
                                                                            }).then((reasonResult) => {
                                                                                if (reasonResult.isConfirmed) {
                                                                                    const reason = reasonResult.value;
                                                                                    sendApplicationStatus(appId, newStatus, reason);
                                                                                }
                                                                            });
                                                                        } else {
                                                                            // If approved, no reason needed
                                                                            sendApplicationStatus(appId, newStatus);
                                                                        }

                                                                    }
                                                                });
                                                            });
                                                        });

                                                        function sendApplicationStatus(appId, status, reason = '') {
                                                            const data = new URLSearchParams();
                                                            data.append('id', appId);
                                                            data.append('status', status);
                                                            if (status === 'rejected' && reason) {
                                                                data.append('reason', reason);
                                                            }

                                                            fetch('update_application_status.php', {
                                                                method: 'POST',
                                                                headers: {
                                                                    'Content-Type': 'application/x-www-form-urlencoded'
                                                                },
                                                                body: data.toString()
                                                            })
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                if (data.success) {
                                                                    Swal.fire('Success', data.message || 'Status updated.', 'success')
                                                                        .then(() => {
                                                                            location.reload();
                                                                        });
                                                                } else {
                                                                    Swal.fire('Error', data.message || 'Failed to update status.', 'error');
                                                                }
                                                            })
                                                            .catch(() => {
                                                                Swal.fire('Error', 'An unexpected error occurred.', 'error');
                                                            });
                                                        }
                                                    });

                                                    </script>
                                                <?php endif; ?>
                                            </td>
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

        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="confirmDeleteForm">
                <div class="modal-content">
                    <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <div class="mb-3">
                        <label for="adminPassword" class="form-label">Enter your password to confirm:</label>
                        <input type="password" class="form-control" id="adminPassword" name="admin_password" required>
                    </div>
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </div>
                </div>
                </form>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.querySelector('#accountTable');
                const dataTable = new simpleDatatables.DataTable(table, {
                    perPage: 0,
                    perPageSelect: [0, 5, 10, 15, 20],
                    searchable: true,
                    sortable: true,
                });
            });
        </script>
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
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Account created.',
        });
        </script>
     <?php endif; ?>
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
