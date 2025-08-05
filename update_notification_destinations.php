<?php
// Script to update all notification destinations to use specific order pages

function updateFile($filename, $user_type) {
    if (!file_exists($filename)) {
        echo "❌ File not found: $filename\n";
        return false;
    }
    
    $content = file_get_contents($filename);
    
    if ($user_type === 'rider') {
        // Update rider notification queries
        $content = str_replace(
            "destination = 'rider'",
            "destination LIKE 'rider_orderdetails.php%'",
            $content
        );
    } elseif ($user_type === 'customer') {
        // Update customer notification queries
        $content = str_replace(
            "destination = 'customer'",
            "destination LIKE 'costumer_orderdetails.php%'",
            $content
        );
    }
    
    // Write back to file
    file_put_contents($filename, $content);
    echo "✅ Updated: $filename\n";
    return true;
}

// Files to update
$rider_files = [
    'riderdashboardclosed.php',
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

echo "\n✅ All notification destination updates completed!\n";
echo "\n📋 Summary of changes:\n";
echo "- Rider notifications now redirect to: rider_orderdetails.php?id=ORDER_ID\n";
echo "- Customer notifications now redirect to: costumer_orderdetails.php?id=ORDER_ID\n";
echo "- All notification queries updated to use LIKE pattern matching\n";
?> 