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
            case 5:
                $d = "staffdashboard.php";
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
                        case 5:
                            $destination = "staffdashboard.php";
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
    <style>
        /* Base styles */
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Segoe UI', sans-serif;
}

.login-container {
    display: flex;
    height: 100vh;
    flex-direction: row;
}

/* Left side */
.login-left {
    flex: 1;
    background: linear-gradient(135deg, #f99f2c, #f97f2c);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    text-align: center;
    padding: 40px;
}

.branding i {
    font-size: 40px;
    margin-bottom: 10px;
}

.branding h1 {
    font-weight: 700;
    margin-bottom: 10px;
}

/* Right side */
.login-right {
    flex: 1;
    background-color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}

.login-form {
    width: 100%;
    max-width: 400px;
}

.login-form .avatar {
    text-align: center;
    margin-bottom: 20px;
    color: #ccc;
}

/* Input styles */
.input-group-text {
    background-color: #f1f1f1;
    border-right: none;
}

.input-group .form-control {
    border-left: none;
}

.input-group .form-control:focus {
    box-shadow: none;
    border-color: #f97f2c;
}

/* Button */
.login-btn {
    background: linear-gradient(135deg, #f99f2c, #f97f2c);
    color: white;
    border: none;
    padding: 10px;
    font-weight: bold;
    border-radius: 30px;
    transition: background 0.3s;
}

.login-btn:hover {
    background: #e86c1a;
}

/* Links */
.register-link {
    color: #f97f2c;
    text-decoration: none;
    font-weight: bold;
}

.register-link:hover {
    text-decoration: underline;
}

@media screen and (max-width: 768px) {
    .login-container {
        flex-direction: column;
    }

    .login-left,
    .login-right {
        flex: none;
        width: 100%;
        padding: 30px 20px;
        text-align: center;
    }

    .login-left {
        border-bottom-left-radius: 30px;
        border-bottom-right-radius: 30px;
    }

    .login-form {
        max-width: 100%;
    }

    .input-group {
        flex-direction: row;
    }

    .input-group-text {
        border-radius: 30px 0 0 30px;
    }

    .form-control {
        border-radius: 0 30px 30px 0;
    }
}

    </style>
</head>
<body class="bg-white">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="login-container">
                    <div class="login-left">
                        <div class="branding">
                            <i class="fas fa-fire fa-2x"></i>
                            <h1><strong>Nothing.</strong></h1>
                            <p>Welcome to Nothing!</p>
                        </div>
                    </div>
                    <div class="login-right">
                        <form method="POST" class="login-form">
                            <div class="avatar">
                                <i class="fas fa-user-circle fa-4x"></i>
                            </div>
                            <p class="text-muted text-center mb-4">Login below to get started.</p>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Email Address" required>
                            </div>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>

                            <button type="submit" class="btn login-btn w-100">Login</button>

                            <div class="text-center mt-3">
                                <span class="text-muted">New user?</span> 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#register" class="register-link">Register</a>
                            </div>
                        </form>
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
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php'; 
                }
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
