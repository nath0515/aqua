<?php
// Script to update all pages with hardcoded notifications
// This script will help identify and update files that need notification fixes

$files_to_update = [
    'costumerorder.php',
    'orderhistory.php',
    'cart.php',
    'ridermap.php',
    'riderdashboard.php',
    'riderdashboardclosed.php',
    'assigneddelivery.php',
    'calendar.php',
    'costumer_createpurchase.php',
    'rider_ratings.php',
    'attendance.php',
    'riderprofile.php',
    'order_details.php',
    'costumer_orderdetails.php',
    'deliveryhistory.php',
    'userprofile.php',
    'rider_orderdetails.php'
];

echo "Files that need notification updates:\n";
foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        echo "- $file\n";
    }
}

echo "\nTo fix these files, you need to:\n";
echo "1. Add: require 'notification_helper.php';\n";
echo "2. Add: \$notifications = getNotifications(\$conn, \$user_id, \$role_id);\n";
echo "3. Replace hardcoded badge with: <?php echo renderNotificationBadge(\$notifications['unread_count']); ?>\n";
echo "4. Replace hardcoded dropdown with: <?php echo renderNotificationDropdown(\$notifications['recent_notifications']); ?>\n";
?> 