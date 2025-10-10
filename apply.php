<?php
require('db.php');
require('session.php');

header('Content-Type: application/json');

$currentDateTime = date('Y-m-d H:i:s');

$dest = '/';
if (isset($_POST['dest'])) {
    $dest = urldecode($_POST['dest']);
}

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id === null) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to apply.',
        'redirect' => $dest
    ]);
    exit;
}

// Check if already applied
$sql = "SELECT user_id, status FROM applications WHERE user_id = :user_id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$exist = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exist) {
    $status = strtolower($exist['status']);
    $msg = match ($status) {
        'pending' => 'You already have a pending reseller application.',
        'rejected' => 'Your previous reseller application was rejected.',
        'approved' => 'You are already a reseller.',
        default => 'You have already applied.'
    };
    echo json_encode([
        'success' => false,
        'message' => $msg,
        'redirect' => $dest
    ]);
    exit;
}

// Insert new application
$sql = "INSERT INTO applications (user_id, application_date, status) VALUES (:user_id, :application_date, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':application_date', $currentDateTime);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit application. Please try again.'
    ]);
}
exit;
