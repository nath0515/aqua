<?php
// Notification Helper Functions
function getNotifications($conn, $user_id, $role_id) {
    $notifications = [];
    
    try {
        // Get unread count based on user role
        if ($role_id == 2) { // Customer
            $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE user_id = :user_id AND read_status = 0";
        } elseif ($role_id == 3) { // Rider
            $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE user_id = :user_id AND read_status = 0";
        } else { // Admin
            $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE read_status = 0";
        }
        
        $notification_stmt = $conn->prepare($notification_sql);
        if ($role_id != 1) { // Not admin
            $notification_stmt->bindParam(':user_id', $user_id);
        }
        $notification_stmt->execute();
        $unread_count = $notification_stmt->fetchColumn();
        
        // Get recent notifications
        if ($role_id == 2) { // Customer
            $recent_sql = "SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY date DESC LIMIT 5";
        } elseif ($role_id == 3) { // Rider
            $recent_sql = "SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY date DESC LIMIT 5";
        } else{ // Admin
            $recent_sql = "SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY date DESC LIMIT 5";
        }
        
        $recent_stmt = $conn->prepare($recent_sql);
        
        $recent_stmt->bindParam(':user_id', $user_id);
        
        $recent_stmt->execute();
        $recent_notifications = $recent_stmt->fetchAll();
        
        $notifications = [
            'unread_count' => $unread_count,
            'recent_notifications' => $recent_notifications
        ];
        
    } catch (PDOException $e) {
        // Fallback to default values if there's an error
        $notifications = [
            'unread_count' => 0,
            'recent_notifications' => []
        ];
    }
    
    return $notifications;
}

function renderNotificationBadge($unread_count) {
    if ($unread_count > 0) {
        return '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $unread_count . '</span>';
    }
    return '';
}

function renderNotificationDropdown($recent_notifications, $unread_count, $user_id, $role_id) {
    $html = '';
    
    // Get current page URL for redirect
    $current_page = basename($_SERVER['PHP_SELF']);
    if (!empty($_SERVER['QUERY_STRING'])) {
        $current_page .= '?' . $_SERVER['QUERY_STRING'];
    }
    
    if (empty($recent_notifications)) {
        $html .= '<li><a class="dropdown-item text-muted" href="#">No notifications</a></li>';
    } else {
        // Add mark all as read button if there are unread notifications
        if ($unread_count > 0) {
            $html .= '<li><a class="dropdown-item text-center text-primary fw-bold" href="process_readnotification.php?action=mark_all_read&user_id=' . $user_id . '&role_id=' . $role_id . '&redirect=' . urlencode($current_page) . '"><i class="fas fa-check-double"></i> Mark All as Read</a></li>';
            $html .= '<li><hr class="dropdown-divider"></li>';
        }
        
        foreach ($recent_notifications as $notification) {
            $read_class = $notification['read_status'] == 1 ? 'text-muted' : 'fw-bold';
            $html .= '<li><a class="dropdown-item ' . $read_class . '" href="process_readnotification.php?id=' . $notification['activitylogs_id'] . '&destination=' . $notification['destination'] . '">' . htmlspecialchars($notification['message']) . '</a></li>';
        }
    }
    
    return $html;
}

function markAllAsRead($conn, $user_id, $role_id) {
    try {
        // Mark ALL notifications as read for the user, regardless of destination
        if ($role_id == 2) { // Customer
            $sql = "UPDATE activity_logs SET read_status = 1 WHERE user_id = :user_id AND read_status = 0";
        } elseif ($role_id == 3) { // Rider
            $sql = "UPDATE activity_logs SET read_status = 1 WHERE user_id = :user_id AND read_status = 0";
        } else { // Admin
            $sql = "UPDATE activity_logs SET read_status = 1 WHERE read_status = 0";
        }
        
        $stmt = $conn->prepare($sql);
        if ($role_id != 1) { // Not admin
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        
        return $stmt->rowCount(); // Return number of notifications marked as read
    } catch (PDOException $e) {
        return false;
    }
}
?> 