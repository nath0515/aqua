<?php
require 'db.php';

$role = $_POST['role'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';

$sql = "SELECT CONCAT(ud.firstname, ' ', ud.lastname) AS full_name, r.role_name, contact_number, u.created_at 
        FROM users u
        JOIN user_details ud ON u.user_id = ud.user_id
        JOIN roles r ON u.role_id = r.role_id
        WHERE 1=1";

$params = [];

if (!empty($role)) {
    $sql .= " AND r.role_name = :role";
    $params[':role'] = $role;
}

if (!empty($startDate)) {
    $sql .= " AND DATE(u.created_at) >= :startDate";
    $params[':startDate'] = $startDate;
}

if (!empty($endDate)) {
    $sql .= " AND DATE(u.created_at) <= :endDate";
    $params[':endDate'] = $endDate;
}

$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) > 0) {
    foreach ($users as $row) {
        echo "<tr>
            <td>" . htmlspecialchars($row['full_name']) . "</td>
            <td>" . htmlspecialchars($row['role_name']) . "</td>
            <td>" . htmlspecialchars($row['contact_number']) . "</td>
            <td>" . date('F j, Y', strtotime($row['created_at'])) . "</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center'>No accounts found.</td></tr>";
}
?>
