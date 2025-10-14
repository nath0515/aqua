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
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Account - AQUADROP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
        body {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            color: #fff;
        }

        .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            width: 100%;
            padding: 20px;
        }

        .logo-section {
            flex: 1;
            text-align: center;
        }

        .logo-section img {
            width: 120px;
            margin-bottom: 10px;
        }

        .logo-section h2 {
            font-weight: 600;
            letter-spacing: 3px;
            color: #fff;
        }

        .form-card {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            color: #fff;
        }

        .form-card h3 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
        }

        .form-control::placeholder {
            color: #cbd5e1;
        }

        .form-control:focus {
            box-shadow: none;
            border: 1px solid #3b82f6;
            background-color: rgba(255, 255, 255, 0.25);
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(to right, #1e40af, #1d4ed8);
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #2563eb, #1d4ed8);
        }

        .go-back {
            text-align: center;
            margin-top: 15px;
        }

        .go-back a {
            color: #93c5fd;
            text-decoration: none;
        }

        .go-back a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .form-card {
                width: 100%;
                margin-top: 30px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Left Side Logo -->
        <div class="logo-section">
            <img src="assets/img/logo2.png" alt="AQUADROP Logo" />
            <h2>AQUADROP</h2>
        </div>

        <!-- Right Side Form -->
        <div class="form-card">
            <h3>Create Account</h3>
            <form action="process_register.php" method="POST" onsubmit="return checkForm()">

                <!-- Username -->
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control"
                        value="<?php if(isset($_GET['email'])) echo $_GET['email']?>" 
                        placeholder="Email" 
                        required 
                        readonly
                    >
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="password" 
                            class="form-control" 
                            name="password"  
                            onInput="check()" 
                            placeholder="Password" 
                            required
                        >
                        <span class="input-group-text bg-transparent text-white" onclick="togglePassword()">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Password validation -->
                <div class="mb-3 d-none" id="validation">
                    <div id="count">Length : 0</div>
                    <div id="check0"><i class="far fa-check-circle"></i> Length more than 8</div>
                    <div id="check2"><i class="far fa-check-circle"></i> Contains number</div>
                    <div id="check3"><i class="far fa-check-circle"></i> Contains special character</div>
                    <div id="check4"><i class="far fa-check-circle"></i> Shouldn't contain spaces</div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            class="form-control" 
                            name="confirm_password" 
                            onInput="confirmCheck()" 
                            placeholder="Password" 
                            required
                        >
                        <span class="input-group-text bg-transparent text-white" onclick="toggleConfirmPassword()">
                            <i class="fa fa-eye" id="confirmEyeIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Password Match Check -->
                <div class="mb-3 d-none" id="validation1">
                    <div id="check5" style="color: red;">Passwords do not match.</div>
                </div>

                <button type="submit" class="btn btn-primary">Continue</button>
            </form>

            <div class="go-back">
                <a href="login.php">Go back</a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        function togglePassword() {
            const field = document.getElementById("password");
            const icon = document.getElementById("eyeIcon");
            field.type = field.type === "password" ? "text" : "password";
            icon.classList.toggle("fa-eye-slash");
        }

        function toggleConfirmPassword() {
            const field = document.getElementById("confirm_password");
            const icon = document.getElementById("confirmEyeIcon");
            field.type = field.type === "password" ? "text" : "password";
            icon.classList.toggle("fa-eye-slash");
        }

        function check() {
            var input = document.getElementById("password").value.trim();
            document.getElementById("password").value = input;
            document.getElementById("count").innerText = "Length : " + input.length;
            var validationDiv = document.getElementById("validation");
            validationDiv.classList.toggle("d-none", input.length === 0);

            document.getElementById("check0").style.color = input.length >= 8 ? "lightgreen" : "red";
            document.getElementById("check2").style.color = /[0-9]/.test(input) ? "lightgreen" : "red";
            document.getElementById("check3").style.color = /[^A-Za-z0-9]/.test(input) ? "lightgreen" : "red";
            document.getElementById("check4").style.color = /\s/.test(input) ? "red" : "lightgreen";
        }

        function confirmCheck() {
            const input = document.getElementById("confirm_password").value.trim();
            const orig = document.getElementById("password").value;
            const validationDiv = document.getElementById("validation1");
            if (input.length === 0) {
                validationDiv.classList.add("d-none");
            } else {
                validationDiv.classList.remove("d-none");
                if (input === orig) {
                    validationDiv.classList.add("d-none");
                } else {
                    validationDiv.classList.remove("d-none");
                }
            }
        }

        function checkForm() {
            const valid = [...document.querySelectorAll("#validation div")]
                .slice(1)
                .every(div => div.style.color === "lightgreen");
            if (!valid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please ensure all password conditions are met.',
                });
                return false;
            }
            return true;
        }
    </script>

    <?php if (isset($_GET['status'])): ?>
        <script>
            <?php if ($_GET['status'] == 'notmatch'): ?>
                Swal.fire({ icon: 'error', title: 'Error!', text: 'Passwords do not match.' });
            <?php elseif ($_GET['status'] == 'error'): ?>
                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong while creating the account.' });
            <?php elseif ($_GET['status'] == 'exist'): ?>
                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Email already exists.' });
            <?php elseif ($_GET['status'] == 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Account Added!',
                    text: 'Your account has been successfully created. Please check your email to verify.',
                }).then(() => window.location.href = "login.php");
            <?php endif ?>
        </script>
    <?php endif; ?>
</body>
</html>
