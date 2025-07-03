<?php
require 'session.php';
require 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
$offset = ($page - 1) * $perPage;

try {
    // Count total records
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
    $countStmt->bindParam(':user_id', $user_id);
    $countStmt->execute();
    $total = $countStmt->fetchColumn();

    // Fetch paginated data
    $sql = "SELECT a.order_id, a.date, a.amount, a.status_id, c.status_name, a.rider, 
                   COALESCE(d.firstname, 'None') as rider_firstname, 
                   COALESCE(d.lastname, '') as rider_lastname,
                   b.firstname, b.lastname, b.contact_number, b.address
            FROM orders a
            JOIN user_details b ON a.user_id = b.user_id
            JOIN orderstatus c ON a.status_id = c.status_id
            LEFT JOIN user_details d ON a.rider = d.user_id
            WHERE a.user_id = :user_id
            ORDER BY a.date DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => intval($total),
        'page' => $page,
        'perPage' => $perPage
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
