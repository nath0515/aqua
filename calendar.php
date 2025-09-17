<?php
session_start();
require 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a rider
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Manila'); // Set to Philippine timezone
$today = date('Y-m-d');
$current_month = isset($_GET['month']) ? $_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get user details
$stmt = $conn->prepare("SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number 
    FROM users u 
    LEFT JOIN user_details ud ON u.user_id = ud.user_id 
    WHERE u.user_id = :user_id");
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

// Get today's attendance data
$stmt = $conn->prepare("SELECT in_time, out_time FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all attendance data for the current month
$stmt = $conn->prepare("SELECT DATE(in_time) as date, in_time, out_time FROM attendance WHERE user_id = :user_id AND MONTH(in_time) = :month AND YEAR(in_time) = :year");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':month', $current_month);
$stmt->bindParam(':year', $current_year);
$stmt->execute();
$monthly_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a lookup array for quick access
$attendance_lookup = [];
foreach ($monthly_attendance as $record) {
    $attendance_lookup[$record['date']] = $record;
}

// Generate calendar
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$first_day_of_week = date('w', $first_day);
$month_name = date('F Y', $first_day);

// Navigation
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Calendar - AquaDrop</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 0 5px;
        }
        .calendar-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        .calendar-nav {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .calendar-nav a {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
        }
        .calendar-nav a:hover {
            background: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .calendar-day-header {
            background: #ffffff;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .calendar-day {
            background: white;
            padding: 8px 6px;
            aspect-ratio: 1;
            border-right: 1px solid #f1f3f4;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease;
            min-height: 100px;
        }
        .calendar-day:hover {
            background: #f8f9fa;
        }
        .calendar-day.empty {
            background: #fafbfc;
        }
        .calendar-day.today {
            background: #fff3cd;
            border: 2px solid #ffc107;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
        }
        .day-number {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
            color: #212529;
            text-decoration: underline;
            text-decoration-color: #e9ecef;
            text-underline-offset: 2px;
        }
        .toggle-row {
            display: flex;
            align-items: center;
            margin: 4px 0;
            font-size: 11px;
            white-space: nowrap;
            gap: 4px;
        }
        .toggle-label {
            flex-shrink: 0;
            width: 45px;
            font-weight: 500;
            white-space: nowrap;
            color: #495057;
        }
        .toggle-time {
            color: #6c757d;
            width: 50px;
            text-align: left;
            flex-shrink: 0;
            font-weight: 500;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            /* Mobile Header */
            .calendar-header {
                flex-direction: column;
                gap: 15px;
                padding: 20px 15px;
                margin-bottom: 20px;
            }
            
            .calendar-header h2 {
                font-size: 24px;
                text-align: center;
            }
            
            .calendar-nav {
                width: 100%;
                justify-content: space-between;
                gap: 10px;
            }
            
            .calendar-nav a {
                padding: 12px 20px;
                font-size: 16px;
                flex: 1;
                text-align: center;
            }
            
            /* Mobile Calendar Grid - Make only the grid scrollable */
            .calendar-grid {
                grid-template-columns: repeat(7, 120px);
                gap: 1px;
                min-width: 840px; /* 7 columns × 120px */
                margin: 0;
                border-radius: 8px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .calendar-day-header {
                padding: 12px 8px;
                font-size: 12px;
                min-width: 120px;
            }
            
            .calendar-day {
                min-height: 120px;
                padding: 8px 6px;
                min-width: 120px;
            }
            
            .day-number {
                font-size: 14px;
                margin-bottom: 8px;
            }
            
            /* Mobile Toggle Switches - Keep same as desktop */
            .toggle-row {
                margin: 4px 0;
                gap: 4px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .toggle-label {
                width: 45px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .toggle-time {
                width: 50px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .toggle-switch {
                width: 35px;
                height: 18px;
                flex-shrink: 0;
            }
            
            .toggle-slider:before {
                height: 14px;
                width: 14px;
            }
            
            /* Mobile Sidebar */
            #layoutSidenav_nav {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            #layoutSidenav_nav.show {
                transform: translateX(0);
            }
            
            .sb-topnav {
                padding: 0 15px;
            }
            
            /* Mobile Modal */
            .modal-content {
                margin: 20px;
                border-radius: 15px;
            }
            
            /* Floating action button for mobile */
            .mobile-fab {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                background: linear-gradient(135deg, #0077b6 0%, #005a8b 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 24px;
                box-shadow: 0 4px 15px rgba(0, 119, 182, 0.3);
                z-index: 1000;
                transition: all 0.3s ease;
            }
            
            .mobile-fab:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(0, 119, 182, 0.4);
            }
        }
        
        /* Extra small devices */
        @media (max-width: 480px) {
            .calendar-day {
                min-height: 100px;
                padding: 6px 2px;
            }
            
            .day-number {
                font-size: 12px;
            }
            
            .toggle-label {
                width: 45px;
                font-size: 10px;
            }
            
            .toggle-time {
                width: 50px;
                font-size: 10px;
            }
            
            .calendar-header h2 {
                font-size: 20px;
            }
            
            .calendar-nav a {
                padding: 10px 15px;
                font-size: 14px;
            }
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 35px;
            height: 18px;
            flex-shrink: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e9ecef;
            transition: .3s;
            border-radius: 20px;
            z-index: 1;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            z-index: 2;
        }
        input:checked + .toggle-slider {
            background-color: #0d6efd;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        .toggle-switch.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .toggle-switch.disabled input {
            cursor: not-allowed;
        }
        .toggle-switch.disabled .toggle-slider {
            cursor: not-allowed;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-proceed {
            background: #28a745;
            color: white;
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
        <a class="navbar-brand ps-3" href="index.php">
            <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
        </a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
            <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-5"></i>
                        <?php if ($unread_count > 0): ?>
                            <span id="notificationBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">unread notifications</span>
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
                        <div class="sb-sidenav-menu-heading">MENU</div>
                        <a class="nav-link" href="riderdashboardclosed.php">
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
                        <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                        </a>
                        <a class="nav-link active" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
                        </a>
                    </div>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Calendar</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="riderdashboardclosed.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Calendar</li>
                    </ol>
                    
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h2><?php echo $month_name; ?></h2>
                            <div class="calendar-nav">
                                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-primary">Previous</a>
                                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-primary">Next</a>
                            </div>
                        </div>
                        
                        <div class="calendar-grid">
                            <div class="calendar-day-header">Sun</div>
                            <div class="calendar-day-header">Mon</div>
                            <div class="calendar-day-header">Tue</div>
                            <div class="calendar-day-header">Wed</div>
                            <div class="calendar-day-header">Thu</div>
                            <div class="calendar-day-header">Fri</div>
                            <div class="calendar-day-header">Sat</div>
                            
                            <?php
                            // Empty cells for days before the first day of the month
                            for ($i = 0; $i < $first_day_of_week; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                            
                            // Days of the month
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $current_date = date('Y-m-d', mktime(0, 0, 0, $current_month, $day, $current_year));
                                $is_today = ($current_date === $today);
                                $is_past = ($current_date < $today);
                                $is_future = ($current_date > $today);
                                
                                // Check if there's attendance data for this date
                                $day_attendance = isset($attendance_lookup[$current_date]) ? $attendance_lookup[$current_date] : null;
                                $has_attendance = $day_attendance !== null;
                                
                                echo '<div class="calendar-day' . ($is_today ? ' today' : '') . '">';
                                echo '<div class="day-number">' . $day . '</div>';
                                
                                if ($is_today) {
                                    // Today - show interactive toggles
                                    $has_logged_in = $today_attendance && $today_attendance['in_time'];
                                    $has_logged_out = $today_attendance && $today_attendance['out_time'];
                                    $login_time = $has_logged_in ? date('g:i A', strtotime($today_attendance['in_time'])) : '';
                                    $logout_time = $has_logged_out ? date('g:i A', strtotime($today_attendance['out_time'])) : '';
                                    
                                    // Time In toggle
                                    echo '<div class="toggle-row">';
                                    echo '<span class="toggle-label">Time In</span>';
                                    echo '<span class="toggle-time" id="login-time"' . ($has_logged_in ? '' : ' style="display: none;"') . '>' . $login_time . '</span>';
                                    echo '<label class="toggle-switch' . ($has_logged_in ? ' disabled' : '') . '">';
                                    echo '<input type="checkbox" id="login-toggle" onclick="event.preventDefault(); console.log(\'Time In toggle clicked!\'); toggleAttendance(\'login\')"' . ($has_logged_in ? ' checked disabled' : '') . '>';
                                    echo '<span class="toggle-slider"></span>';
                                    echo '</label>';
                                    echo '</div>';
                                    
                                    // Time Out toggle
                                    echo '<div class="toggle-row">';
                                    echo '<span class="toggle-label">Time Out</span>';
                                    echo '<span class="toggle-time" id="logout-time"' . ($has_logged_out ? '' : ' style="display: none;"') . '>' . $logout_time . '</span>';
                                    echo '<label class="toggle-switch' . ($has_logged_out ? ' disabled' : '') . '">';
                                    echo '<input type="checkbox" id="logout-toggle" onclick="event.preventDefault(); console.log(\'Time Out toggle clicked!\'); console.log(\'hasLoggedIn: ' . ($has_logged_in ? 'true' : 'false') . '\'); console.log(\'hasLoggedOut: ' . ($has_logged_out ? 'true' : 'false') . '\'); toggleAttendance(\'logout\')"' . ($has_logged_out ? ' checked disabled' : (!$has_logged_in ? ' disabled' : '')) . '>';
                                    echo '<span class="toggle-slider"></span>';
                                    echo '</label>';
                                    echo '</div>';
                                } elseif ($has_attendance && $is_past) {
                                    // Past days with attendance - show read-only times
                                    $login_time = $day_attendance['in_time'] ? date('g:i A', strtotime($day_attendance['in_time'])) : '';
                                    $logout_time = $day_attendance['out_time'] ? date('g:i A', strtotime($day_attendance['out_time'])) : '';
                                    
                                    echo '<div class="toggle-row">';
                                    echo '<span class="toggle-label">Time In</span>';
                                    echo '<span class="toggle-time">' . $login_time . '</span>';
                                    echo '<label class="toggle-switch disabled">';
                                    echo '<input type="checkbox" checked disabled>';
                                    echo '<span class="toggle-slider"></span>';
                                    echo '</label>';
                                    echo '</div>';
                                    
                                    if ($logout_time) {
                                        echo '<div class="toggle-row">';
                                        echo '<span class="toggle-label">Time Out</span>';
                                        echo '<span class="toggle-time">' . $logout_time . '</span>';
                                        echo '<label class="toggle-switch disabled">';
                                        echo '<input type="checkbox" checked disabled>';
                                        echo '<span class="toggle-slider"></span>';
                                        echo '</label>';
                                        echo '</div>';
                                    }
                                }
                                
                                echo '</div>';
                            }

                            // Empty cells for days after the last day of the month
                            $last_day_of_week = date('w', mktime(0, 0, 0, $current_month, $days_in_month, $current_year));
                            for ($i = $last_day_of_week; $i < 6; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalTitle">Proceed with action?</h5>
            </div>
            <div class="modal-body">
                <p id="modalMessage">This action cannot be undone.</p>
            </div>
            <div class="modal-buttons">
                <button class="btn-proceed" onclick="confirmAction()">PROCEED</button>
                <button class="btn-cancel" onclick="closeModal()">NO</button>
            </div>
        </div>
    </div>

    <!-- Mobile Floating Action Button -->
    <div class="mobile-fab d-md-none" onclick="showMobileMenu()">
        <i class="fas fa-bars"></i>
    </div>

    <script>
        console.log('Calendar JavaScript loaded!');
        
        // Mobile-specific functionality
        let isMobile = window.innerWidth <= 768;
        
        // Mobile menu toggle
        function showMobileMenu() {
            const sidebar = document.getElementById('layoutSidenav_nav');
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (isMobile) {
                const sidebar = document.getElementById('layoutSidenav_nav');
                const fab = document.querySelector('.mobile-fab');
                if (sidebar && !sidebar.contains(e.target) && !fab.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        // Enhanced toggle function
        function toggleAttendance(action) {
            console.log('toggleAttendance called with action:', action);
            const toggle = document.getElementById(action + '-toggle');
            console.log('Toggle element:', toggle);
            
            if (!toggle) {
                console.error('Toggle element not found');
                return;
            }
            
            currentAction = action;
            currentToggle = toggle;
            
            let message = '';
            if (action === 'login') {
                message = 'Do you want to Time In?';
            } else if (action === 'logout') {
                message = 'Do you want to Time Out?';
            }
            
            document.getElementById('modalTitle').textContent = 'Confirm Action';
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmationModal').style.display = 'block';
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            isMobile = window.innerWidth <= 768;
        });
    </script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentAction = '';
        let currentToggle = '';

        function toggleAttendance(action) {
            console.log('toggleAttendance called with action:', action);
            const toggle = document.getElementById(action + '-toggle');
            console.log('Toggle element:', toggle);
            
            // Check if this action has already been performed today
            const hasLoggedIn = <?php echo ($today_attendance && $today_attendance['in_time']) ? 'true' : 'false'; ?>;
            const hasLoggedOut = <?php echo ($today_attendance && $today_attendance['out_time']) ? 'true' : 'false'; ?>;
            
            console.log('hasLoggedIn:', hasLoggedIn);
            console.log('hasLoggedOut:', hasLoggedOut);
            
            if (action === 'login' && hasLoggedIn) {
                alert('You have already timed in today.');
                return;
            }
            
            if (action === 'logout' && hasLoggedOut) {
                alert('You have already timed out today.');
                return;
            }
            
            // For logout, check if user has logged in first
            if (action === 'logout' && !hasLoggedIn) {
                alert('You must time in first before timing out.');
                return;
            }
            
            console.log('All checks passed, showing modal');
            
            currentAction = action;
            currentToggle = action + '-toggle';
            
            const modal = document.getElementById('confirmationModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            
            console.log('Modal element:', modal);
            console.log('Modal title element:', modalTitle);
            console.log('Modal message element:', modalMessage);
            
            const actionText = action.charAt(0).toUpperCase() + action.slice(1);
            modalTitle.textContent = `Proceed with ${actionText}?`;
            modalMessage.textContent = `Are you sure you want to ${action}? This action will be recorded with the current time.`;
            
            modal.style.display = 'block';
            console.log('Modal should be visible now');
        }

        function confirmAction() {
            const formData = new FormData();
            formData.append('action', currentAction);
            formData.append('date', '<?php echo $today; ?>');
            
            console.log('Sending request to process_calendar_action.php');
            
            fetch('process_calendar_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                if (data.success) {
                    // Update toggle state
                    const toggle = document.getElementById(currentToggle);
                    toggle.checked = true;
                    
                    // Show the time
                    const timeElement = document.getElementById(currentAction + '-time');
                    if (timeElement && data.time) {
                        timeElement.textContent = data.time;
                        timeElement.style.display = 'inline';
                    }
                    
                    // Close modal
                    closeModal();
                    
                    console.log('Action completed successfully');
                } else {
                    console.error('Server error:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function closeModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 