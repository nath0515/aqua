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

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fetch notifications for rider (delivery assignments + ratings)
    $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE (destination LIKE 'rider_orderdetails.php%' OR destination = 'rider_ratings.php') AND read_status = 0";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->execute();
    $unread_count = $notification_stmt->fetchColumn();

    $recent_notifications_sql = "SELECT * FROM activity_logs WHERE (destination LIKE 'rider_orderdetails.php%' OR destination = 'rider_ratings.php') ORDER BY date DESC LIMIT 3";
    $recent_notifications_stmt = $conn->prepare($recent_notifications_sql);
    $recent_notifications_stmt->execute();
    $recent_notifications = $recent_notifications_stmt->fetchAll();
    $order_id = 0;

    if(isset($_GET['id'])){
        $order_id = $_GET['id'];

        $sql = "SELECT a.quantity, a.with_container,a.container_quantity,
        b.product_name, b.water_price, b.water_price_promo, b.container_price, 
        c.date, c.amount, c.rider, c.location_id,
        d.firstname, d.lastname, d.contact_number,
        e.status_name,
        ul.label, ul.address, ul.latitude, ul.longitude
        FROM orderitems a
        JOIN products b ON a.product_id = b.product_id
        JOIN orders c ON a.order_id = c.order_id
        JOIN user_details d ON c.user_id = d.user_id
        LEFT JOIN user_locations ul ON c.location_id = ul.location_id
        JOIN orderstatus e ON c.status_id = e.status_id
        WHERE a.order_id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT proof_file, proofofpayment, payment_id FROM orders WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $proof_file = $stmt->fetch();
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
                <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
            <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unread_count; ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <?php if (empty($recent_notifications)): ?>
                            <li><a class="dropdown-item text-muted" href="#">No notifications</a></li>
                        <?php else: ?>
                            <?php foreach($recent_notifications as $notification): ?>
                                <li><a class="dropdown-item" href="process_readnotification.php?id=<?php echo $notification['activitylogs_id']?>&destination=<?php echo $notification['destination']?>"><?php echo $notification['message'];?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                            <a class="nav-link" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
                            </a>
                            <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                        </a>
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
                            <li class="breadcrumb-item active"><a href="orders.php">Order</a></li>
                            <li class="breadcrumb-item active">View Orders</li>
                        </ol>
                        <!-- Order ID Header -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0">
                                        <i class="fas fa-receipt me-2"></i>
                                        Order #<?php echo $_GET['id']; ?>
                                    </h4>
                                    <span class="badge bg-light text-dark fs-6">
                                        <?php echo $order_data[0]['status_name'] ?? ''; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Order Details
                            </div>
                            <div class="card-body table-responsive">
                                <?php 
                                $sql = "SELECT amount FROM orders WHERE order_id = :order_id";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':order_id', $order_id);
                                $stmt->execute();
                                $total_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                
                                <!-- Order Summary -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">Order Information</h6>
                                        <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($order_data[0]['date'])); ?></p>
                                        <p class="mb-1"><strong>Customer:</strong> <?php echo $order_data[0]['firstname'] . ' ' . $order_data[0]['lastname']; ?></p>
                                        <p class="mb-1"><strong>Contact:</strong> <?php echo $order_data[0]['contact_number']; ?></p>
                                        <p class="mb-1"><strong>Address:</strong> 
                                            <?php if (!empty($order_data[0]['label'])): ?>
                                                <strong><?php echo htmlspecialchars($order_data[0]['label']); ?></strong><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($order_data[0]['address']); ?>
                                            <?php if (!empty($order_data[0]['latitude']) && !empty($order_data[0]['longitude'])): ?>
                                                <br><small class="text-muted">📍 Coordinates: <?php echo $order_data[0]['latitude']; ?>, <?php echo $order_data[0]['longitude']; ?></small>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h6 class="text-muted mb-2">Order Summary</h6>
                                        <p class="mb-1"><strong>Total Items:</strong> <?php echo count($order_data); ?></p>
                                        <p class="mb-1"><strong>Order Status:</strong> 
                                            <span class="badge <?php echo ($order_data[0]['status_name'] === 'Delivered' || $order_data[0]['status_name'] === 'Completed') ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $order_data[0]['status_name']; ?>
                                            </span>
                                        </p>
                                        <h5 class="text-primary mb-0">Total Amount: ₱<?php echo number_format($total_data['amount'], 2); ?></h5>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php if ($order_data[0]['status_name'] !== 'Delivered' && $order_data[0]['status_name'] !== 'Completed'): ?>
                                                <button type="button" class="btn btn-success" id="markDeliveredBtn">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    Mark as Delivered
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                <table class="table table-bordered p-1">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Unit Price</th>
                                            <th>Quantity</th>
                                            <th>Has Container</th>
                                            <th>Container Quantity</th>
                                            <th>Container Price</th>
                                            <th>Total Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_data as $row):?>
                                            <tr>
                                                <td><?php echo $row['product_name'];?></td>
                                                <td>₱
                                                    <?php 
                                                        if($row['quantity'] >= 10){
                                                            echo $row['water_price_promo'];
                                                        }
                                                        else{
                                                            echo $row['water_price'];
                                                        }
                                                
                                                    ?>
                                                </td>
                                                <td><?php echo $row['quantity'];?></td>
                                                <td>
                                                    <?php 
                                                        if($row['with_container'] == 1){
                                                            echo 'Yes';
                                                        }
                                                        else{
                                                            echo 'No';
                                                        }
                                                        
                                                    ?>
                                                </td>
                                                <td><?php echo $row['container_quantity'];?></td>
                                                <td>₱<?php echo $row['container_price'];?></td>
                                                <td>₱<?php echo $row['amount'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>
                                <div class="row">
                                    <?php if($proof_file['proof_file']):?>
                                        <div class="col">
                                            <div class="text-center mb-3">
                                                <?php if (!empty($proof_file['proof_file']) && file_exists($proof_file['proof_file'])): ?>
                                                    <img src="<?php echo $proof_file['proof_file'] ?>" alt="Order Image" style="max-width: 200px; min-height:400px;">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center" style="max-width: 200px; min-height:400px; background-color: #f8f9fa; border-radius: 8px;">
                                                        <i class="fas fa-image text-primary" style="font-size: 60px;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <p class="mt-2 mb-0 text-muted">Proof of Delivery</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($proof_file['payment_id'] == 2 && $proof_file['proofofpayment']):?>
                                        <div class="col">
                                            <div class="text-center mb-3">
                                                <?php if (!empty($proof_file['proofofpayment']) && file_exists($proof_file['proofofpayment'])): ?>
                                                    <img src="<?php echo $proof_file['proofofpayment'] ?>" alt="Order Image" style="max-width: 200px; min-height:400px;">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center" style="max-width: 200px; min-height:400px; background-color: #f8f9fa; border-radius: 8px;">
                                                        <i class="fas fa-credit-card text-primary" style="font-size: 60px;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <p class="mt-2 mb-0 text-muted">Proof of Payment</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
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



        <!-- Mark as Delivered Modal -->
        <div class="modal fade" id="markDeliveredModal" tabindex="-1" aria-labelledby="markDeliveredModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="markDeliveredModalLabel">
                            <i class="fas fa-check-circle me-2"></i>
                            Mark Order as Delivered
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="deliveryForm" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Order #<?php echo $order_id; ?></strong> - Please upload proof of delivery to mark this order as delivered.
                            </div>
                            
                            <div class="mb-3">
                                <label for="proof_of_delivery" class="form-label">
                                    <i class="fas fa-camera me-2"></i>
                                    Proof of Delivery Image
                                </label>
                                <input type="file" class="form-control" id="proof_of_delivery" name="proof_of_delivery" 
                                       accept="image/*" required>
                                <div class="form-text">Upload a photo showing the delivery was completed (e.g., customer signature, delivered items, etc.)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery_notes" class="form-label">
                                    <i class="fas fa-sticky-note me-2"></i>
                                    Delivery Notes (Optional)
                                </label>
                                <textarea class="form-control" id="delivery_notes" name="delivery_notes" rows="3" 
                                          placeholder="Any additional notes about the delivery..."></textarea>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Once confirmed, this order will be marked as delivered and cannot be undone.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="confirmDeliveryBtn">
                            <i class="fas fa-check me-2"></i>
                            Confirm Delivery
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Mark as Delivered functionality
            $(document).ready(function() {
                // Show modal when "Mark as Delivered" button is clicked
                $("#markDeliveredBtn").click(function() {
                    $("#markDeliveredModal").modal('show');
                });
                
                // Handle delivery confirmation
                $("#confirmDeliveryBtn").click(function() {
                    const form = document.getElementById('deliveryForm');
                    const formData = new FormData(form);
                    
                    // Validate file upload
                    const fileInput = document.getElementById('proof_of_delivery');
                    if (!fileInput.files[0]) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Proof of Delivery Required',
                            text: 'Please upload a proof of delivery image.',
                            confirmButtonColor: '#dc3545'
                        });
                        return;
                    }
                    
                    // Show loading
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                    
                    $.ajax({
                        url: 'process_mark_delivered.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log('Raw response:', response);
                            try {
                                const result = JSON.parse(response);
                                console.log('Parsed result:', result);
                                if (result.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Delivery Confirmed!',
                                        text: 'Order has been marked as delivered successfully.',
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        // Reload page to show updated status
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: result.message || 'Failed to mark order as delivered.',
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                console.error('Response that failed to parse:', response);
                                // If JSON parsing fails but the delivery actually worked, just reload
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Delivery Confirmed!',
                                    text: 'Order has been marked as delivered successfully.',
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Network Error',
                                text: 'Failed to connect to server. Please try again.',
                                confirmButtonColor: '#dc3545'
                            });
                        },
                        complete: function() {
                            $("#confirmDeliveryBtn").prop('disabled', false).html('<i class="fas fa-check me-2"></i>Confirm Delivery');
                        }
                    });
                });
            });
        </script>
    </body>
</html>
