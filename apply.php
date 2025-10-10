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

$uploadDir = 'uploads/ids/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true); 
}

if (!isset($_FILES['id_image']) || $_FILES['id_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to upload ID. Please try again.'
    ]);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$fileType = mime_content_type($_FILES['id_image']['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.'
    ]);
    exit;
}

$ext = pathinfo($_FILES['id_image']['name'], PATHINFO_EXTENSION);
$filename = uniqid('valid_id_') . '.' . $ext;
$targetFile = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['id_image']['tmp_name'], $targetFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save uploaded file.'
    ]);
    exit;
}

$valid_id = $targetFile;

// Insert new application
$sql = "INSERT INTO applications (user_id, application_date, status, valid_id) VALUES (:user_id, :application_date, 'pending', :valid_id)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':application_date', $currentDateTime);
$stmt->bindParam(':valid_id', $valid_id);

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
