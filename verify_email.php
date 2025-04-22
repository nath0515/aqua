<?php 
 require('db.php');
 session_start();
 date_default_timezone_set('Asia/Manila');

 if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $sql = "SELECT * FROM users WHERE verification_token = :token AND role_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if (!($stmt->rowCount() > 0)) {
        header('Location: 401.html');
        exit();
    }
    $data = $stmt->fetch();
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
    <title>User Details</title>
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
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">User Details</h3></div>
                                <div class="card-body">
                                    <form action="process_verifydetails.php" method="POST" onsubmit="return checkForm()">
                                        <input type="text" name="token" class="form-control" value="<?php if(isset($_GET['token'])) echo $_GET['token']?>" placeholder="Email" required hidden readonly>
                                        <div class="mb-3">
                                            <label for="" class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control" value="<?php if(isset($data['email'])) echo $data['email']?>" placeholder="Email" required readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="" class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control" value="" placeholder="Username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="" class="form-label">First Name</label>
                                            <input type="text" name="firstname" class="form-control" value="" placeholder="First name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="" class="form-label">Last Name</label>
                                            <input type="text" name="lastname" class="form-control" value="" placeholder="Last name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="" class="form-label">Address</label>
                                            <input type="text" name="address" class="form-control" value="" placeholder="Address" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="" class="form-label">Contact Number</label>
                                            <input type="text" name="contact_number" class="form-control" value="" oninput="validatePhoneNumber(this)" placeholder="Contact Number" required>
                                            <script>
                                                function validatePhoneNumber(input) {
                                                    input.value = input.value.replace(/[^0-9]/g, '').slice(0, 11);
                                                }
                                            </script>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">Save</button>
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


    <?php if (isset($_GET['status'])): ?>
        <script>
            <?php if ($_GET['status'] == 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong while saving the details.',
                });
            <?php elseif ($_GET['status'] == 'exist'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Username already exists.',
                });
                <?php elseif ($_GET['status'] == 'invalidphone'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Phone number is invalid!',
                });
            <?php endif ?>
        </script>
    <?php endif; ?>
</body>
</html>
