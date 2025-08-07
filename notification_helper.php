<?php
// Notification Helper Functions
function getNotifications($conn, $user_id, $role_id) {
    $notifications = [];
    
    try {
        // Get unread count based on user role
        if ($role_id == 2) { // Customer
            $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE destination LIKE 'costumer_orderdetails.php%' AND user_id = :user_id AND read_status = 0";
        } elseif ($role_id == 3) { // Rider
            $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE destination LIKE 'rider_orderdetails.php%' AND user_id = :user_id AND read_status = 0";
        } else { // Admin
            $notification_sql = "SELECT COUNT(*) AS unread_count FROM activity_logs WHERE destination LIKE 'order_details.php%' AND read_status = 0";
        }
        
        $notification_stmt = $conn->prepare($notification_sql);
        if ($role_id != 1) { // Not admin
            $notification_stmt->bindParam(':user_id', $user_id);
        }
        $notification_stmt->execute();
        $unread_count = $notification_stmt->fetchColumn();
        
        // Get recent notifications
        if ($role_id == 2) { // Customer
            $recent_sql = "SELECT * FROM activity_logs WHERE destination LIKE 'costumer_orderdetails.php%' AND user_id = :user_id ORDER BY date DESC LIMIT 5";
        } elseif ($role_id == 3) { // Rider
            $recent_sql = "SELECT * FROM activity_logs WHERE destination LIKE 'rider_orderdetails.php%' AND user_id = :user_id ORDER BY date DESC LIMIT 5";
        } else { // Admin
            $recent_sql = "SELECT * FROM activity_logs WHERE destination LIKE 'order_details.php%' ORDER BY date DESC LIMIT 5";
        }
        
        $recent_stmt = $conn->prepare($recent_sql);
        if ($role_id != 1) { // Not admin
            $recent_stmt->bindParam(':user_id', $user_id);
        }
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

function renderNotificationDropdown($recent_notifications) {
    $html = '';
    
    if (empty($recent_notifications)) {
        $html .= '<li><a class="dropdown-item text-muted" href="#">No notifications</a></li>';
    } else {
        foreach ($recent_notifications as $notification) {
            $html .= '<li><a class="dropdown-item" href="process_readnotification.php?id=' . $notification['activitylogs_id'] . '&destination=' . $notification['destination'] . '">' . htmlspecialchars($notification['message']) . '</a></li>';
        }
    }
    
    return $html;
}
?> 