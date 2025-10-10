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

// Execute and return result
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => "Application has been " . htmlspecialchars($status) . "."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update application status.'
    ]);
}
exit;
