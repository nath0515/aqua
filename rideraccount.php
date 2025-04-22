<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    
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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#!">Settings</a></li>
                        <li><a class="dropdown-item" href="#!">Activity Log</a></li>
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
                                    <a class="nav-link" href="#">Stock</a>
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
                                <h4 class="mb-0">🚴 Rider Registration</h4>
                            </div>
                            <div class="card-body">
                                <form action="process_registerrider.php" method="POST" onsubmit="return checkForm()">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="name" required placeholder="Juan Dela Cruz">
                                    </div>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="name" required placeholder="Juan Dela Cruz">
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact_number" name="contact"
                                            required pattern="[0-9]{11}" maxlength="11"
                                            placeholder="09XXXXXXXXX"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,11)">
                                    </div>

                                    <div class="mb-3">
                                        <label for="username" class="form-label">Email</label>
                                        <input type="text" class="form-control" id="email" name="email" placeholder="juandelacruz@gmail.com" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="" class="form-label">Password</label>
                                            <div class="input-group">
                                                <input type="password" id="password" class="form-control" name="password"  onInput="check()" placeholder="Password" required>
                                                <span class="input-group-text" onclick="togglePassword()">
                                                    <i class="fa fa-eye" id="eyeIcon"></i> 
                                                </span>
                                            </div>
                                    </div>

                                    <div class="mb-3 d-none" id="validation">
                                            <div id="count">Length : 0</div>
                                            <!-- Password Strength Check (Moved here under Confirm Password) -->
                                            <div id="check0">
                                                <i class="far fa-check-circle"></i> <span> Length more than 8.</span>
                                            </div>
                                            <div id="check2">
                                                <i class="far fa-check-circle"></i> <span> Contains numerical character.</span>
                                            </div>
                                            <div id="check3">
                                                <i class="far fa-check-circle"></i> <span> Contains special character.</span>
                                            </div>
                                            <div id="check4">
                                                <i class="far fa-check-circle"></i> <span> Shouldn't contain spaces.</span>
                                            </div>
                                        </div>

                                    <div class="mb-3">
                                        <label for="" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <input type="password" id="confirm_password" class="form-control" name="confirm_password" onInput="confirmCheck()" placeholder="Password" required>
                                            <span class="input-group-text" onclick="toggleConfirmPassword()">
                                                <i class="fa fa-eye" id="confirmEyeIcon"></i> 
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3 d-none" id="validation1">
                                            <div id="check5">
                                                <i class="far fa-check-circle"></i> <span> Passwords do not match.</span>
                                            </div>       
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">Register Rider</button>
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
    </body>
</html>
