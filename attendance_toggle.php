<?php
require 'db.php';
require 'session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_POST['id'] ?? null;
$type = $_POST['type'] ?? null;
$now = date('Y-m-d H:i:s');

if (!$id || !in_array($type, ['in', 'out'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

$field = $type === 'in' ? 'time_in' : 'time_out';

$stmt = $conn->prepare("UPDATE attendance SET $field = :now WHERE id = :id AND user_id = :user_id AND $field IS NULL");
$stmt->bindParam(':now', $now);
$stmt->bindParam(':id', $id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'success', 'message' => ucfirst($type) . ' time recorded']);
} else {
    echo json_encode(['status' => 'error', 'message' => ucfirst($type) . ' already set or invalid record']);
}
?>
