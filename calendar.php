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
            margin-bottom: 20px;
        }
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        .calendar-nav button {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .calendar-nav button:hover {
            background: #0b5ed7;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        .calendar-day-header {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
        }
        .calendar-day {
            background: white;
            padding: 10px;
            min-height: 80px;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }
        .calendar-day.empty {
            background: #f8f9fa;
        }
        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0;
            font-size: 12px;
            gap: 8px;
        }
        .toggle-time {
            margin-right: auto;
            color: #666;
            min-width: 60px;
            text-align: right;
            display: inline-block;
        }
        .toggle-label {
            flex-shrink: 0;
            min-width: 40px;
            font-weight: 500;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
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
            background-color: #ccc;
            transition: .3s;
            border-radius: 20px;
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
        }
        input:checked + .toggle-slider {
            background-color: #2196f3;
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
            <li class="nav-item dropdown">
                <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-fw"></i>
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
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar"></i></div>
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

    <script>
        console.log('Calendar JavaScript loaded!');
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