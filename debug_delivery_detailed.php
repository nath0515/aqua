<?php
// Debug script to test delivery process
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Detailed Delivery Debug</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test:</h3>";
try {
    require 'db.php';
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if uploads directory exists and is writable
echo "<h3>2. Uploads Directory Test:</h3>";
$uploads_dir = 'uploads/';
if (is_dir($uploads_dir)) {
    echo "✅ Uploads directory exists<br>";
    if (is_writable($uploads_dir)) {
        echo "✅ Uploads directory is writable<br>";
    } else {
        echo "❌ Uploads directory is NOT writable<br>";
    }
} else {
    echo "❌ Uploads directory does not exist<br>";
}

// Test 3: Check orders table structure
echo "<h3>3. Orders Table Structure:</h3>";
try {
    $sql = "DESCRIBE orders";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_proof_file = false;
    $has_status_id = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'proof_file') {
            $has_proof_file = true;
            echo "✅ proof_file column found: " . $column['Type'] . "<br>";
        }
        if ($column['Field'] === 'status_id') {
            $has_status_id = true;
            echo "✅ status_id column found: " . $column['Type'] . "<br>";
        }
    }
    
    if (!$has_proof_file) {
        echo "❌ proof_file column NOT found<br>";
    }
    if (!$has_status_id) {
        echo "❌ status_id column NOT found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
}

// Test 4: Check orderstatus table
echo "<h3>4. Order Status Table:</h3>";
try {
    $sql = "SELECT * FROM orderstatus ORDER BY status_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available statuses:<br>";
    foreach ($statuses as $status) {
        echo "- ID " . $status['status_id'] . ": " . $status['status_name'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking orderstatus: " . $e->getMessage() . "<br>";
}

// Test 5: Check if order 1424 exists and its current status
echo "<h3>5. Test Order 1424:</h3>";
try {
    $sql = "SELECT o.order_id, o.status_id, o.rider, o.user_id, os.status_name 
            FROM orders o 
            JOIN orderstatus os ON o.status_id = os.status_id 
            WHERE o.order_id = 1424";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "✅ Order 1424 found:<br>";
        echo "- Status: " . $order['status_name'] . " (ID: " . $order['status_id'] . ")<br>";
        echo "- Rider: " . $order['rider'] . "<br>";
        echo "- Customer: " . $order['user_id'] . "<br>";
    } else {
        echo "❌ Order 1424 not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking order: " . $e->getMessage() . "<br>";
}

// Test 6: Check activity_logs table
echo "<h3>6. Activity Logs Table:</h3>";
try {
    $sql = "DESCRIBE activity_logs";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['message', 'date', 'destination', 'user_id'];
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $found = true;
                echo "✅ $col column found: " . $column['Type'] . "<br>";
                break;
            }
        }
        if (!$found) {
            echo "❌ $col column NOT found<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking activity_logs: " . $e->getMessage() . "<br>";
}

// Test 7: Simulate the update query
echo "<h3>7. Test Update Query:</h3>";
try {
    // Test the exact update query that would be used
    $test_proof_path = 'uploads/test_proof.jpg';
    $test_order_id = 1424;
    
    $update_sql = "UPDATE orders SET status_id = 4, proof_file = :proof_path WHERE order_id = :order_id";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':proof_path', $test_proof_path);
    $update_stmt->bindParam(':order_id', $test_order_id);
    
    // Don't actually execute, just check if it prepares successfully
    echo "✅ Update query prepared successfully<br>";
    echo "Query: $update_sql<br>";
    
} catch (Exception $e) {
    echo "❌ Error preparing update query: " . $e->getMessage() . "<br>";
}

echo "<h3>Debug Complete</h3>";
?> 