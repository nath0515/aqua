
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL)
require 'session.php';
require 'db.php';


$role_id = isset($_GET['role']) && $_GET['role'] !== '' ? intval($_GET['role']) : null;
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : null;

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

// Date filtering
if ($start_date !== null && $end_date !== null) {
    $sql .= " AND DATE(u.created_at) BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date;
    $params[':end_date'] = $end_date;
} else if ($start_date !== null) {
    $sql .= " AND DATE(u.created_at) >= :start_date";
    $params[':start_date'] = $start_date;
} else if ($end_date !== null) {
    $sql .= " AND DATE(u.created_at) <= :end_date";
    $params[':end_date'] = $end_date;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
