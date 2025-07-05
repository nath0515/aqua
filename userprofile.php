<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number,profile_pic FROM users u
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
        <title>My Profile</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
            <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
            </a>
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                    <li class="nav-item me-2">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                </li>
                <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><a class="dropdown-item" href="#">Notification 1</a></li>
                        <li><a class="dropdown-item" href="#">Notification 2</a></li>
                        <li><a class="dropdown-item" href="#">Notification 3</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <li><a class="dropdown-item" href="mapuser.php">Pinned Location</a></li>
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
                    <a class="nav-link" href="home.php">
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
                            <a class="nav-link" href="costumerorder.php">Order</a>
                            <a class="nav-link" href="orderhistory.php">Order History</a>
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
                                <h3 class="mb-0"><i class="bi bi-person-circle"></i>  My Profile</h3>
                            </div>
                            <div class="card-body">
                                <form id="profileForm" action="update_profile.php" method="POST">
                                    <div class="mb-3 text-center">
                                        <img src="uploads/<?php echo htmlspecialchars($user_data['profile_pic'] ?? 'default.png'); ?>" 
                                            alt="Profile Picture" 
                                            id="profilePreview"
                                            class="img-thumbnail rounded-circle" 
                                            style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <div class="mb-3 d-none" id="profilePicGroup">
                                        <label for="profile_pic" class="form-label">Change Profile Picture</label>
                                        <input type="file" class="form-control" name="profile_pic" id="profile_pic" accept="image/*">
                                    </div>
                                    <!-- Full Name (Read-only) -->
                                    <div class="mb-3" id="fullnameGroup">
                                        <label for="fullname" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="fullname" 
                                            value="<?php echo htmlspecialchars($user_data['firstname'] . ' ' . $user_data['lastname']); ?>" 
                                            readonly>
                                    </div>

                                    <!-- First Name (Hidden initially) -->
                                    <div class="mb-3 d-none" id="firstnameGroup">
                                        <label for="firstname" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                                    </div>

                                    <!-- Last Name (Hidden initially) -->
                                    <div class="mb-3 d-none" id="lastnameGroup">
                                        <label for="lastname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                                    </div>

                                    <!-- Contact -->
                                    <div class="mb-3">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                                            required pattern="[0-9]{11}" maxlength="11"
                                            value="<?php echo htmlspecialchars($user_data['contact_number']); ?>"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,11)" readonly>
                                    </div>

                                    <!-- Email -->
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required
                                            pattern="[a-zA-Z0-9._%+-]+@gmail\.com$"
                                            value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                                    </div>

                                    <!-- Address -->
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" name="address" id="address" rows="2" required readonly><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-warning me-2" id="editBtn" onclick="enableEdit()">Edit</button>
                                        <button type="submit" class="btn btn-success d-none me-3" id="updateBtn">Update Profile</button>
                                        <button type="button" class="btn btn-secondary d-none" id="cancelBtn" onclick="cancelEdit()">Cancel</button>
                                    </div>
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
        function checkForm() {
            const contact = document.getElementById("contact_number").value;
            if (!/^09\d{9}$/.test(contact)) {
                document.getElementById("contactError").style.display = 'block';
                return false;
            }
            return true;
        }

        function enableEdit() {
            const fullName = document.getElementById("fullname").value.trim();
            const parts = fullName.split(" ");
            const first = parts.slice(0, -1).join(" ") || "";
            const last = parts.slice(-1).join(" ") || "";

            document.getElementById("fullnameGroup").classList.add("d-none");
            document.getElementById("firstnameGroup").classList.remove("d-none");
            document.getElementById("lastnameGroup").classList.remove("d-none");

            document.getElementById("firstname").value = first;
            document.getElementById("lastname").value = last;

            ["email", "contact_number", "address"].forEach(id =>
                document.getElementById(id).removeAttribute("readonly"));

            // ✅ Show profile picture upload input when editing
            document.getElementById("profilePicGroup").classList.remove("d-none");

            document.getElementById("editBtn").classList.add("d-none");
            document.getElementById("updateBtn").classList.remove("d-none");
            document.getElementById("cancelBtn").classList.remove("d-none");
        }

            function cancelEdit() {
                location.reload();
            }

            // ✅ Live preview of selected image
            document.getElementById("profile_pic")?.addEventListener('change', function (event) {
                const file = event.target.files[0];
                if (file) {
                    document.getElementById("profilePreview").src = URL.createObjectURL(file);
                }
            });

            // ✅ Handle form submission with image upload
            $('#profileForm').submit(function (e) {
                e.preventDefault();

                if (!checkForm()) return;

                const formData = new FormData(this); // Collect full form including file input

                Swal.fire({
                    title: "Are you sure?",
                    text: "Do you want to update your profile?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, update it!",
                    cancelButtonText: "Cancel"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'update_profile.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                const data = JSON.parse(response);
                                if (data.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Profile updated successfully!',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        window.location.href = 'profile.php';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Update failed!',
                                        text: data.error || 'Unknown error.',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Something went wrong!',
                                    text: 'Please try again later.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });
        </script>
    </body>
</html>

