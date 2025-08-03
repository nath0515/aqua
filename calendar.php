<?php
session_start();
require 'db.php';

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

// Get attendance data for current month
$stmt = $conn->prepare("SELECT DATE(in_time) as date, TIME(in_time) as login_time, TIME(out_time) as logout_time, status 
    FROM attendance 
    WHERE user_id = :user_id AND MONTH(in_time) = :month AND YEAR(in_time) = :year 
    ORDER BY in_time DESC");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':month', $current_month);
$stmt->bindParam(':year', $current_year);
$stmt->execute();
$attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create attendance lookup array
$attendance_lookup = [];
foreach ($attendance_data as $record) {
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
            min-height: 120px;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .calendar-day.empty {
            background: #f8f9fa;
        }
        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
        .calendar-day.past {
            background: #f5f5f5;
            color: #999;
        }
        .calendar-day.future {
            background: #f8f9fa;
            color: #ccc;
        }
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .attendance-info {
            font-size: 0.8em;
            color: #666;
        }
        .toggle-container {
            position: absolute;
            bottom: 5px;
            left: 5px;
            right: 5px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            font-size: 0.75em;
            gap: 5px;
        }
        .toggle-label {
            font-weight: bold;
            color: #333;
            flex-shrink: 0;
        }
        .toggle-time {
            color: #666;
            font-size: 0.7em;
            font-style: italic;
            flex-shrink: 0;
            margin-right: auto;
        }
        .toggle-switch {
            display: inline-block;
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 10px;
            position: relative;
            cursor: pointer;
            margin: 2px;
        }
        .toggle-switch.active {
            background: #28a745;
        }
        .toggle-switch.disabled {
            background: #e9ecef;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .toggle-switch.disabled .toggle-slider {
            background: #ccc;
        }
        .toggle-switch {
            flex-shrink: 0;
        }
        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle-switch.active .toggle-slider {
            transform: translateX(20px);
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
                        <a class="nav-link" href="deliveryhistory.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                            Delivery Management
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
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    <?php echo $user_data['firstname'] . ' ' . $user_data['lastname']; ?>
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
                                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-primary">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-secondary">
                                    Today
                                </a>
                                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-primary">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
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
                                $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                                $is_today = ($date === $today);
                                $is_past = ($date < $today);
                                $is_future = ($date > $today);
                                
                                $day_class = 'calendar-day';
                                if ($is_today) $day_class .= ' today';
                                elseif ($is_past) $day_class .= ' past';
                                elseif ($is_future) $day_class .= ' future';

                                $attendance = isset($attendance_lookup[$date]) ? $attendance_lookup[$date] : null;
                                
                                echo '<div class="' . $day_class . '" data-date="' . $date . '">';
                                echo '<div class="day-number">' . $day . '</div>';
                                

                                if ($is_today) {
                                    $has_logged_in = $attendance && $attendance['login_time'];
                                    $has_logged_out = $attendance && $attendance['logout_time'];
                                    
                                    echo '<div class="toggle-container">';
                                    
                                    // Login toggle row
                                    $login_title = $has_logged_in ? 'Already logged in' : 'Click to login';
                                    $login_time = $has_logged_in ? date('g:i A', strtotime($attendance['login_time'])) : '';
                                    echo '<div class="toggle-row">';
                                    echo '<span class="toggle-label">Log in:</span>';
                                    if ($login_time) echo '<span class="toggle-time">' . $login_time . '</span>';
                                    echo '<div class="toggle-switch ' . ($has_logged_in ? 'active' : '') . '" id="login-toggle" onclick="toggleAttendance(\'login\')" title="' . $login_title . '">';
                                    echo '<div class="toggle-slider"></div>';
                                    echo '</div>';
                                    echo '</div>';
                                    

                                    
                                    // Logout toggle row
                                    $logout_disabled = !$has_logged_in || $has_logged_out;
                                    $logout_title = $logout_disabled ? ($has_logged_out ? 'Already logged out' : 'Login first to logout') : 'Click to logout';
                                    $logout_time = $has_logged_out ? date('g:i A', strtotime($attendance['logout_time'])) : '';
                                    echo '<div class="toggle-row">';
                                    echo '<span class="toggle-label">Log out:</span>';
                                    if ($logout_time) echo '<span class="toggle-time">' . $logout_time . '</span>';
                                    echo '<div class="toggle-switch ' . ($has_logged_out ? 'active' : '') . ($logout_disabled ? ' disabled' : '') . '" id="logout-toggle" ' . ($logout_disabled ? '' : 'onclick="toggleAttendance(\'logout\')"') . ' title="' . $logout_title . '">';
                                    echo '<div class="toggle-slider"></div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '</div>';
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

    <script src="js/scripts.js"></script>
    <script>
        let currentAction = '';
        let currentToggle = '';

        function toggleAttendance(action) {
            currentAction = action;
            currentToggle = action + '-toggle';
            
            const modal = document.getElementById('confirmationModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            
            const actionText = action.charAt(0).toUpperCase() + action.slice(1);
            modalTitle.textContent = `Proceed with ${actionText}?`;
            modalMessage.textContent = `Are you sure you want to ${action}? This action will be recorded with the current time.`;
            
            modal.style.display = 'block';
        }

        function confirmAction() {
            const formData = new FormData();
            formData.append('action', currentAction);
            formData.append('date', '<?php echo $today; ?>');
            
            fetch('process_calendar_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update toggle state
                    const toggle = document.getElementById(currentToggle);
                    toggle.classList.add('active');
                    
                    // Refresh page to show updated time
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
            
            closeModal();
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