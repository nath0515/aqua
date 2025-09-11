<?php
require 'db.php';

$role = $_POST['role'] ?? '';

$sql = "SELECT CONCAT(ud.firstname, ' ', ud.lastname) AS full_name, r.role_name, contact_number, u.created_at 
        FROM users u
        JOIN user_details ud ON u.user_id = ud.user_id
        JOIN roles r ON u.role_id = r.role_id";

if (!empty($role)) {
    $sql .= " WHERE r.role_name = :role_name";
}

$stmt = $conn->prepare($sql);

if (!empty($role)) {
    $stmt->bindParam(':role_name', $role);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML rows
foreach ($users as $row) {
    echo "<tr>
        <td>{$row['full_name']}</td>
        <td>{$row['role_name']}</td>
        <td>{$row['contact_number']}</td>
        <td>" . date('F j, Y', strtotime($row['created_at'])) . "</td>
    </tr>";
}
?>
