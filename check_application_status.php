<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'] ?? null;
    
    header('Content-Type: application/json');

    if (!$user_id) {
        echo json_encode(['error' => 'User not logged in']);
        exit;
    }

    $sql = "SELECT status FROM applications WHERE user_id = :user_id LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'exists' => true,
            'status' => $result['status']
        ]);
    } else {
        echo json_encode([
            'exists' => false
        ]);
    }


?>