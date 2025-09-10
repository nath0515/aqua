<?php
require 'session.php';
require 'db.php';

// Get filter inputs, sanitize
$role_id = isset($_GET['role']) && $_GET['role'] !== '' ? intval($_GET['role']) : null;
$date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : null;

$sql = "SELECT CONCAT(ud.firstname, ' ', ud.lastname) AS full_name, role_name, contact_number, created_at 
        FROM users u
        JOIN user_details ud ON u.user_id = ud.user_id
        JOIN roles r ON u.role_id = r.role_id
        WHERE 1";

$params = [];

if ($role_id !== null) {
    $sql .= " AND u.role_id = :role_id";
    $params[':role_id'] = $role_id;
}

if ($date !== null) {
    // Filtering by date only (ignoring time)
    $sql .= " AND DATE(u.created_at) = :date";
    $params[':date'] = $date;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
