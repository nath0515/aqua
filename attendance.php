<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $role_id = $_SESSION['role_id'];
    if($role_id == 1){
        header("Location: index.php");
    }else if ($role_id == 2){
        header("Location: home.php");
    }

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get notifications using helper for consistency
    require 'notification_helper.php';
    $notifications = getNotifications($conn, $user_id, $role_id);
    $unread_count = $notifications['unread_count'];
    
    // Check for notification success message
    $notification_success = isset($_GET['notifications_marked']) ? (int)$_GET['notifications_marked'] : 0;

    // Get filter parameters
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $date_error = '';
    
    // Validate date range
    if (!empty($start_date) && !empty($end_date)) {
        if ($start_date > $end_date) {
            $date_error = 'Error: Start date cannot be after end date. Please select a valid date range.';
            $start_date = '';
            $end_date = '';
        }
    }
    
    // Build the attendance query with date filtering
    $attendance_sql = "
        SELECT DATE(in_time) AS date, 
            TIME(in_time) AS time_in, 
            TIME(out_time) AS time_out
        FROM attendance
        WHERE user_id = :user_id
    ";
    
    $params = [':user_id' => $user_id];
    
    if (!empty($start_date) && !empty($end_date) && empty($date_error)) {
        $attendance_sql .= " AND DATE(in_time) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    }
    
    $attendance_sql .= " ORDER BY in_time DESC";
    
    $stmt = $conn->prepare($attendance_sql);
    $stmt->execute($params);
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);  

    $salary_per_day = 500; // Daily rate
    $hourly_rate = $salary_per_day / 8; // Assuming 8-hour work day
    $total_salary = 0;
    
    // Calculate actual salary based on hours worked
    foreach ($attendance_data as $record) {
        if (!empty($record['time_in']) && !empty($record['time_out'])) {
            $time_in = strtotime($record['time_in']);
            $time_out = strtotime($record['time_out']);
            $hours_worked = ($time_out - $time_in) / 3600; // Convert seconds to hours
            $daily_salary = $hours_worked * $hourly_rate;
            $total_salary += $daily_salary;
        }
    }

    $stmt = $conn->prepare("SELECT * FROM rider_status WHERE DATE(date) = :date AND user_id = :user_id");
    $stmt->execute([':date' => $today, ':user_id' => $user_id]);
    if ($stmt->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO rider_status (user_id, status, time_in, time_out, date) VALUES (:user_id, 0, 0, 0, :date)");
        $stmt->execute([':user_id' => $user_id, ':date' => $today]);
        header('Location: attendance.php');
        exit();
    }
    
    $stmt = $conn->prepare("SELECT * FROM rider_status WHERE user_id = :user_id ORDER BY date DESC");
    $stmt->execute([':user_id' => $user_id]);
    $rider_status_data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Orders</title>
        <link rel="manifest" href="/manifest.json">
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
            <!-- Navbar Brand-->
           <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/tagiled2.png" alt="AquaDrop Logo" style="width: 220px; height: 60px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                 <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
                        <?php echo renderNotificationBadge($unread_count); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li class="dropdown-header fw-bold text-dark">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <?php echo renderNotificationDropdown($notifications['recent_notifications'], $unread_count, $user_id, $role_id); ?>
                        <li><a class="dropdown-item text-center text-muted small" href="activitylogsrider.php">View all notifications</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle mt-1" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <li><a id="installBtn" class="dropdown-item" style="display: none;">Install AquaDrop</a></li>
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
                        <div id="loadingOverlay">
                            <div class="spinner"></div>
                        </div>
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
                            <a class="nav-link" href="rider_ratings.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-star"></i></div>
                            My Ratings
                            </a>
                                                    <a class="nav-link active" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                        </a>
                            <a class="nav-link" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
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
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Attendance</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Attendance</li>
                        </ol>
                        <?php if (!empty($date_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($date_error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="attendance.php" method="GET">
                            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
                                <div>
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div>
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div>
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <?php if (!empty($start_date) && !empty($end_date)): ?>
                                        <a href="attendance.php" class="btn btn-secondary">Clear Filter</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                        <button id="downloadPDF" class="btn btn-danger mb-3">
                            <i class="fas fa-file-pdf me-2"></i> Download Attendance as PDF
                        </button>
                        <button id="printAttendance" class="btn btn-secondary mb-3 ms-2">
                            <i class="fas fa-print me-2"></i> Print Attendance
                        </button>
                        <button id="previewReport" class="btn btn-warning mb-3 ms-2">
                            <i class="fas fa-eye me-2"></i> Print Preview
                        </button>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-clock me-1"></i>
                                Attendance Records
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Date</th>  
                                            <th>Time-In</th>
                                            <th>Time-Out</th>
                                            <th>Daily Salary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attendance_data)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    <?php if (!empty($start_date) && !empty($end_date)): ?>
                                                        No attendance records found for the selected date range (<?= date('M j', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?>)
                                                    <?php else: ?>
                                                        No attendance records found
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($attendance_data as $row): ?>
                                                <tr>
                                                    <td><?= date('F j, Y', strtotime($row['date'])) ?></td>
                                                    <td>
                                                    <?php
                                                        $timeInDisplay = ($row['time_in'] === '00:00:00' || $row['time_in'] === '0000-00-00 00:00:00' || empty($row['time_in'])) 
                                                            ? 'Not yet timed in' 
                                                            : htmlspecialchars($row['time_in']);
                                                        ?>
                                                    <?= $timeInDisplay ?>
                                                    </td>
                                                    <td>
                                                    <?php
                                                        $timeOutDisplay = ($row['time_out'] === '00:00:00' || $row['time_out'] === '0000-00-00 00:00:00' || empty($row['time_out'])) 
                                                            ? 'Not yet timed out' 
                                                            : htmlspecialchars($row['time_out']);
                                                    ?>
                                                    <?= $timeOutDisplay ?>
                                                    </td>
                                                    <?php
                                                        $daily_salary = 0;
                                                        if (!empty($row['time_in']) && !empty($row['time_out'])) {
                                                            $time_in = strtotime($row['time_in']);
                                                            $time_out = strtotime($row['time_out']);
                                                            $hours_worked = ($time_out - $time_in) / 3600;
                                                            $daily_salary = $hours_worked * $hourly_rate;
                                                        }
                                                    ?>
                                                    <td>₱<?= number_format($daily_salary, 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <tr class="table-success fw-bold">
                                            <td colspan="3"></td> 
                                            <td colspan="2"></td> 
                                            <td style="text-align: right;"><strong>Total Salary:</strong></td>
                                            <td><strong>₱<?= number_format($total_salary, 2) ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <div id="printPreviewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background: rgba(0,0,0,0.5); overflow:auto; z-index:9999;">
            <div style="background:#fff; margin:30px auto; padding:20px; width:90%; max-width:1000px; position:relative;">
                <div id="previewContent"></div>
                <button id="closePreview" style="position:absolute; top:10px; right:10px;">Close</button>
                <button id="printPreviewBtn" style="position:absolute; top:10px; right:80px;">Print</button>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

        <script>
        document.getElementById("downloadPDF").addEventListener("click", function () {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF();
            let y = 15;

            function pageBreak() {
                if (y > 280) {
                    pdf.addPage();
                    y = 20;
                }
            }

            // ============================
            // HEADER
            // ============================
            pdf.setFont("helvetica", "bold");
            pdf.setFontSize(18);
            pdf.text("AquaDrop Water Refilling Station", 105, y, { align: "center" });
            y += 8;

            pdf.setFontSize(14);
            pdf.setFont("helvetica", "normal");
            pdf.text("Employee Attendance Report", 105, y, { align: "center" });
            y += 7;

            pdf.setFontSize(11);
            pdf.text(
                "Period: <?= !empty($start_date) ? date('F j, Y', strtotime($start_date)) : 'Start' ?> - <?= !empty($end_date) ? date('F j, Y', strtotime($end_date)) : 'End' ?>",
                105,
                y,
                { align: "center" }
            );
            y += 15;

            // ============================
            // TABLE HEADER
            // ============================
            pdf.setFont("helvetica", "bold");
            pdf.setFontSize(12);

            pdf.text("Date", 14, y);
            pdf.text("Time-In", 70, y);
            pdf.text("Time-Out", 110, y);
            pdf.text("Daily Salary (Php)", 190, y, { align: "right" });

            y += 8;
            pdf.setFont("helvetica", "normal");
            pdf.setFontSize(11);

            // ============================
            // TABLE ROWS (PHP TO JS)
            // ============================
            <?php foreach ($attendance_data as $row): 
                $date = date('F j, Y', strtotime($row['date']));
                $tin = ($row['time_in'] == "00:00:00" || empty($row['time_in'])) ? "Not timed in" : $row['time_in'];
                $tout = ($row['time_out'] == "00:00:00" || empty($row['time_out'])) ? "Not timed out" : $row['time_out'];

                // Salary calculation
                $daily_salary = 0;
                if (!empty($row['time_in']) && !empty($row['time_out'])) {
                    $daily_salary = ((strtotime($row['time_out']) - strtotime($row['time_in'])) / 3600) * $hourly_rate;
                }
            ?>

                pdf.text("<?= $date ?>", 14, y);
                pdf.text("<?= $tin ?>", 70, y);
                pdf.text("<?= $tout ?>", 110, y);
                pdf.text("<?= number_format($daily_salary, 2) ?>", 190, y, { align: "right" });

                y += 7;
                pageBreak();
            <?php endforeach; ?>

            // ============================
            // TOTAL SALARY
            // ============================
            y += 5;
            pdf.setFont("helvetica", "bold");
            pdf.text("Total Salary: Php <?= number_format($total_salary, 2) ?>", 190, y, { align: "right" });
            y += 12;

            // ============================
            // FOOTER
            // ============================
            pdf.setFontSize(10);
            pdf.setFont("helvetica", "italic");

            pdf.text(
                "Generated by: <?= $user_data['firstname'] . ' ' . $user_data['lastname'] ?>",
                105,
                285,
                { align: "center" }
            );

            pdf.text(
                "Generated by AquaDrop Water Ordering System",
                105,
                292,
                { align: "center" }
            );

            // SAVE PDF
            pdf.save("Attendance_Report_<?= date('Ymd') ?>.pdf");
        });
        </script>
        <script>
        document.getElementById("printAttendance").addEventListener("click", function () {
            let printWindow = window.open('', '', 'width=900,height=700');
            
            printWindow.document.write('<html><head><title>Attendance Report</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
            printWindow.document.write('th, td { border: 1px solid #000; padding: 8px; text-align: left; }');
            printWindow.document.write('th { background-color: #f2f2f2; }');
            printWindow.document.write('h2, h3, p { margin: 5px 0; }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');

            // HEADER
            printWindow.document.write('<h2 style="text-align:center;">AquaDrop Water Refilling Station</h2>');
            printWindow.document.write('<h3 style="text-align:center;">Employee Attendance Report</h3>');
            printWindow.document.write('<p style="text-align:center;">Period: <?= !empty($start_date) ? date("F j, Y", strtotime($start_date)) : "Start" ?> - <?= !empty($end_date) ? date("F j, Y", strtotime($end_date)) : "End" ?></p>');

            // TABLE
            printWindow.document.write('<table>');
            printWindow.document.write('<thead><tr>');
            printWindow.document.write('<th>Date</th><th>Time-In</th><th>Time-Out</th><th>Daily Salary (Php)</th>');
            printWindow.document.write('</tr></thead>');
            printWindow.document.write('<tbody>');

            <?php foreach ($attendance_data as $row): 
                $date = date('F j, Y', strtotime($row['date']));
                $tin = ($row['time_in'] == "00:00:00" || empty($row['time_in'])) ? "Not timed in" : $row['time_in'];
                $tout = ($row['time_out'] == "00:00:00" || empty($row['time_out'])) ? "Not timed out" : $row['time_out'];
                $daily_salary = 0;
                if (!empty($row['time_in']) && !empty($row['time_out'])) {
                    $daily_salary = ((strtotime($row['time_out']) - strtotime($row['time_in'])) / 3600) * $hourly_rate;
                }
            ?>
                printWindow.document.write('<tr>');
                printWindow.document.write('<td><?= $date ?></td>');
                printWindow.document.write('<td><?= $tin ?></td>');
                printWindow.document.write('<td><?= $tout ?></td>');
                printWindow.document.write('<td style="text-align:right;"><?= number_format($daily_salary, 2) ?></td>');
                printWindow.document.write('</tr>');
            <?php endforeach; ?>

            printWindow.document.write('</tbody></table>');

            // TOTAL SALARY
            printWindow.document.write('<h3 style="text-align:right; margin-top:20px;">Total Salary: Php <?= number_format($total_salary, 2) ?></h3>');

            // FOOTER
            printWindow.document.write('<p style="text-align:center; font-style:italic;">Generated by: <?= $user_data["firstname"] . " " . $user_data["lastname"] ?></p>');
            printWindow.document.write('<p style="text-align:center; font-style:italic;">Generated by AquaDrop Water Ordering System</p>');

            printWindow.document.write('</body></html>');

            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        });
        </script>

        <script>
        document.getElementById("previewReport").addEventListener("click", function () {
            const preview = document.getElementById("previewContent");
            preview.innerHTML = ''; // Clear previous content

            // HEADER
            preview.innerHTML += '<h2 style="text-align:center;">AquaDrop Water Refilling Station</h2>';
            preview.innerHTML += '<h3 style="text-align:center;">Employee Attendance Report</h3>';
            preview.innerHTML += '<p style="text-align:center;">Period: <?= !empty($start_date) ? date("F j, Y", strtotime($start_date)) : "Start" ?> - <?= !empty($end_date) ? date("F j, Y", strtotime($end_date)) : "End" ?></p>';

            // TABLE
            let table = '<table style="width:100%; border-collapse: collapse;">';
            table += '<thead><tr>';
            table += '<th style="border:1px solid #000; padding:5px;">Date</th>';
            table += '<th style="border:1px solid #000; padding:5px;">Time-In</th>';
            table += '<th style="border:1px solid #000; padding:5px;">Time-Out</th>';
            table += '<th style="border:1px solid #000; padding:5px; text-align:right;">Daily Salary (Php)</th>';
            table += '</tr></thead><tbody>';

            <?php foreach ($attendance_data as $row): 
                $date = date('F j, Y', strtotime($row['date']));
                $tin = ($row['time_in'] == "00:00:00" || empty($row['time_in'])) ? "Not timed in" : $row['time_in'];
                $tout = ($row['time_out'] == "00:00:00" || empty($row['time_out'])) ? "Not timed out" : $row['time_out'];
                $daily_salary = 0;
                if (!empty($row['time_in']) && !empty($row['time_out'])) {
                    $daily_salary = ((strtotime($row['time_out']) - strtotime($row['time_in'])) / 3600) * $hourly_rate;
                }
            ?>
                table += '<tr>';
                table += '<td style="border:1px solid #000; padding:5px;"><?= $date ?></td>';
                table += '<td style="border:1px solid #000; padding:5px;"><?= $tin ?></td>';
                table += '<td style="border:1px solid #000; padding:5px;"><?= $tout ?></td>';
                table += '<td style="border:1px solid #000; padding:5px; text-align:right;"><?= number_format($daily_salary,2) ?></td>';
                table += '</tr>';
            <?php endforeach; ?>

            table += '</tbody></table>';
            preview.innerHTML += table;

            // TOTAL
            preview.innerHTML += '<h3 style="text-align:right; margin-top:20px;">Total Salary: Php <?= number_format($total_salary,2) ?></h3>';

            // FOOTER
            preview.innerHTML += '<p style="text-align:center; font-style:italic;">Generated by: <?= $user_data["firstname"] . " " . $user_data["lastname"] ?></p>';
            preview.innerHTML += '<p style="text-align:center; font-style:italic;">Generated by AquaDrop Water Ordering System</p>';

            // Show modal
            document.getElementById("printPreviewModal").style.display = "block";
        });

        // Close modal
        document.getElementById("closePreview").addEventListener("click", function() {
            document.getElementById("printPreviewModal").style.display = "none";
        });

        // Print from modal
        document.getElementById("printPreviewBtn").addEventListener("click", function() {
            const printContents = document.getElementById("previewContent").innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload(); // reload to restore original JS functionality
        });
        </script>


        <script>
            // Date validation
            document.addEventListener('DOMContentLoaded', function() {
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const form = document.querySelector('form[action="attendance.php"]');
                
                function validateDates() {
                    const startDate = startDateInput.value;
                    const endDate = endDateInput.value;
                    
                    if (startDate && endDate && startDate > endDate) {
                        alert('Error: Start date cannot be after end date. Please select a valid date range.');
                        return false;
                    }
                    return true;
                }
                
                form.addEventListener('submit', function(e) {
                    if (!validateDates()) {
                        e.preventDefault();
                    }
                });
                
                // Real-time validation
                endDateInput.addEventListener('change', function() {
                    if (startDateInput.value && this.value && startDateInput.value > this.value) {
                        this.setCustomValidity('End date must be after start date');
                    } else {
                        this.setCustomValidity('');
                    }
                });
                
                startDateInput.addEventListener('change', function() {
                    if (endDateInput.value && this.value && this.value > endDateInput.value) {
                        endDateInput.setCustomValidity('End date must be after start date');
                    } else {
                        endDateInput.setCustomValidity('');
                    }
                });
            });
            
            let deferredPrompt;

            // Listen for the beforeinstallprompt event
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault(); // Prevent automatic prompt
                deferredPrompt = e; // Save the event for later
                document.getElementById('installBtn').style.display = 'inline-block'; // Show the button
            });

            // Install button click handler
            document.getElementById('installBtn').addEventListener('click', async () => {
                if (!deferredPrompt) return;

                document.getElementById('loadingOverlay').style.display = 'flex';

                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;

                document.getElementById('loadingOverlay').style.display = 'none';

                if (outcome === 'accepted') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Installation Complete',
                        text: 'AquaDrop has been successfully installed!',
                        confirmButtonColor: '#0077b6'
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Installation Cancelled',
                        text: 'You can install AquaDrop anytime!',
                        confirmButtonColor: '#0077b6'
                    });
                }

                deferredPrompt = null;
                document.getElementById('installBtn').style.display = 'none';
            });
        </script>
        <!-- PWA: Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js') // ✅ Root-level path
                    .then(reg => console.log('✅ Service Worker registered:', reg))
                    .catch(err => console.error('❌ Service Worker registration failed:', err));
            }
        </script>
            <?php if ($notification_success > 0): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Notifications Marked as Read!',
                    text: '<?php echo $notification_success; ?> notification(s) have been marked as read.',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>
        <?php endif; ?>
</body>
</html>
