<?php
// Script to update all hardcoded notifications to real notifications

function updateFile($filename, $user_type) {
    if (!file_exists($filename)) {
        echo "❌ File not found: $filename\n";
        return false;
    }
    
    $content = file_get_contents($filename);
    
    // Add notification logic after session.php require
    $notification_logic = '';
    if ($user_type === 'rider') {
        $notification_logic = '
    // Fetch notifications for rider
    $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE destination = \'rider\' AND read_status = 0";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->execute();
    $unread_count = $notification_stmt->fetchColumn();

    $recent_notifications_sql = "SELECT * FROM activity_logs WHERE destination = \'rider\' ORDER BY date DESC LIMIT 3";
    $recent_notifications_stmt = $conn->prepare($recent_notifications_sql);
    $recent_notifications_stmt->execute();
    $recent_notifications = $recent_notifications_stmt->fetchAll();';
    } elseif ($user_type === 'customer') {
        $notification_logic = '
    // Fetch notifications for customer
    $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE destination = \'customer\' AND user_id = :user_id AND read_status = 0";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bindParam(\':user_id\', $user_id);
    $notification_stmt->execute();
    $unread_count = $notification_stmt->fetchColumn();

    $recent_notifications_sql = "SELECT * FROM activity_logs WHERE destination = \'customer\' AND user_id = :user_id ORDER BY date DESC LIMIT 3";
    $recent_notifications_stmt = $conn->prepare($recent_notifications_sql);
    $recent_notifications_stmt->bindParam(\':user_id\', $user_id);
    $recent_notifications_stmt->execute();
    $recent_notifications = $recent_notifications_stmt->fetchAll();';
    }
    
    // Find the right place to insert notification logic (after user data fetching)
    $insert_pattern = '/(\$user_data = \$stmt->fetch\(PDO::FETCH_ASSOC\);)/';
    $replacement = '$1' . $notification_logic;
    
    if (preg_match($insert_pattern, $content)) {
        $content = preg_replace($insert_pattern, $replacement, $content);
    } else {
        // Try alternative pattern
        $insert_pattern = '/(require \'session\.php\';)/';
        $replacement = '$1' . $notification_logic;
        if (preg_match($insert_pattern, $content)) {
            $content = preg_replace($insert_pattern, $replacement, $content);
        }
    }
    
    // Replace hardcoded notification HTML
    $old_notification_html = '/<a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">\s*<i class="fas fa-bell"><\/i>\s*<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">\s*3\s*<span class="visually-hidden">unread messages<\/span>\s*<\/span>\s*<\/a>\s*<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">\s*<li><a class="dropdown-item" href="#">Notification 1<\/a><\/li>\s*<li><a class="dropdown-item" href="#">Notification 2<\/a><\/li>\s*<li><a class="dropdown-item" href="#">Notification 3<\/a><\/li>\s*<\/ul>/s';
    
    $new_notification_html = '<a class="nav-link position-relative mt-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                                <li><a class="dropdown-item" href="process_readnotification.php?id=<?php echo $notification[\'activitylogs_id\']?>&destination=<?php echo $notification[\'destination\']?>"><?php echo $notification[\'message\'];?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>';
    
    $content = preg_replace($old_notification_html, $new_notification_html, $content);
    
    // Write back to file
    file_put_contents($filename, $content);
    echo "✅ Updated: $filename\n";
    return true;
}

// Files to update
$rider_files = [
    'assigneddelivery.php',
    'attendance.php', 
    'calendar.php',
    'deliveryhistory.php',
    'ridermap.php',
    'riderprofile.php',
    'rider_orderdetails.php',
    'rider_ratings.php'
];

$customer_files = [
    'costumerorder.php',
    'costumer_createpurchase.php',
    'orderhistory.php',
    'order_details.php',
    'costumer_orderdetails.php',
    'cart.php',
    'userprofile.php'
];

echo "Updating rider files...\n";
foreach ($rider_files as $file) {
    updateFile($file, 'rider');
}

echo "\nUpdating customer files...\n";
foreach ($customer_files as $file) {
    updateFile($file, 'customer');
}

echo "\n✅ All notification updates completed!\n";
?> 