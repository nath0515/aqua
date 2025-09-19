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
    body, html {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(-45deg, #1e3c72, #2a5298, #1e3c72, #2a5298);
        background-size: 400% 400%;
        animation: gradientBG 10s ease infinite;
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .login-container {
        display: flex;
        height: 100vh;
        flex-direction: row;
    }

    .login-left {
        flex: 1;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        text-align: center;
        padding: 40px;
    }
    .modal-backdrop {
    backdrop-filter: blur(5px);
    background-color: rgba(0, 0, 0, 0.3);
}

    .modal-content {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #000 !important;
    }

    .modal-content .form-control {
        background: rgba(255, 255, 255, 0.9);
        color: #000 !important;
        border: 1px solid #ccc;
        border-radius: 8px;
    }

    .modal-content .form-control::placeholder {
        color: #555 !important;
    }

    .modal-content .input-group-text {
        background: rgba(255, 255, 255, 0.8);
        color: #000;
        border: 1px solid #ccc;
    }

    .modal-content .btn-success {
        background: #007bff;
        border: none;
        transition: 0.3s ease-in-out;
    }

    .modal-content .btn-success:hover {
        background: #0056b3;
    }


    .branding {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .branding-logo {
        width: 25vw;      
        max-width: 320px;  
        min-width: 150px;  
        height: auto;      
        margin-bottom: 20px;
        animation: float 3s ease-in-out infinite;
    }

    /* Text styling */
    .branding-text {
        font-size: clamp(1.5rem, 3vw, 3rem); 
        letter-spacing: 3px;
        color: white;
        margin-top: 10px;
    }



    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .login-right {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
    }

    .login-form {
        width: 100%;
        max-width: 400px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        color: #fff;
        animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-form .avatar {
        text-align: center;
        margin-bottom: 20px;
        color: #fff;
    }

    .input-group-text {
        background-color: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
    }

    .form-control {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        transition: all 0.3s;
    }

    .form-control::placeholder {
        color: #eee;
    }

    .form-control:focus {
        background: rgba(255,255,255,0.3);
        box-shadow: 0 0 10px rgba(255,255,255,0.5);
        color: #fff;
    }

    .login-btn {
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        border: none;
        padding: 12px;
        font-weight: bold;
        border-radius: 30px;
        transition: transform 0.3s, box-shadow 0.3s;
        color: white;
    }

    .login-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .forgot-link, .register-link {
        color: #cde3ff;
        transition: 0.3s;
    }

    .forgot-link:hover, .register-link:hover {
        text-decoration: underline;
        color: #fff;
    }
    .app-description {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 15px;
    backdrop-filter: blur(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
    }


    @media (max-width: 768px) {
        .login-container {
            flex-direction: column;
        }

        .login-left {
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }

        .login-right {
            padding: 20px;
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
                            <img src="assets/img/logo1.png" alt="Logo" class="branding-logo">
                        </div>
                    </div>
                    <div class="login-right">
                        <form method="POST" class="login-form">
                            <div class="avatar">
                                <i class="fas fa-user-circle fa-4x"></i>
                            </div>
                            <p class="text-center mb-4">Login below to get started.</p>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Email Address" required>
                            </div>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                                <span class="input-group-text" onclick="togglePassword()" style="cursor:pointer;">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                            <div class="text-end mb-3">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#forgot" class="forgot-link">Forgot Password?</a>
                            </div>

                            <button type="submit" class="btn login-btn w-100">Login</button>

                            <div class="text-center mt-3">
                                <span>New user?</span> 
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
