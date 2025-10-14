<?php 
require('db.php');
session_start();
date_default_timezone_set('Asia/Manila');

if (isset($_SESSION['loggedin'])) {
    switch($_SESSION['role_id']){
        case 1:
            $d = "index.php"; break;
        case 2:
            $d = "home.php"; break;
        case 3:
            $d = "rider.php"; break;
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
    <title>Register | AquaDrop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2e0e6c06f.js" crossorigin="anonymous"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .register-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            width: 90%;
            max-width: 1100px;
        }
        .logo-section {
            flex: 1 1 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
            margin-bottom: 2rem;
        }
        .logo-section img {
            width: 100px;
            margin-bottom: 1rem;
        }
        .form-section {
            flex: 1 1 350px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: white;
        }
        .form-section h3 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-control {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
        }
        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .input-group-text {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
        }
        .btn-primary {
            background: linear-gradient(to right, #2563eb, #1e3a8a);
            border: none;
            border-radius: 50px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #1d4ed8, #1e3a8a);
        }
        .card-footer a {
            color: #cbd5e1;
            text-decoration: none;
        }
        .card-footer a:hover {
            color: white;
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            body {
                padding: 2rem 0;
            }
            .register-container {
                flex-direction: column;
                text-align: center;
            }
            .form-section {
                width: 100%;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="logo-section">
        <img src="assets/img/logo2.png" alt="AquaDrop Logo">
        <h4>AQUADROP</h4>
    </div>

    <div class="form-section">
        <h3>Create Account</h3>
        <form action="process_register.php" method="POST" onsubmit="return checkForm()">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php if(isset($_GET['email'])) echo $_GET['email']?>" placeholder="Email" required readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" id="password" class="form-control" name="password" onInput="check()" placeholder="Password" required>
                    <span class="input-group-text" onclick="togglePassword()">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3 d-none" id="validation">
                <div id="count">Length : 0</div>
                <div id="check0"><i class="far fa-check-circle"></i> Length more than 8.</div>
                <div id="check2"><i class="far fa-check-circle"></i> Contains numerical character.</div>
                <div id="check3"><i class="far fa-check-circle"></i> Contains special character.</div>
                <div id="check4"><i class="far fa-check-circle"></i> Shouldn't contain spaces.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" id="confirm_password" class="form-control" name="confirm_password" onInput="confirmCheck()" placeholder="Confirm Password" required>
                    <span class="input-group-text" onclick="toggleConfirmPassword()">
                        <i class="fa fa-eye" id="confirmEyeIcon"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3 d-none" id="validation1">
                <div id="check5"><i class="far fa-check-circle"></i> Passwords do not match.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Continue</button>
        </form>

        <div class="text-center mt-4">
            <a href="login.php">Go back</a>
        </div>
    </div>
</div>

<!-- Bootstrap + SweetAlert + JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Keep your password toggle + validation scripts unchanged -->
<script>
<?php include('js/register-validation.js'); ?>
</script>

<?php if (isset($_GET['status'])): ?>
<script>
<?php if ($_GET['status'] == 'notmatch'): ?>
Swal.fire({icon: 'error', title: 'Error!', text: 'Passwords do not match.'});
<?php elseif ($_GET['status'] == 'error'): ?>
Swal.fire({icon: 'error', title: 'Oops...', text: 'Something went wrong while creating the account.'});
<?php elseif ($_GET['status'] == 'exist'): ?>
Swal.fire({icon: 'error', title: 'Oops...', text: 'Email already exists.'});
<?php elseif ($_GET['status'] == 'success'): ?>
Swal.fire({icon: 'success', title: 'Account Added!', text: 'The account has been successfully created. Please check your email to verify.'})
.then(() => window.location.href = "login.php");
<?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>
