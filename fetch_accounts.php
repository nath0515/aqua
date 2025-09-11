<?php
require 'db.php';

$role = $_POST['role'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';

$sql = "SELECT CONCAT(ud.firstname, ' ', ud.lastname) AS full_name, r.role_name, contact_number, u.created_at 
        FROM users u
        JOIN user_details ud ON u.user_id = ud.user_id
        JOIN roles r ON u.role_id = r.role_id
        WHERE 1 = 1";

if (!empty($role)) {
    $sql .= " AND r.role_name = :role";
}

if (!empty($startDate)) {
    $sql .= " AND DATE(u.created_at) >= :startDate";
}

if (!empty($endDate)) {
    $sql .= " AND DATE(u.created_at) <= :endDate";
}

$stmt = $conn->prepare($sql);

if (!empty($role)) {
    $stmt->bindParam(':role', $role);
}
if (!empty($startDate)) {
    $stmt->bindParam(':startDate', $startDate);
}
if (!empty($endDate)) {
    $stmt->bindParam(':endDate', $endDate);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output table rows
foreach ($users as $row) {
    echo "<tr>
        <td>{$row['full_name']}</td>
        <td>{$row['role_name']}</td>
        <td>{$row['contact_number']}</td>
        <td>" . date('F j, Y', strtotime($row['created_at'])) . "</td>
    </tr>";
}
?>
