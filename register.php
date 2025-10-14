<?php 
require('db.php');
session_start();
date_default_timezone_set('Asia/Manila');

if (isset($_SESSION['loggedin'])) {
    switch($_SESSION['role_id']){
        case 1: $d = "index.php"; break;
        case 2: $d = "home.php"; break;
        case 3: $d = "rider.php"; break;
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

  <!-- Bootstrap & Fonts -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <style>
    /* ===== General Layout ===== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body, html {
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(-45deg, #1e3c72, #2a5298, #1e3c72, #2a5298);
      background-size: 400% 400%;
      animation: gradientBG 10s ease infinite;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #fff;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1100px;
      width: 95%;
      padding: 30px;
      gap: 40px;
      flex-wrap: wrap;
    }

    /* ===== Left Logo Section ===== */
    .logo-section {
      flex: 1;
      text-align: center;
      animation: fadeIn 1.2s ease-in-out;
    }

    .logo-section img {
      width: 160px;
      height: auto;
      margin-bottom: 20px;
      animation: float 3s ease-in-out infinite;
    }

    .logo-section h2 {
      font-weight: 600;
      letter-spacing: 2px;
      color: #fff;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    /* ===== Register Card ===== */
    .form-card {
      flex: 1;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .form-card h3 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      color: #fff;
    }

    .form-control {
      background: rgba(255,255,255,0.2);
      border: none;
      color: #fff;
      transition: all 0.3s;
    }

    .form-control::placeholder {
      color: #d1d5db;
    }

    .form-control:focus {
      background: rgba(255,255,255,0.3);
      box-shadow: 0 0 10px rgba(255,255,255,0.5);
      color: #fff;
    }

    .input-group-text {
      background: rgba(255,255,255,0.2);
      border: none;
      color: #fff;
    }

    .btn-primary {
      width: 100%;
      background: linear-gradient(45deg, #1e3c72, #2a5298);
      border: none;
      border-radius: 30px;
      padding: 12px;
      font-weight: bold;
      transition: 0.3s;
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .go-back {
      text-align: center;
      margin-top: 20px;
    }

    .go-back a {
      color: #cde3ff;
      text-decoration: none;
      transition: 0.3s;
    }

    .go-back a:hover {
      text-decoration: underline;
      color: #fff;
    }

    /* ===== Responsive Design ===== */
    @media (max-width: 992px) {
      .container {
        flex-direction: column;
        align-items: center;
      }

      .form-card {
        width: 100%;
      }

      .logo-section {
        margin-bottom: 20px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Left Side Logo -->
    <div class="logo-section">
      <img src="assets/img/logo2.png" alt="AQUADROP Logo">
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
          <input type="email" name="email" class="form-control" 
            value="<?php if(isset($_GET['email'])) echo $_GET['email']?>" 
            placeholder="Email" required readonly>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" id="password" class="form-control" name="password" onInput="check()" placeholder="Password" required>
            <span class="input-group-text" onclick="togglePassword()"><i class="fa fa-eye" id="eyeIcon"></i></span>
          </div>
        </div>

        <div class="mb-3 d-none" id="validation">
          <div id="count">Length : 0</div>
          <div id="check0"><i class="far fa-check-circle"></i> Length ≥ 8</div>
          <div id="check2"><i class="far fa-check-circle"></i> Contains number</div>
          <div id="check3"><i class="far fa-check-circle"></i> Contains special character</div>
          <div id="check4"><i class="far fa-check-circle"></i> No spaces</div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <div class="input-group">
            <input type="password" id="confirm_password" class="form-control" name="confirm_password" onInput="confirmCheck()" placeholder="Confirm Password" required>
            <span class="input-group-text" onclick="toggleConfirmPassword()"><i class="fa fa-eye" id="confirmEyeIcon"></i></span>
          </div>
        </div>

        <div class="mb-3 d-none" id="validation1">
          <div id="check5" style="color:red;">Passwords do not match.</div>
        </div>

        <button type="submit" class="btn btn-primary">Continue</button>
      </form>

      <div class="go-back">
        <a href="login.php">Go back</a>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
      validationDiv.classList.toggle("d-none", input === "" || input === orig);
    }

    function checkForm() {
      const valid = [...document.querySelectorAll("#validation div")].slice(1).every(div => div.style.color === "lightgreen");
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
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong.' });
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
