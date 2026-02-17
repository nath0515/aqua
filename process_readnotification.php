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
            // Redirect back to the current page if redirect parameter is provided
            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirect_url = $_GET['redirect'];
            } else {
                // Fallback to appropriate dashboard based on role
                if ($role_id == 2) { // Customer
                    $redirect_url = 'home.php';
                } elseif ($role_id == 3) { // Rider
                    $redirect_url = 'riderdashboard.php';
                } else { // Admin
                    $redirect_url = 'index.php';
                }
            }
            
            // Add success parameter to the redirect URL
            $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
            header('Location: ' . $redirect_url . $separator . 'notifications_marked=' . $marked_count);
            exit();
        } else {
            echo "Error marking notifications as read.";
        }
    } else {
        // Original single notification read functionality
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $destination = isset($_GET['destination']) ? $_GET['destination'] : 'home';

    // Update notification
    $stmt = $conn->prepare("UPDATE activity_logs SET read_status = 1 WHERE activitylogs_id = :id");
    $stmt->execute(['id' => $id]);

    // Remove .php if someone sends it
    $destination = str_replace('.php', '', $destination);

    // Prevent external redirects (security)
    if (strpos($destination, 'http') === 0) {
        $destination = 'home';
    }

    // Redirect safely
    header("Location: /" . ltrim($destination, '/'));
    exit();

        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
?>