<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");  
    }else if ($role_id == 3){
        header("Location: riderdashboard.php");
    }

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname,longitude,latitude, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fetch notifications for customer
    // Get notifications using helper for consistency
    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;


    $sql = "SELECT * FROM products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT a.cart_id, a.product_id, b.product_name, b.product_photo, a.with_container, b.water_price, b.water_price_promo, b.container_price, a.quantity, a.container_quantity 
    FROM cart a 
    JOIN products b ON a.product_id = b.product_id 
    WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total items in cart (only product quantity, not containers)
    $cart_count = 0;
    foreach ($cart_data as $item) {
        $cart_count += $item['quantity'];
    }

    $sql = "SELECT * FROM payment_method";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $payment_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM user_locations WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $user_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT gcash FROM store_status WHERE ss_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $gcash = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Stock</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

        <style>
        .btn-delete:hover {
            color: #fff !important;
            background-color: #dc3545 !important; /* Bootstrap's 'danger' red */
            border-radius: 5px;
            transition: 0.3s ease;
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
                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cart_count; ?>
                            <span class="visually-hidden">items in cart</span>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li class="dropdown-header fw-bold text-dark">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php echo renderNotificationDropdown($notifications['recent_notifications'], $unread_count, $user_id, $role_id); ?>
                        <li><a class="dropdown-item text-center text-muted small" href="activitylogsuser.php">View all notifications</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <li><a class="dropdown-item" href="addresses.php">Addresses</a></li>
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
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        <?php echo "".$user_data['firstname']." ".$user_data['lastname'];?>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container py-5">
                        <div class="card shadow-lg">
                            <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">🛒 Your Shopping Cart</h5>
                            </div>

                            <div class="card-body">
                            <!-- Product Card -->
                            <?php foreach($cart_data as $row):?>
                            <div class="card mb-3 shadow-sm product-item">
                                <div class="card-body d-flex align-items-center">
                                    <input class="form-check-input me-3 product-checkbox" type="checkbox" 
                                    data-cart-id="<?php echo $row['cart_id']; ?>"
                                    data-id="<?php echo $row['product_id']; ?>" 
                                    data-quantity="<?php echo $row['quantity']; ?>"
                                    data-with-container="<?php echo $row['with_container'];?>"
                                    data-container-quantity="<?php echo $row['container_quantity'];?>"
                                    >

                                    <?php if (!empty($row['product_photo']) && file_exists($row['product_photo'])): ?>
                                        <img src="<?php echo $row['product_photo'];?>" class="me-3" alt="Product" style="width: 80px; height: auto;">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center me-3" style="width: 80px; height: 80px; background-color: #f8f9fa; border-radius: 8px;">
                                            <i class="fas fa-water text-primary" style="font-size: 30px;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo $row['product_name'];?></h6>
                                    <p class="mb-1 text-muted small">With Container: <?php if($row['with_container'] == 0){echo 'None';}else{echo $row['container_quantity'];}?></p>
                                    </div>
                                    <?php
                                        $quantity = $row['quantity'];
                                        $with_container = $row['with_container'];
                                        $container_quantity = $row['container_quantity'];
                                        $price = $quantity >= 10 
                                        ? $quantity * $row['water_price_promo'] 
                                        : $quantity * $row['water_price'];
                                    
                                    if ($with_container == 1) {
                                        $price += $container_quantity * $row['container_price'];
                                    }
                                    ?>
                                    <div class="text-end me-3">
                                    <p class="mb-2 fw-bold price" data-price="<?php echo $price;?>">₱<?php echo $price;?></p>
                                    <div class="input-group input-group-sm w-auto">

                                        <p class="mb-1 text-muted small">Quantity: <?php echo $quantity; ?></p>
                                    </div>
                                    </div>

                                    <div>
                                    <button class="btn btn-link text-danger btn-sm btn-delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="card-footer d-flex justify-content-between align-items-center bg-white border-top">
                                <div>
                                    <input class="form-check-input me-2" type="checkbox" id="select-all">
                                    <label for="select-all" class="mb-0">Select All</label>
                                </div>
                                <div class="row align-items-end">
                                    <!-- Payment Method -->
                                    <div class="col-md-6">
                                        <div class="form-group form-group-default">
                                            <label>Address</label>
                                            <select name="location_id" id="location_id" class="form-select">
                                                <option value="0">Select Address</option>
                                                <?php foreach($user_locations as $row): ?>
                                                    <option value="<?php echo $row['location_id']?>"><?php echo $row['label']?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group form-group-default">
                                            <label>Payment Method</label>
                                            <select name="payment_id" id="payment_id" class="form-select">
                                                <option value="0">Select Payment Method</option>
                                                <?php foreach($payment_data as $row): ?>
                                                    <option value="<?php echo $row['payment_id']?>"><?php echo $row['payment_name']?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6 text-end">
                                        <p class="mb-1">Total (<span id="selected-count">0</span> item): 
                                            <strong>₱<span id="total-price">0</span></strong>
                                        </p>
                                        <button class="btn btn-success" id="reserve-btn">Reserve</button>
                                        <button class="btn btn-warning">Check Out</button>
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

        <div class="modal fade" id="reserveModal" tabindex="-1" aria-labelledby="reserveModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="reserveModalLabel">Select Delivery Date & Time</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Delivery Date -->
                        <div class="mb-3">
                            <label for="modal_delivery_date" class="form-label">Delivery Date</label>
                            <input type="date" id="modal_delivery_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Delivery Time -->
                        <div class="mb-3">
                            <label for="modal_delivery_time" class="form-label">Delivery Time</label>
                            <input type="time" id="modal_delivery_time" class="form-control" min="08:00" max="17:00">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirm-reserve-btn">Confirm Reservation</button>
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
        <script src="js/datatables-simple-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
        const selectAllCheckbox = document.getElementById('select-all');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const totalPriceEl = document.getElementById('total-price');
        const selectedCountEl = document.getElementById('selected-count');

        function updateTotal() {
            let total = 0;
            let count = 0;

            productCheckboxes.forEach((cb, index) => {
            if (cb.checked) {
                count++;
                const productCard = cb.closest('.product-item');
                const price = parseFloat(productCard.querySelector('.price').dataset.price);
                total += price;
            }
            });

            selectedCountEl.textContent = count;
            totalPriceEl.textContent = total.toLocaleString();
        }

        selectAllCheckbox.addEventListener('change', function () {
            productCheckboxes.forEach(cb => cb.checked = this.checked);
            updateTotal();
        });

        productCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
            const allChecked = Array.from(productCheckboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
            updateTotal();
            });
        });

        </script>

        <script>
        document.querySelector('.btn-warning').addEventListener('click', function() {
            const selectedItems = [];
            let totalPrice = 0;

            document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
                const parentCard = cb.closest('.product-item');
                const priceElem = parentCard.querySelector('.price');
                const price = parseFloat(priceElem.getAttribute('data-price')) || 0;

                const item = {
                    cart_id: parseInt(cb.getAttribute('data-cart-id')),
                    product_id: parseInt(cb.getAttribute('data-id')),
                    quantity: parseInt(cb.getAttribute('data-quantity')),
                    with_container: parseInt(cb.getAttribute('data-with-container')),
                    container_quantity: parseInt(cb.getAttribute('data-container-quantity'))
                };

                selectedItems.push(item);
                totalPrice += price;
            });

            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No items selected',
                    text: 'Please select at least one item to proceed to checkout.'
                });
                return;
            }

            const paymentId = parseInt(document.getElementById('payment_id').value);
            const locationId = parseInt(document.getElementById('location_id').value);

            if (locationId === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Location',
                    text: 'Please choose a location to deliver before checkout.'
                });
                return;
            }

            if (paymentId === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Payment Method',
                    text: 'Please choose a valid payment method before checkout.'
                });
                return;
            }

            Swal.fire({
                title: "Are you sure you want to checkout?",
                text: "You won't be able to undo this action.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Checkout!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    if (paymentId === 2) {
                        Swal.fire({
                            title: 'Scan to Pay via GCash',
                            html: `
                                <p>Please upload proof of payment (photo, receipt.):</p>
                                <input type="file" id="proofpayment" class="swal2-input" accept="image/*,.pdf">
                            `,
                            imageUrl: 'uploads/<?=$gcash?>',
                            imageWidth: 200,
                            imageHeight: 200,
                            imageAlt: 'GCash QR Code',
                            confirmButtonText: 'Done',
                            cancelButtonText: 'Cancel'
                        }).then((qrResult) => {
                            if (qrResult.isConfirmed) {
                                processCheckout(selectedItems, paymentId, locationId);
                            }
                        });
                    } else {
                        processCheckout(selectedItems, paymentId, locationId);
                    }
                }
            });
        });

        function processCheckout(items, paymentId, locationId) {

            const formData = new FormData();

            if (paymentId === 2) {
                const proofFile = document.getElementById("proofpayment").files[0];

                if (!proofFile) {
                    Swal.fire("Error!", "Please upload a proof of payment.", "warning");
                    return;
                }

                formData.append("proof_file", proofFile);
            }
            formData.append("items", JSON.stringify(items));
            formData.append("payment_id", paymentId);
            formData.append("location_id", locationId);

            fetch("process_checkout.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Success!", data.message, "success")
                        .then(() => window.location.href = "orders.php");
                } else {
                    Swal.fire("Error!", data.message, "error");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire("Error!", "An unexpected error occurred.", "error");
            });
        }

        </script>



        <script>
            document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();

                const card = this.closest('.card');
                const checkbox = card.querySelector('.product-checkbox');
                const cartId = checkbox.getAttribute('data-cart-id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to remove this item from the cart?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('process_removefromcart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ cart_id: cartId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove the card from the DOM
                                card.remove();

                                // Update the totals
                                //updateCartTotals();

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Removed!',
                                    text: 'The item has been removed from your cart.'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to remove item.'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'An error occurred',
                                text: 'Please try again later.'
                            });
                        });
                    }
                });
            });
        });

        </script>

        <script>
        document.getElementById("reserve-btn").addEventListener("click", function () {
            const selectedItems = [];
            let totalPrice = 0;

            document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
                const parentCard = cb.closest('.product-item');
                const priceElem = parentCard.querySelector('.price');
                const price = parseFloat(priceElem.getAttribute('data-price')) || 0;
                const quantity = parseInt(cb.getAttribute('data-quantity')) || 1;

                const item = {
                    cart_id: parseInt(cb.getAttribute('data-cart-id')),
                    product_id: parseInt(cb.getAttribute('data-id')),
                    quantity: quantity,
                    price: price, // ✅ include price so backend can compute amount
                    with_container: parseInt(cb.getAttribute('data-with-container')) || 0,
                    container_quantity: parseInt(cb.getAttribute('data-container-quantity')) || 0
                };

                selectedItems.push(item);
                totalPrice += price * quantity; // ✅ fix: include quantity
            });

            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No items selected',
                    text: 'Please select at least one item to reserve.'
                });
                return;
            }

            const paymentId = parseInt(document.getElementById('payment_id').value);
            const locationId = parseInt(document.getElementById('location_id').value);

            if (locationId === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Location',
                    text: 'Please choose a delivery address.'
                });
                return;
            }

            if (paymentId === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Payment Method',
                    text: 'Please choose a payment method.'
                });
                return;
            }

            // Save the selected data temporarily
            window.reservationData = {
                items: selectedItems,
                payment_id: paymentId,
                location_id: locationId
            };

            // Open modal
            const modal = new bootstrap.Modal(document.getElementById('reserveModal'));
            modal.show();
        });

        document.getElementById("confirm-reserve-btn").addEventListener("click", function () {
            const deliveryDate = document.getElementById("modal_delivery_date").value;
            const deliveryTime = document.getElementById("modal_delivery_time").value;

            // 💡 Time range validation
            const minTime = "08:00";
            const maxTime = "17:00";

            if (!deliveryDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Delivery Date',
                    text: 'Please choose a delivery date to proceed.'
                });
                return;
            }

            if (!deliveryTime) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Delivery Time',
                    text: 'Please choose a delivery time to proceed.'
                });
                return;
            }

            if (deliveryTime < minTime || deliveryTime > maxTime) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Time',
                    text: 'Delivery time must be between 8:00 AM and 5:00 PM.'
                });
                return;
            }

            const { items, payment_id, location_id } = window.reservationData;

            const formData = new FormData();
            formData.append("items", JSON.stringify(items));
            formData.append("payment_id", payment_id);
            formData.append("location_id", location_id);
            formData.append("delivery_date", deliveryDate);
            formData.append("delivery_time", deliveryTime);

            fetch("process_reservation.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Reserved!", data.message, "success")
                        .then(() => window.location.href = "orders.php");
                } else {
                    Swal.fire("Error!", data.message, "error");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire("Error!", "An unexpected error occurred.", "error");
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
