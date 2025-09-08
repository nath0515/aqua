<?php 
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    try {
        require 'session.php';
        require 'db.php';
        
        echo "<h1>Debug Custom Report</h1>";
        echo "<hr>";

        $user_id = $_SESSION['user_id'];
        $role_id = $_SESSION['role_id'];
        
        echo "<p><strong>User ID:</strong> $user_id</p>";
        echo "<p><strong>Role ID:</strong> $role_id</p>";
        
        if($role_id == 2){
            echo "<p>Redirecting to home.php (User role)</p>";
            header("Location: home.php");
        }else if ($role_id == 3){
            echo "<p>Redirecting to riderdashboard.php (Rider role)</p>";
            header("Location: riderdashboard.php");
        }

        // Get date range from URL parameters
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
        
        echo "<p><strong>Start Date:</strong> $start_date</p>";
        echo "<p><strong>End Date:</strong> $end_date</p>";

        // Validate date range
        if (empty($start_date) || empty($end_date)) {
            echo "<p>Empty dates - redirecting to report.php</p>";
            header('Location: report.php');
            exit();
        }

        if (strtotime($start_date) > strtotime($end_date)) {
            echo "<p>Invalid date range - redirecting to report.php</p>";
            header('Location: report.php');
            exit();
        }

        echo "<h2>Testing Database Queries</h2>";

        // Test user data query
        echo "<h3>1. User Data Query</h3>";
        $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
        JOIN user_details ud ON u.user_id = ud.user_id
        WHERE u.user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ User data query successful</p>";
        echo "<pre>" . print_r($user_data, true) . "</pre>";

        // Test orders query
        echo "<h3>2. Orders Query</h3>";
        $sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.contact_number, 
                c.status_name, d.firstname AS rider_firstname, d.lastname AS rider_lastname, e.payment_name
                FROM orders a
                JOIN user_details b ON a.user_id = b.user_id
                JOIN orderstatus c ON a.status_id = c.status_id
                LEFT JOIN user_details d ON a.rider = d.user_id
                JOIN payment_method e ON a.payment_id = e.payment_id
                WHERE DATE(a.date) BETWEEN :start_date AND :end_date
                AND (a.status_id = 4 OR a.status_id = 5)
                ORDER BY a.date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Orders query successful - Found " . count($order_data) . " orders</p>";
        echo "<pre>" . print_r($order_data, true) . "</pre>";

        // Test expenses query
        echo "<h3>3. Expenses Query</h3>";
        $sql = "SELECT a.expense_id, a.date, a.amount, a.comment, b.expensetype_name
                FROM expense a
                JOIN expensetype b ON a.expensetype_id = b.expensetype_id
                WHERE DATE(a.date) BETWEEN :start_date AND :end_date
                ORDER BY a.date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $expense_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Expenses query successful - Found " . count($expense_data) . " expenses</p>";
        echo "<pre>" . print_r($expense_data, true) . "</pre>";

        // Test activity logs query
        echo "<h3>4. Activity Logs Query</h3>";
        $sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE read_status = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $unread_count = $unread_result['unread_count'];
        echo "<p>✅ Activity logs query successful - Unread count: $unread_count</p>";

        // Test store status query
        echo "<h3>5. Store Status Query</h3>";
        $sql = "SELECT status FROM store_status WHERE ss_id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $status = $stmt->fetchColumn();
        echo "<p>✅ Store status query successful - Status: $status</p>";

        echo "<hr>";
        echo "<p style='color: green;'><strong>✅ All queries successful! The issue might be in the HTML/PHP rendering.</strong></p>";

    } catch (PDOException $e) {
        echo "<p style='color: red;'><strong>❌ Database Error:</strong> " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ General Error:</strong> " . $e->getMessage() . "</p>";
    }
?>
