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
            <main class="container py-4">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-8">
                        <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">👤 Admin Registration</h4>
                    </div>
                    <div class="card-body">
                        <form action="process_registeradmin.php" method="POST" onsubmit="return checkForm()" id="adminForm">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstname" name="firstname" required placeholder="Juan">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastname" name="lastname" required placeholder="Dela Cruz">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="contact_number" required pattern="[0]{1}[9]{1}[0-9]{9}" maxlength="11" placeholder="09XXXXXXXXX">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email (Gmail only)</label>
                                <input type="email" class="form-control" id="email" name="email" required pattern="[a-zA-Z0-9._%+-]+@gmail\.com$">
                                <div class="form-text">Must be a Gmail address (e.g. example@gmail.com).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required oninput="check()">
                                    <span class="input-group-text" onclick="togglePassword()"><i class="fa fa-eye" id="eyeIcon"></i></span>
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="validation">
                                <div id="count">Length : 0</div>
                                <div id="check0"><i class="far fa-check-circle"></i> <span>Length more than 8</span></div>
                                <div id="check2"><i class="far fa-check-circle"></i> <span>Contains number</span></div>
                                <div id="check3"><i class="far fa-check-circle"></i> <span>Contains special character</span></div>
                                <div id="check4"><i class="far fa-check-circle"></i> <span>No spaces</span></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required oninput="confirmCheck()">
                                    <span class="input-group-text" onclick="toggleConfirmPassword()"><i class="fa fa-eye" id="confirmEyeIcon"></i></span>
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="validation1">
                                <div id="check5"><i class="far fa-check-circle"></i> Passwords do not match.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register Admin</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
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
            const emailInput = document.getElementById("email");

            emailInput.addEventListener("input", function () {
                const pattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
                if (!pattern.test(emailInput.value)) {
                    emailInput.classList.add("is-invalid");
                    emailInput.classList.remove("is-valid");
                } else {
                    emailInput.classList.remove("is-invalid");
                    emailInput.classList.add("is-valid");
                }
            });
        </script>
        <script>
            document.getElementById("riderForm").addEventListener("submit", function(e) {
                const contactField = document.getElementById("contact_number");
                const contactError = document.getElementById("contactError");
                const contactValue = contactField.value;

                // Check if it's exactly 11 digits and starts with "09"
                const isValid = /^[0]{1}[9]{1}[0-9]{9}$/.test(contactValue);

                if (!isValid) {
                    e.preventDefault(); // Stop form submission
                    contactField.classList.add("is-invalid");
                    contactError.style.display = "block";
                } else {
                    contactField.classList.remove("is-invalid");
                    contactError.style.display = "none";
                }
            });
        </script>

        <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            var eyeIcon = document.getElementById("eyeIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
        function toggleConfirmPassword() {
            var passwordField = document.getElementById("confirm_password");
            var eyeIcon = document.getElementById("confirmEyeIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
        

        function check(){
            var input = document.getElementById("password").value;

            input = input.trim();
            document.getElementById("password").value = input;
            document.getElementById("count").innerText = "Length : " + input.length;
            var validationDiv = document.getElementById("validation");
            if (input.length > 0) {
                // Remove d-none class to make the validation div visible
                validationDiv.classList.remove("d-none");
            } else {
                // Add d-none class to hide the validation div if input is empty
                validationDiv.classList.add("d-none");
            }
            if(input.length >= 8){
                document.getElementById("check0").style.color = "green";
            }else{
                document.getElementById("check0").style.color = "red";
            }
            if(input.match(/[0-9]/i)){
                document.getElementById("check2").style.color = "green";
            }else{
                document.getElementById("check2").style.color = "red";
            }

            if(input.match(/[^A-Za-z0-9'']/i)){
                document.getElementById("check3").style.color = "green";
            }else{
                document.getElementById("check3").style.color = "red";
            }

            if(input.match('')){
                document.getElementById("check4").style.color = "green";
            }else{
                document.getElementById("check4").style.color = "red";
            }
        }

        function checkForm() {
            var check0 = document.getElementById("check0").style.color === "green";
            var check2 = document.getElementById("check2").style.color === "green";
            var check3 = document.getElementById("check3").style.color === "green";
            var check4 = document.getElementById("check4").style.color === "green";

            if (check0 && check2 && check3 && check4) {
                return true;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please ensure all conditions are met before submitting.',
                    confirmButtonText: 'Try again',
                });
                return false;
            }
        }

        function confirmCheck(){
            var input = document.getElementById("confirm_password").value;
            var orig = document.getElementById("password").value;

            input = input.trim();
            document.getElementById("confirm_password").value = input;
            var validationDiv = document.getElementById("validation1");
            if (input.length > 0) {
                validationDiv.classList.remove("d-none");
            } else {
                validationDiv.classList.add("d-none");
            }
            if(input == orig){
                document.getElementById("check5").style.color = "green";
                validationDiv.classList.add("d-none");
            }else{
                document.getElementById("check5").style.color = "red";
                
                validationDiv.classList.remove("d-none");
            }
        }
    
    </script>
        <?php if (isset($_GET['status'])): ?>
        <script>
            <?php if ($_GET['status'] == 'notmatch'): ?>
            Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?php echo "Passwords do not match." ?>',
            });
            <?php elseif ($_GET['status'] == 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong while creating the account.',
                });
            <?php elseif ($_GET['status'] == 'exist'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Email already exists.',
                });
            <?php elseif ($_GET['status'] == 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Account Added!',
                    text: 'The account has been successfully created. Please check your email to verify.',
                }).then((result) => { window.location.href = "login.php";
                });
            <?php endif ?>
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
