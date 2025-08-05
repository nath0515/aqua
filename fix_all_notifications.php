<?php
// This script will help identify and fix all hardcoded notifications
// Run this to see which files need to be updated

$files_to_check = [
    // Rider pages
    'assigneddelivery.php',
    'attendance.php', 
    'calendar.php',
    'deliveryhistory.php',
    'ridermap.php',
    'riderprofile.php',
    'rider_orderdetails.php',
    'rider_ratings.php',
    
    // Customer pages
    'costumerorder.php',
    'costumer_createpurchase.php',
    'orderhistory.php',
    'order_details.php',
    'costumer_orderdetails.php',
    'cart.php',
    'userprofile.php',
    
    // Other pages
    'addresses.php',
    'user_tracker.php',
    'mapuser.php',
    'add_location.php'
];

echo "Files that need notification updates:\n";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'Notification 1') !== false) {
            echo "✅ $file - NEEDS UPDATE\n";
        } else {
            echo "❌ $file - Already updated or no notifications\n";
        }
    } else {
        echo "❌ $file - File not found\n";
    }
}

echo "\n\nTo fix these files, you need to:\n";
echo "1. Add notification fetching logic at the top of each file\n";
echo "2. Replace hardcoded notification HTML with dynamic PHP\n";
echo "3. Use the same pattern as riderdashboard.php and home.php\n";
?> 