<?php
require 'session.php';
require 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $sql = "SELECT a.order_id, a.date, a.amount, a.status_id, c.status_name, d.firstname as rider_firstname, d.lastname as rider_lastname,
                b.firstname, b.lastname, b.contact_number, b.address
            FROM orders a
            JOIN user_details b ON a.user_id = b.user_id
            JOIN orderstatus c ON a.status_id = c.status_id
            JOIN user_details d ON a.rider = d.user_id
            WHERE a.user_id = :user_id
            ORDER BY a.date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'orders' => $orders]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
