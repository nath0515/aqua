<?php 
require 'session.php';
require 'db.php';

// Fetch notifications for rider
$notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE destination LIKE 'rider_orderdetails.php%' AND read_status = 0";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->execute();
$unread_count = $notification_stmt->fetchColumn();

$recent_notifications_sql = "SELECT * FROM activity_logs WHERE destination LIKE 'rider_orderdetails.php%' ORDER BY date DESC LIMIT 3";
$recent_notifications_stmt = $conn->prepare($recent_notifications_sql);
$recent_notifications_stmt->execute();
$recent_notifications = $recent_notifications_stmt->fetchAll();

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Only riders can access this page
if($role_id != 3){
    header("Location: index.php");
    exit;
}

    // Get rider's ratings
    $sql = "SELECT r.*, o.order_id, o.date, o.amount, 
            ud.firstname, ud.lastname, ud.contact_number
            FROM order_ratings r
            JOIN orders o ON r.order_id = o.order_id
            JOIN user_details ud ON r.user_id = ud.user_id
            WHERE r.rider_id = :rider_id
            ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':rider_id', $user_id);
$stmt->execute();
$ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average ratings
$total_orders = count($ratings);
$avg_order_rating = 0;
$avg_rider_rating = 0;

if($total_orders > 0) {
    $total_order_rating = array_sum(array_column($ratings, 'order_rating'));
    $total_rider_rating = array_sum(array_column($ratings, 'rider_rating'));
    $avg_order_rating = round($total_order_rating / $total_orders, 1);
    $avg_rider_rating = round($total_rider_rating / $total_orders, 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>My Ratings - AquaDrop</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .rating-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .rating-summary {
            background: linear-gradient(135deg, #0077b6, #005a8b);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .rating-number {
            font-size: 3rem;
            font-weight: bold;
        }
        
        .customer-name {
            font-weight: 600;
            color: #0077b6;
        }
        
        .review-text {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
        <a class="navbar-brand ps-3" href="riderdashboard.php">
            <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
        </a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        
        <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
            <li class="nav-item dropdown">
                <a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-fw"></i>
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
                                                    <a class="nav-link" href="attendance.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Attendance
                            </a>
                        <a class="nav-link" href="calendar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Calendar
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
                <h1 class="mt-4">My Ratings & Reviews</h1>
                
                <!-- Rating Summary -->
                <div class="rating-summary">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="rating-number"><?php echo $total_orders; ?></div>
                            <div>Total Orders Rated</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rating-number"><?php echo $avg_order_rating; ?></div>
                            <div>Avg. Order Rating</div>
                        </div>
                        <div class="col-md-4">
                            <div class="rating-number"><?php echo $avg_rider_rating; ?></div>
                            <div>Avg. Rider Rating</div>
                        </div>
                    </div>
                </div>

                <!-- Individual Ratings -->
                <?php if(empty($ratings)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-star text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No ratings yet</h4>
                        <p class="text-muted">Complete more deliveries to receive customer ratings!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($ratings as $rating): ?>
                        <div class="rating-card">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="customer-name">
                                        <?php echo htmlspecialchars($rating['firstname'] . ' ' . $rating['lastname']); ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        Order #<?php echo $rating['order_id']; ?> • 
                                        <?php echo date('M d, Y', strtotime($rating['date'])); ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Order Amount:</strong> ₱<?php echo number_format($rating['amount'], 2); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="mb-2">
                                        <small class="text-muted">Order Rating:</small><br>
                                        <div class="stars">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $rating['order_rating'] ? '' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Your Rating:</small><br>
                                        <div class="stars">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $rating['rider_rating'] ? '' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if(!empty($rating['review_text'])): ?>
                                <div class="review-text">
                                    <i class="fas fa-quote-left me-2"></i>
                                    <?php echo htmlspecialchars($rating['review_text']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html> 