<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");
    }else if ($role_id == 2){
        header("Location: home.php");
    }

    // Fetch user details
    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch orders assigned to the logged-in rider (user_id is the rider's user_id)
    $sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.address, b.contact_number, c.status_name, a.rider FROM orders a
    JOIN user_details b ON a.user_id = b.user_id
    JOIN orderstatus c ON a.status_id = c.status_id 
    WHERE a.status_id = 3 AND a.rider = :user_id";  // Filter by rider's user_id
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);  // Bind user_id here
    $stmt->execute();
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch available order statuses
    $sql = "SELECT * FROM orderstatus WHERE status_id IN (3, 4, 6)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT status FROM store_status WHERE ss_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rider_status = $stmt->fetchColumn();
    if($rider_status == 1){
        header('Location: riderdashboard.php');
        exit();
    }

    // Get notifications using helper for consistency
    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;
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
              <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li class="dropdown-header fw-bold text-dark">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php echo renderNotificationDropdown($notifications['recent_notifications'], $unread_count, $user_id, $role_id); ?>
                        <li><a class="dropdown-item text-center text-muted small" href="activitylogsrider.php">View all notifications</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="riderprofile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <?php 
                        // Commented out Off Duty toggle
                        /*
                        $sql = "SELECT status FROM rider_status WHERE user_id = :user_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $status_rider = $row ? $row['status'] : 0;
                        */
                        ?>
                        <!-- <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="return confirmToggle(event, <?= $status_rider ?>)">
                            <?php echo ($status_rider) ? 'Off Duty' : 'On Duty'; ?>
                        </a>
                        </li> -->
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
                            <a class="nav-link" href="riderdashboard.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Delivery Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="deliveryhistory.php">Delivered History</a>
                                    <a class="nav-link" href="ridermap.php">Maps</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="rider_ratings.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-star"></i></div>
                            My Ratings
                            </a>
                                                    <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                        </a>
                            <a class="nav-link" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
                            </a>
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
                    <div class="container my-3">
                        <div class="row">
                            <!-- Rider Header Info -->
                            <div class="col-12">
                                <div class="card mb-3">
                                    <div class="card-body d-flex flex-column flex-sm-row align-items-center">
                                        <img src="assets/img/icon-192.png" class="mb-3 mb-sm-0 me-sm-3" alt="Shop Logo" style="width: 64px; height: 64px;">
                                        <div class="text-center text-sm-start">
                                            <h5 class="fw-bold mb-1">DoodsNer Water Refilling Station</h5>
                                            <p class="text-muted mb-0">Rider: <?= htmlspecialchars($user_data['firstname'] . ' ' . $user_data['lastname']) ?> (ID:#00<?= htmlspecialchars($user_data['user_id']) ?>)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned Deliveries Today -->
                            <div class="container my-3">
                                <div class="row">
                                    <div class="col-12">
                                        <?php if (!empty($order_data)): ?>
                                            <div class="card">
                                                <div class="card-header bg-primary text-white">
                                                    Assigned Deliveries (Today)
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th scope="col">Order #</th>
                                                                <th scope="col">Customer</th>
                                                                <th scope="col">Address</th>
                                                                <th scope="col">Contact</th>
                                                                <th scope="col">Amount</th>
                                                                <th scope="col">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($order_data as $row): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($row['order_id']) ?></td>
                                                                    <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                                                    <td><?= htmlspecialchars($row['address']) ?></td>
                                                                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                                                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                                                                            <?php
                                                                                $status = htmlspecialchars($row['status_name']);
                                                                                $badgeClass = 'bg-secondary'; // Default

                                                                                if ($status === 'Delivered') {
                                                                                    $badgeClass = 'bg-success';
                                                                                } elseif ($status === 'Delivering') {
                                                                                    $badgeClass = 'bg-warning text-dark';
                                                                                } elseif ($status === 'Cancelled') {
                                                                                    $badgeClass = 'bg-danger';
                                                                                }
                                                                            ?>
                                                                            <span 
                                                                                class="badge <?= $badgeClass ?>"
                                                                                title="Order status: <?= $status ?>"
                                                                            >
                                                                                <?= $status ?>
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info text-center">
                                                No assigned deliveries at the moment.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>


                            <!-- Shift Info -->
                            <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                <h5>Shift Schedule</h5>
                                <p class="mb-1">8:00 AM – 5:00 PM</p>
                                <?php
                                    $badgeClass = $status_rider ? 'bg-success' : 'bg-secondary';
                                    $statusText = $status_rider ? 'On Duty' : 'Off Duty';
                                    ?>
                                    Status: 
                                    <a class="mb-0" href="javascript:void(0);" onclick="return confirmToggle(event, <?= $status_rider ?>)">
                                        <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                    </a>
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
        <div class="modal fade" id="editorder" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
            
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="process_editorder.php" method="POST" enctype="multipart/form-data">
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
                            
                            <input type="text" name="order_id" id="editOrderId" hidden>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
            $(document).ready(function() {
                $("#editOrderBtn").on('click', function() {
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
            function confirmToggle(event, status_rider) {
                // Prevent the default action of the link (i.e., don't actually navigate to another page)
                event.preventDefault();
                
                // Show SweetAlert2 confirmation dialog based on the current status
                if (status_rider === 1) {
                    // If the user is currently On Duty, confirm Clocking Out
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to Clock Out?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Clock Out',
                        cancelButtonText: 'No, Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Proceed with the Clock Out (submit the form, or send a request, etc.)
                            window.location.href = "attendance_toggle.php?status=0";
                        }
                    });
                } else {
                    // If the user is currently Off Duty, confirm Clocking In
                    // Check if the user has already clocked out today by sending an AJAX request

                    // AJAX call to check if clock-in is allowed
                    fetch('check_clock_in.php')
                        .then(response => response.text())
                        .then(data => {
                            if (data === 'ClockInNotAllowed') {
                                // Show SweetAlert2 message if clock-in is not allowed
                                Swal.fire({
                                    title: 'Cannot Clock In',
                                    text: "You have already clocked out today and cannot clock in again.",
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                // If clock-in is allowed, confirm Clocking In
                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: "Do you want to Clock In?",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes, Clock In',
                                    cancelButtonText: 'No, Cancel',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Proceed with the Clock In (submit the form, or send a request, etc.)
                                        window.location.href = "attendance_toggle.php?status=1";
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }

                return false;  // Prevent default action (i.e., preventing the link click from doing anything)
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

