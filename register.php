<?php 
 require('db.php');
 session_start();
 date_default_timezone_set('Asia/Manila');

 if (isset($_SESSION['loggedin'])) {
    switch($_SESSION['role_id']){
        case 1:
            $d = "index.php";
            break;
        case 2:
            $d = "home.php";
            break;
        case 3:
            $d = "rider.php";
            break;
        default:
            $error_message = "Unexpected error. Please try again.";
    }
    if(isset($d)){ 
        header("Location: ".$d);
        exit();
    }
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
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-white">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                            <img src="assets/img/logo.png" alt="Logo" class="mb-2" style="width: 150px; height: auto; display: block; margin: 0 auto;">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Create Account</h3></div>
                                <div class="card-body">
                                    <form action="process_register.php" method="POST" onsubmit="return checkForm()">
                                        <div class="mb-3">
                                            <label for="" class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="" class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control" value="<?php if(isset($_GET['email'])) echo $_GET['email']?>" placeholder="Email" required readonly>
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

                                        <button type="submit" class="btn btn-primary w-100">Continue</button>
                                    </form>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small"><a href="login.php">Go back</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

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
</body>
</html>
