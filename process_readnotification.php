<?php 
require ('db.php');
require ('session.php');
require ('notification_helper.php');

try {
    // Check if it's a mark all as read action
    if (isset($_GET['action']) && $_GET['action'] == 'mark_all_read') {
        $user_id = $_GET['user_id'];
        $role_id = $_GET['role_id'];
        
        $marked_count = markAllAsRead($conn, $user_id, $role_id);
        
        if ($marked_count !== false) {
            // Redirect back to the current page or dashboard
            $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            
            // Set appropriate redirect based on role
            if ($role_id == 2) { // Customer
                $redirect_url = 'home.php';
            } elseif ($role_id == 3) { // Rider
                $redirect_url = 'riderdashboard.php';
            } else { // Admin
                $redirect_url = 'index.php';
            }
            
            header('Location: ' . $redirect_url . '?notifications_marked=' . $marked_count);
            exit();
        } else {
            echo "Error marking notifications as read.";
        }
    } else {
        // Original single notification read functionality
        $id = $_GET['id'];
        $destination = $_GET['destination'];

        $stmt = $conn->prepare("UPDATE activity_logs SET read_status = 1 WHERE activitylogs_id = :id");
        $stmt->execute(['id' => $id]);

        header('Location: '.$destination);
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>