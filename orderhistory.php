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
    // Fetch notifications for customer
    // Get notifications using helper for consistency
    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;

            $sql = "SELECT * FROM orderstatus";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total items in cart (only product quantity, not containers)
    $sql = "SELECT a.cart_id, a.product_id, b.product_name, a.with_container, a.quantity, a.container_quantity 
        FROM cart a 
        JOIN products b ON a.product_id = b.product_id 
        WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cart_count = 0;
    foreach ($cart_data as $item) {
        $cart_count += $item['quantity'];
    }
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
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
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
                        <a class="nav-link collapsed <?php echo ($current_page == 'costumerorder.php' || $current_page == 'orderhistory.php') ? '' : 'collapsed'; ?>" 
                        href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="<?php echo ($current_page == 'costumerorder.php' || $current_page == 'orderhistory.php') ? 'true' : 'false'; ?>" 
                        aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                            Order Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>

                        <div class="collapse <?php echo ($current_page == 'costumerorder.php' || $current_page == 'orderhistory.php') ? 'show' : ''; ?>" 
                            id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link <?php echo $current_page == 'costumerorder.php' ? 'active' : ''; ?>" href="costumerorder.php">Order</a>
                                <a class="nav-link <?php echo $current_page == 'orderhistory.php' ? 'active' : ''; ?>" href="orderhistory.php">Order History</a>
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
                        <h1 class="mt-4">Order History</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="home.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Order Management</li>
                            <li class="breadcrumb-item active">Order History</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Order History
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
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
                                        
                                    </tbody>
                                </table>
                                <div id="pagination" class="mt-3 text-end"></div>
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
            document.addEventListener("DOMContentLoaded", function() {
                const datatablesSimple = document.getElementById('datatablesSimple');
                const paginationContainer = document.getElementById('pagination');
                let currentPage = 1;
                const perPage = 10;
                function fetchOrders(page = 1) {
                    fetch(`process_usercheckorders.php?page=${page}&perPage=${perPage}`, {
                        method: 'GET',
                        headers: { 'Content-Type': 'application/json' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateOrdersTable(data.orders);
                            renderPagination(data.total, data.page, data.perPage);
                        } else {
                            console.error("Failed to fetch orders:", data.message);
                        }
                    })
                    .catch(error => console.error("Error fetching orders:", error));
                }

                function updateOrdersTable(orders) {
                    const tbody = datatablesSimple.querySelector('tbody');
                    if (tbody) {
                        tbody.innerHTML = '';
                    }

                    orders.forEach(order => {
                        const row = document.createElement('tr');
                        const riderName = order.rider_firstname === 'None' ? 'None' : `${order.rider_firstname} ${order.rider_lastname}`;
                        // Build complete address
                        let completeAddress = '';
                        if (order.label) {
                            completeAddress += `<strong>${order.label}</strong><br>`;
                        }
                        if (order.address) {
                            completeAddress += order.address;
                        }
                        if (order.barangay_name) {
                            completeAddress += `, ${order.barangay_name}`;
                        }
                        if (order.municipality_name) {
                            completeAddress += `, ${order.municipality_name}`;
                        }
                        if (order.province_name) {
                            completeAddress += `, ${order.province_name}`;
                        }
                        if (order.latitude && order.longitude) {
                            completeAddress += `<br><small class="text-muted">📍 ${order.latitude}, ${order.longitude}</small>`;
                        }
                        if (!completeAddress) {
                            completeAddress = '<span class="text-muted">No address data</span>';
                        }

                        row.innerHTML = `
                            <td><strong>#${order.order_id}</strong></td>
                            <td>${order.date}</td>
                            <td>₱${order.amount}</td>
                            <td>${order.firstname} ${order.lastname}</td>
                            <td>${order.contact_number}</td>
                            <td>${completeAddress}</td>
                           <td><div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2">
                                    <span class="badge ${getStatusBadgeClass(order.status_name)}">
                                    ${order.status_name}
                                    </span>
                                </div>
                            </td>
                            <td>${riderName}</td>
                            <td>
                                <a href="costumer_orderdetails.php?id=${order.order_id}" class="btn btn-outline-secondary btn-sm me-1">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                ${(!['Delivered','Delivering','Completed'].includes(order.status_name)) 
                                    ? `<button class="btn btn-outline-danger btn-sm cancelOrderBtn"
                                            data-id="${order.order_id}"
                                            title="Cancel Order">
                                            <i class="bi bi-x-circle"></i>
                                    </button>` 
                                : ''}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }

                function renderPagination(total, page, perPage) {
                    const totalPages = Math.ceil(total / perPage);
                    let html = '';
                    const maxVisible = 5; // max page buttons visible
                    const start = Math.max(1, page - Math.floor(maxVisible / 2));
                    const end = Math.min(totalPages, start + maxVisible - 1);

                    if (totalPages <= 1) {
                        paginationContainer.innerHTML = '';
                        return;
                    }

                    if (page > 1) {
                        html += `<button class="btn btn-sm btn-outline-primary me-1" data-page="${page - 1}">Previous</button>`;
                    }

                    if (start > 1) {
                        html += `<button class="btn btn-sm btn-outline-primary me-1" data-page="1">1</button>`;
                        if (start > 2) {
                            html += `<span class="me-1">...</span>`;
                        }
                    }

                    for (let i = start; i <= end; i++) {
                        html += `<button class="btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-primary'} me-1" data-page="${i}">${i}</button>`;
                    }

                    if (end < totalPages) {
                        if (end < totalPages - 1) {
                            html += `<span class="me-1">...</span>`;
                        }
                        html += `<button class="btn btn-sm btn-outline-primary me-1" data-page="${totalPages}">${totalPages}</button>`;
                    }

                    if (page < totalPages) {
                        html += `<button class="btn btn-sm btn-outline-primary" data-page="${page + 1}">Next</button>`;
                    }

                    paginationContainer.innerHTML = html;

                    // Add event listeners
                    paginationContainer.querySelectorAll('button[data-page]').forEach(button => {
                        button.addEventListener('click', () => {
                            const newPage = parseInt(button.getAttribute('data-page'));
                            currentPage = newPage;
                            fetchOrders(newPage);
                        });
                    });
                }
                function getStatusBadgeClass(status) {
                    switch (status) {
                        case 'Pending':
                            return 'bg-warning text-dark';
                        case 'Accepted':
                            return 'bg-primary';
                        case 'Delivering':
                            return 'bg-warning text-dark';
                        case 'Delivered':
                            return 'bg-success';
                        case 'Completed':
                            return 'bg-info';
                        case 'Cancel':
                        case 'Cancelled':
                            return 'bg-danger';
                        default:
                            return 'bg-secondary';
                    }
                }

                // Load first page
                fetchOrders(currentPage);
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
        <script>
            $(document).ready(function () {
                $(document).on("click", ".cancelOrderBtn", function () {
                    var orderId = $(this).data("id");

                    Swal.fire({
                        title: "Cancel Order",
                        html: `
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                <select id="cancelReason" class="swal2-input">
                                    <option value="" disabled selected>Select a reason</option>
                                    <option value=Wrong Delivery Address">Wrong Delivery Address</option>
                                    <option value="Entered Date And time is wrong">Entered Date And time is wrong</option>
                                    <option value="Changed my mind">Changed my mind</option>
                                    <option value="Wrong item ordered">Wrong item ordered</option>
                                    <option value="Other">Other</option>
                                </select>
                                <input type="text" id="otherReason" class="swal2-input" 
                                    placeholder="Enter reason" style="display:none;">
                            </div>
                        `,
                        didOpen: () => {
                            const dropdown = Swal.getPopup().querySelector("#cancelReason");
                            const input = Swal.getPopup().querySelector("#otherReason");

                            dropdown.addEventListener("change", () => {
                                if (dropdown.value === "Other") {
                                    input.style.display = "block";
                                } else {
                                    input.style.display = "none";
                                    input.value = ""; // clear if hidden
                                }
                            });
                        },
                        preConfirm: () => {
                            const dropdown = Swal.getPopup().querySelector("#cancelReason");
                            const input = Swal.getPopup().querySelector("#otherReason");

                            if (!dropdown.value) {
                                Swal.showValidationMessage("Please select a reason");
                                return false;
                            }

                            if (dropdown.value === "Other" && !input.value.trim()) {
                                Swal.showValidationMessage("Please enter your reason");
                                return false;
                            }

                            return dropdown.value === "Other" ? input.value.trim() : dropdown.value;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "user_cancel_order.php",
                                type: "POST",
                                dataType: "json",
                                data: {
                                    order_id: orderId,
                                    reason: result.value
                                },
                                success: function(response) {
                                    if (response.success) {
                                        Swal.fire({
                                            icon: "success",
                                            title: "Order Cancelled",
                                            text: response.message,
                                            confirmButtonColor: "#3085d6"
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: "error",
                                            title: "Error",
                                            text: response.message
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Server Error",
                                        text: "Something went wrong: " + error
                                    });
                                }
                            });
                        }
                    });
                });
            });
        </script>

</body>
</html>
