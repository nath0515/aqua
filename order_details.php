<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

    require 'session.php';
    require 'db.php';

    

    $user_id = $_SESSION['user_id'];
    $dateNow = date('Y-m-d');
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

    $order_id = 0;

    if(isset($_GET['id'])){
        $order_id = $_GET['id'];

        $sql = "SELECT a.quantity, a.with_container,a.container_quantity, a.isDiscounted,
        b.product_name, b.water_price, b.water_price_promo, b.container_price, 
        c.date, c.amount, c.rider,
        d.firstname, d.lastname, d.address, d.contact_number,
        e.status_name
        FROM orderitems a
        JOIN products b ON a.product_id = b.product_id
        JOIN orders c ON a.order_id = c.order_id
        JOIN user_details d ON c.user_id = d.user_id
        JOIN orderstatus e ON c.status_id = e.status_id
        WHERE a.order_id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT proof_file, proofofpayment, payment_id FROM orders WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $proof_file = $stmt->fetch();

        $sql = "SELECT date FROM orders WHERE order_id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        $date_data = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 220px; height: 60px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
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
                            <li class="breadcrumb-item active"><a href="orders.php">Order</a></li>
                            <li class="breadcrumb-item active">View Orders</li>
                        </ol>
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
                        <div id="reportContent">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-table me-1"></i>
                                    Orders
                                </div>
                                <div class="card-body table-responsive">
                                    <?php 
                                    $sql = "SELECT amount FROM orders WHERE order_id = :order_id";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bindParam(':order_id', $order_id);
                                    $stmt->execute();
                                    $total_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0"><?php echo date("F j, Y - h:iA", strtotime($date_data['date'])); ?></h5>
                                        <h5 class="mb-0">Total Price: ₱ <?php echo $total_data['amount']?></h5>
                                    </div>
                                    <?php
                                    // Check if any items in the order have containers
                                    $has_containers = false;
                                    foreach($order_data as $row) {
                                        if($row['with_container'] == 1) {
                                            $has_containers = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <table class="table table-bordered p-1">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Unit Price</th>
                                                <th>Quantity</th>
                                                <?php if($has_containers): ?>
                                                    <th>Has Container</th>
                                                    <th>Container Quantity</th>
                                                    <th>Container Price</th>
                                                <?php endif; ?>
                                                <th>Total Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($order_data as $row):?>
                                                <?php
                                                // Calculate line item total
                                                $unit_price = ($row['isDiscounted'] == 1) ? $row['water_price_promo'] : $row['water_price'];
                                                $line_total = $unit_price * $row['quantity'];
                                                if($row['with_container'] == 1) {
                                                    $line_total += $row['container_price'] * $row['container_quantity'];
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['product_name'];?></td>
                                                    <td>₱<?php echo number_format($unit_price, 2);?></td>
                                                    <td><?php echo $row['quantity'];?></td>
                                                    <?php if($has_containers): ?>
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
                                                        <td>₱<?php echo number_format($row['container_price'], 2);?></td>
                                                    <?php endif; ?>
                                                    <td>₱<?php echo number_format($line_total, 2);?></td>
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
                                    
                                    <p><i id="processedBy"></i></p>
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
        <!-- View Receipt Modal -->
        <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg"> <!-- You can use modal-xl if needed -->
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">AquaDrop Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Receipt content starts here -->
                <div id="sampleReceipt">
                    <h2>AquaDrop Receipt</h2>
                    <p>Date: <?php echo date("F j, Y - h:i A", strtotime($date_data['date'])); ?></p>
                    <p>Customer: <?php echo $order_data[0]['firstname'].' '.$order_data[0]['lastname']; ?></p>
                    <p>Address: <?php echo $order_data[0]['address']; ?></p>
                    <p>Contact: <?php echo $order_data[0]['contact_number']; ?></p>

                    <table class="table table-bordered mt-3">
                        <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($order_data as $row): ?>
                            <?php
                            $unit_price = ($row['quantity'] >= 10) ? $row['water_price_promo'] : $row['water_price'];
                            $line_total = $unit_price * $row['quantity'];
                            if($row['with_container'] == 1) {
                                $line_total += $row['container_price'] * $row['container_quantity'];
                            }
                            ?>
                            <tr>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>₱<?php echo number_format($unit_price, 2); ?></td>
                                <td>₱<?php echo number_format($line_total, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><strong>Total: ₱<?php echo $total_data['amount']; ?></strong></p>
                    <p><em>Thank you for choosing AquaDrop!</em></p>
                </div>
                <!-- Receipt content ends here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
            </div>
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
        <script src="js/datatables-simple-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>                                        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

        <div id="receiptContent" class="d-none">
        <h2>AquaDrop Receipt</h2>
        <p>Date: <?php echo date("F j, Y - h:i A", strtotime($date_data['date'])); ?></p>
        <p>Customer: <?php echo $order_data[0]['firstname'].' '.$order_data[0]['lastname']; ?></p>
        <p>Address: <?php echo $order_data[0]['address']; ?></p>
        <p>Contact: <?php echo $order_data[0]['contact_number']; ?></p>
        
            <table>
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($order_data as $row): ?>
                    <?php
                    $unit_price = ($row['quantity'] >= 10) ? $row['water_price_promo'] : $row['water_price'];
                    $line_total = $unit_price * $row['quantity'];
                    if($row['with_container'] == 1) {
                        $line_total += $row['container_price'] * $row['container_quantity'];
                    }
                    ?>
                <tr>
                    <td><?php echo $row['product_name']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>₱<?php echo number_format($unit_price, 2); ?></td>
                    <td>₱<?php echo number_format($line_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <p><strong>Total: ₱<?php echo $total_data['amount']; ?></strong></p>
        <p><em>Thank you for choosing AquaDrop!</em></p>
        </div>

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
        <script>
            document.getElementById("downloadPDF").addEventListener("click", function () {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF();

            pdf.setFontSize(18);
            pdf.setFont("helvetica", "bold");
            pdf.text("AquaDrop Receipt", 105, 15, { align: "center" });

            pdf.setFontSize(12);
            pdf.setFont("helvetica", "normal");
            pdf.text("Date: <?php echo date("F j, Y - h:i A", strtotime($date_data['date'])); ?>", 14, 30);

            pdf.text("Customer: <?php echo $order_data[0]['firstname'].' '.$order_data[0]['lastname']; ?>", 14, 38);
            pdf.text("Address: <?php echo $order_data[0]['address']; ?>", 14, 46);
            pdf.text("Contact: <?php echo $order_data[0]['contact_number']; ?>", 14, 54);

            let startY = 70;
            pdf.setFont("helvetica", "bold");
            pdf.text("Item", 14, startY);
            pdf.text("Qty", 90, startY);
            pdf.text("Unit Price", 120, startY);
            pdf.text("Total", 160, startY);

            startY += 6;
            pdf.setFont("helvetica", "normal");

            <?php foreach($order_data as $row): ?>
            <?php
            // Calculate line item total for PDF
            $unit_price = ($row['quantity'] >= 10) ? $row['water_price_promo'] : $row['water_price'];
            $line_total = $unit_price * $row['quantity'];
            if($row['with_container'] == 1) {
                $line_total += $row['container_price'] * $row['container_quantity'];
            }
            ?>
            pdf.text("<?php echo $row['product_name']; ?>", 14, startY);
            pdf.text("<?php echo $row['quantity']; ?>", 95, startY, { align: "right" });
            pdf.text("Php <?php echo number_format($unit_price, 2); ?>", 130, startY, { align: "right" });
            pdf.text("Php <?php echo number_format($line_total, 2); ?>", 180, startY, { align: "right" });
            startY += 6;
            <?php endforeach; ?>

            startY += 6;
            pdf.setFont("helvetica", "bold");
            pdf.text("Total: Php <?php echo $total_data['amount']; ?>", 180, startY, { align: "right" });

            startY += 20;
            pdf.setFont("helvetica", "italic");
            pdf.text("Thank you for choosing AquaDrop!", 105, startY, { align: "center" });

            pdf.save(`Receipt_<?php echo date('Ymd', strtotime($date_data['date'])); ?>.pdf`);
        });
        </script>
        <script>
            document.getElementById('printReceipt').addEventListener('click', function() {
            const printContents = document.getElementById('receiptContent').innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }); 
        </script>
        <script>
        document.getElementById('viewReceipt').addEventListener('click', function () {
            var receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();
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
