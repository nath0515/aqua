<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");
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
    
    // Get all customer notifications (not just recent 5)
    $sql = "SELECT activitylogs_id, message, date, destination, read_status FROM activity_logs WHERE user_id = :user_id ORDER BY date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

     $logsPerPage = 5; // Change this to however many logs you want per page
    $totalLogs = count($logs); // Total logs fetched
    $totalPages = ceil($totalLogs / $logsPerPage);

    // Get current page from query parameter, default to 1
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $currentPage = max(1, min($totalPages, $currentPage)); // Ensure within bounds

    // Calculate the index to slice the logs array
    $startIndex = ($currentPage - 1) * $logsPerPage;
    $logsToShow = array_slice($logs, $startIndex, $logsPerPage);

    $range = 1; // pages before and after current page
    $startPage = max(1, $currentPage - $range);
    $endPage = min($totalPages, $currentPage + $range);
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
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 170px; height: 60px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                <li class="nav-item me-2">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                </li>
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
                        <li><a class="dropdown-item" href="userprofile.php">Profile</a></li>

                        <?php 
                    $sql = "SELECT rs FROM users WHERE user_id = :user_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $rs = $stmt->fetchColumn();
                    if($rs == 0):?>
                        <li><a class="dropdown-item" id="apply-reseller">Apply as Reseller</a></li>
                        <script>

                            document.addEventListener('DOMContentLoaded', function () {
                            const applyBtn = document.getElementById('apply-reseller');

                            if (!applyBtn) return;

                            applyBtn.addEventListener('click', function (e) {
                                e.preventDefault();

                                fetch('check_application_status.php', {
                                    method: 'GET',
                                    credentials: 'same-origin'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        Swal.fire('Error', data.error, 'error');
                                        return;
                                    }

                                    if (data.exists) {
                                        switch (data.status.toLowerCase()) {
                                            case 'pending':
                                                Swal.fire('Application Pending', 'You already have a pending reseller application under review.', 'info');
                                                break;
                                            case 'rejected':
                                                Swal.fire({
                                                    title: 'Application Rejected',
                                                    html: 'Unfortunately, your reseller application was not approved.' +
                                                        (data.reason ? '<br><strong>Reason:</strong> ' + data.reason : ''),
                                                    icon: 'warning'
                                                });
                                                break;
                                            case 'approved':
                                                Swal.fire('Already Approved', 'You are already a reseller.', 'success');
                                                break;
                                            default:
                                                Swal.fire('Notice', 'Your application status: ' + data.status, 'info');
                                        }
                                    } else {
                                        Swal.fire({
                                            title: 'Apply as Reseller',
                                            text: 'Are you sure you want to submit a reseller application? Our team will review it promptly.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonText: 'Yes, apply now',
                                            cancelButtonText: 'Cancel'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Show second Swal with file upload
                                                Swal.fire({
                                                    title: 'Upload Valid ID',
                                                    text: 'Please upload a clear image of a valid government-issued ID.',
                                                    input: 'file',
                                                    inputAttributes: {
                                                        accept: 'image/*',
                                                        'aria-label': 'Upload your ID'
                                                    },
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Submit Application',
                                                    cancelButtonText: 'Cancel'
                                                }).then((uploadResult) => {
                                                    if (uploadResult.isConfirmed) {
                                                        const file = uploadResult.value;

                                                        if (!file) {
                                                            Swal.fire('Error', 'No file selected. Please try again.', 'error');
                                                            return;
                                                        }

                                                        const formData = new FormData();
                                                        formData.append('id_image', file);

                                                        fetch('apply.php', {
                                                            method: 'POST',
                                                            body: formData
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success) {
                                                                Swal.fire('Success', 'Your reseller application has been submitted successfully.', 'success');
                                                            } else {
                                                                Swal.fire('Error', data.message || 'Failed to submit application.', 'error');
                                                            }
                                                        })
                                                        .catch(() => {
                                                            Swal.fire('Error', 'An unexpected error occurred while submitting your application.', 'error');
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire('Error', 'Failed to check your application status. Please try again later.', 'error');
                                });
                            });
                        });

                        </script>
                <?php endif; ?>
                        <li><a class="dropdown-item" href="addresses.php">My Addresses</a></li>
                        <li><a class="dropdown-item" href="activitylogsuser.php">Activity Log</a></li>
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
                        <div class="sb-sidenav-menu">
                            <div class="nav">
                                <div class="sb-sidenav-menu-heading">Menu</div>
                                <a class="nav-link" href="home.php">
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
                                        <a class="nav-link" href="costumerorder.php">Order</a>
                                        <a class="nav-link" href="orderhistory.php">Order History</a>
                                    </nav>
                                </div>
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
                    <div class="card card-body card-dark bg-primary bubble-shadow mb-4 mt-4 animated fadeInDown m-5 ">
                        <h1 class="m-2 text-white"><i class="far fa-bell"></i> &nbsp; Activity Logs</h1>
                    </div>
                    <div class="card card-body mb-4 animated fadeInUp m-5">
                    <?php foreach($logsToShow as $row): ?>
                        <a class="text-dark text-decoration-none" href="process_readnotification.php?id=<?php echo $row['activitylogs_id']?>&destination=<?php echo $row['destination']?>">
                            <div class="mx-4 mb-3 p-3 <?php if ($row['read_status'] == 0) echo 'bg-light border rounded shadow-sm'; ?>">
                                <?php echo $row['message']; ?>
                                <br>
                                <small class="text-muted">
                                    <i><?php echo date('g:i a F j', strtotime($row['date'])); ?></i>
                                </small>
                            </div>
                        </a> 
                    <?php endforeach; ?> 
                </div>

                <!-- Limited Pagination -->
                <div class="d-flex justify-content-center mb-5">
                    <nav>
                        <ul class="pagination">
                            <?php if($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php if($startPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                <?php if($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php if($i == $currentPage) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if($endPage < $totalPages): ?>
                                <?php if($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
                            <?php endif; ?>

                            <?php if($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
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

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                                $("#editOrderId").val(response.data.order_id);

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
