<?php
require('db.php');
require('session.php');

header('Content-Type: application/json');

// Ensure only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get and sanitize inputs
$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$reason = $_POST['reason'] ?? null;

$allowedStatuses = ['approved', 'rejected'];

if (!$id || !$status || !in_array(strtolower($status), $allowedStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid application ID or status.'
    ]);
    exit;
}

// Prepare the update
$sql = "UPDATE applications SET status = :status WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$sql = "SELECT user_id FROM applications WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user_id = $stmt->fetchColumn();

if($status == 'approved'){
    $sql = "UPDATE users SET rs = 1 WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
}
else{
    $sql = "UPDATE applications SET reason = :reason WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

echo json_encode([
    'success' => true,
    'message' => "Application has been " . htmlspecialchars($status) . "."
]);

exit;
