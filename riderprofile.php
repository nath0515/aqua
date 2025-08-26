<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");
    }else if ($role_id == 2){
        header("Location: home.php");
    }

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number,profile_pic,drivers_license FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fetch notifications for rider (delivery assignments + ratings)
    $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE (destination LIKE 'rider_orderdetails.php%' OR destination = 'rider_ratings.php') AND read_status = 0";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->execute();
    $unread_count = $notification_stmt->fetchColumn();

    $recent_notifications_sql = "SELECT * FROM activity_logs WHERE (destination LIKE 'rider_orderdetails.php%' OR destination = 'rider_ratings.php') ORDER BY date DESC LIMIT 3";
    $recent_notifications_stmt = $conn->prepare($recent_notifications_sql);
    $recent_notifications_stmt->execute();
    $recent_notifications = $recent_notifications_stmt->fetchAll();

    
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
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                <li class="nav-item me-2">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                </li>
                <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unread_count; ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <?php if (empty($recent_notifications)): ?>
                            <li><a class="dropdown-item text-muted" href="#">No notifications</a></li>
                        <?php else: ?>
                            <?php foreach($recent_notifications as $notification): ?>
                                <li><a class="dropdown-item" href="process_readnotification.php?id=<?php echo $notification['activitylogs_id']?>&destination=<?php echo $notification['destination']?>"><?php echo $notification['message'];?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="riderprofile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <?php 
                        // Commented out Off Duty toggle
                        /*
                        $sql = "SELECT status FROM rider_status WHERE user_id = :user_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $status_rider = $row ? $row['status'] : 0;
                        */
                        ?>
                        <!-- <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="return confirmToggle(event, <?= $status_rider ?>)">
                            <?php echo ($status_rider) ? 'Off Duty' : 'On Duty'; ?>
                        </a>
                        </li> -->
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
                            <a class="nav-link" href="riderdashboard.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Delivery Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="deliveryhistory.php">Delivered History</a>
                                    <a class="nav-link" href="ridermap.php">Maps</a>
                                </nav>
                            </div>
                            <a class="nav-link" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
                            </a>
                            <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                        </a>
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
                                <form id="profileForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
                                    <!-- Full Name (Read-only) -->
                                    <div class="mb-3 text-center">
                                        <?php 
                                        $profile_pic_path = "uploads/" . ($user_data['profile_pic'] ?? 'default.png');
                                        if (!empty($user_data['profile_pic']) && file_exists($profile_pic_path)): 
                                        ?>
                                            <img src="<?php echo $profile_pic_path; ?>" 
                                                alt="Profile Picture" 
                                                id="profilePreview"
                                                class="img-thumbnail rounded-circle" 
                                                style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-thumbnail rounded-circle d-flex align-items-center justify-content-center" 
                                                id="profilePreview"
                                                style="width: 150px; height: 150px; background-color: #f8f9fa; border: 2px solid #dee2e6;">
                                                <i class="fas fa-user text-primary" style="font-size: 60px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3 d-none" id="profilePicGroup">
                                        <label for="profile_pic" class="form-label">Change Profile Picture</label>
                                        <input type="file" class="form-control" name="profile_pic" id="profile_pic" accept="image/*">
                                    </div>
                                    <div class="mb-3" id="fullnameGroup">
                                        <label for="fullname" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="fullname" 
                                            value="<?php echo htmlspecialchars($user_data['firstname'] . ' ' . $user_data['lastname']); ?>" 
                                            readonly>
                                    </div>

                                    <div class="mb-3 text-center">
                                        <label class="form-label fw-bold">Driver's License</label><br>
                                        <?php 
                                        $license_path = "uploads/" . ($user_data['drivers_license'] ?? 'default_license.png');
                                        if (!empty($user_data['drivers_license']) && file_exists($license_path)): 
                                        ?>
                                            <img src="<?php echo $license_path; ?>" 
                                                alt="Driver's License" 
                                                class="img-thumbnail" 
                                                style="width: 300px; height: auto; object-fit: contain;">
                                        <?php else: ?>
                                            <div class="img-thumbnail d-flex align-items-center justify-content-center" 
                                                style="width: 300px; height: 200px; background-color: #f8f9fa; border: 2px solid #dee2e6;">
                                                <i class="fas fa-id-card text-primary" style="font-size: 80px;"></i>
                                            </div>
                                        <?php endif; ?>
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

            ["email", "contact_number"].forEach(id =>
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

                if (!checkForm()){
                    Swal.fire({
                    title: "Error",
                    text: "Contact Number is not valid",
                    icon: "warning",
                    confirmButtonText: "Ok"
                });
                 return;
                }

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
                                        window.location.href = 'riderprofile.php';
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

