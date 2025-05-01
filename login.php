<?php 
    require('db.php');
    session_start();
    date_default_timezone_set('Asia/Manila');
    $destination = "index.php";

    if (isset($_SESSION['loggedin'])) {
        switch($_SESSION['role_id']){
            case 1:
                $d = "index.php";
                break;
            case 2:
                $d = "home.php";
                break;
            case 3:
                $d = "riderdashboard.php";
                break;
            default:
                $error_message = "Unexpected error. Please try again.";
        }
        if(isset($d)){ 
            header("Location: ".$d);
            exit();
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if(isset($_POST['username']) && isset($_POST['password'])){
            $username = $_POST['username'];
            $password = $_POST['password'];

            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($password, $user['password'])) {
                    switch($user['role_id']){
                        case 0:
                            $error_message = "Please verify your email address.";
                            break;
                        case 1:
                            $destination = "index.php";
                            break;
                        case 2:
                            $destination = "home.php";
                            break;
                        case 3:
                            $destination = "riderdashboard.php";
                            break;
                        default:
                            $error_message = "Unexpected error. Please try again.";
                    }
                    
                    if(isset($destination)){
                        $_SESSION['username'] = $username;
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['loggedin'] = true;
                        $_SESSION['role_id'] = $user['role_id'];
                        header("Location: ".$destination);
                        exit();
                    }
                    
                }
                else{
                    $error_message = "Invalid password.";
                }
            }
            else {
                $error_message = "No user found with that username.";
            }
        }
        else if(isset($_POST['email'])){
            $email = $_POST['email'];

            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error_message = "Email already exists!";
            }
            else{
                header("Location: register.php?email=".$email);
                exit();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

</head>
<body class="bg-white">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                <img src="assets/img/logo.png" alt="Logo" class="mb-2" style="width: 150px; height: auto; display: block; margin: 0 auto;">
                                    <h3 class="text-center font-weight-light my-4">Login</h3>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="mb-3 input-group">
                                            <span class="input-group-text">
                                                <i class="fa fa-user"></i>
                                            </span>
                                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                                        </div>

                                        <div class="mb-3 input-group">
                                            <span class="input-group-text">
                                                <i class="fa fa-lock"></i> 
                                            </span>
                                            <input type="password" id="password" class="form-control" name="password" placeholder="Password" required>
                                            <span class="input-group-text" onclick="togglePassword()">
                                                <i class="fa fa-eye" id="eyeIcon"></i> 
                                            </span>
                                        </div>
                                        <!--
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" id="inputRememberPassword" type="checkbox" value="" />
                                            <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                                        </div>
                                        -->
                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <a class="small" href="#" data-bs-toggle="modal" data-bs-target="#forgot">Forgot Password?</a>

                                            <button type="submit" class="btn btn-primary">Login</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#register">Need an account? Sign up!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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
    
    </script>

    <?php if (isset($error_message)): ?>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $error_message; ?>',
        });
        </script>
     <?php endif; ?>

    <?php if (isset($_GET['fstatus']) && $_GET['fstatus'] == 'success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Link Sent',
                text: 'Password reset link is sent to your email. Please check your email inbox.',
            });
        </script>
    <?php elseif (isset($_GET['fstatus']) && $_GET['fstatus'] == 'error'): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Unexpected error. Please try again.',
            });
        </script>
    <?php elseif (isset($_GET['fstatus']) && $_GET['fstatus'] == '404'): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Email does not exist.',
            });
        </script>
    <?php endif; ?>



    <!-- Modal -->
    <div class="modal fade" id="register" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            
                <form action="" method="POST">
                    <!-- Modal Body -->
                    <div class="modal-body">
                        <p class="small">Please fill out the email form to create an account.</p>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text pe-3"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                            </div>
                        </div>
                    </div>
                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Continue</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="forgot" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="process_sendforgotpassword.php" method="GET">
                    <!-- Modal Body -->
                    <div class="modal-body">
                        <p class="small">Please fill out the email form to recover your account.</p>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text pe-3"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                            </div>
                        </div>
                    </div>
                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Continue</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    
</body>
</html>
