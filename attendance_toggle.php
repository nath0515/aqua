<?php
require 'db.php';
require 'session.php';

$action = $_POST['action'] ?? null;

// ... keep the DB connection and session logic ...

if ($action === 'time_in') {
    // Prevent multiple clock-ins
    $check = $conn->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND DATE(in_time) = :today AND out_time IS NOT NULL");
    $check->bindParam(':user_id', $user_id);
    $check->bindParam(':today', $today);
    $check->execute();
    $alreadyOut = $check->fetch(PDO::FETCH_ASSOC);

    if ($alreadyOut) {
        echo json_encode(['success' => false, 'message' => 'You already clocked out today.']);
        exit;
    }

    // Clock In
    $conn->prepare("UPDATE rider_status SET status = 1, last_toggle = :now WHERE user_id = :user_id")
         ->execute([':now' => $now, ':user_id' => $user_id]);
    $conn->prepare("INSERT INTO attendance (user_id, in_time) VALUES (:user_id, :in_time)")
         ->execute([':user_id' => $user_id, ':in_time' => $now]);

    echo json_encode(['success' => true]);
    exit;

} elseif ($action === 'time_out') {
    // Clock Out
    $conn->prepare("UPDATE rider_status SET status = 0, last_toggle = :now WHERE user_id = :user_id")
         ->execute([':now' => $now, ':user_id' => $user_id]);
    $conn->prepare("UPDATE attendance SET out_time = :out_time WHERE user_id = :user_id AND out_time IS NULL")
         ->execute([':out_time' => $now, ':user_id' => $user_id]);

    echo json_encode(['success' => true]);
    exit;
}

// Fallback if invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;

?>
