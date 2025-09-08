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

// Sorting parameters
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'date';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'DESC';

// Validate sort parameters
$allowedSortColumns = ['order_id', 'date', 'amount', 'firstname', 'contact_number', 'status_name'];
$allowedSortOrders = ['ASC', 'DESC'];

if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'date';
}
if (!in_array($sortOrder, $allowedSortOrders)) {
    $sortOrder = 'DESC';
}

try {
    // Count total records
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
    $countStmt->bindParam(':user_id', $user_id);
    $countStmt->execute();
    $total = $countStmt->fetchColumn();

    // Fetch paginated data with dynamic sorting
    $sql = "SELECT a.order_id, a.date, a.amount, a.status_id, c.status_name, a.rider, 
                   COALESCE(d.firstname, 'None') as rider_firstname, 
                   COALESCE(d.lastname, '') as rider_lastname,
                   b.firstname, b.lastname, b.contact_number,
                   ul.label, ul.address, ul.latitude, ul.longitude,
                   tb.barangay_name, tm.municipality_name, tp.province_name
            FROM orders a
            JOIN user_details b ON a.user_id = b.user_id
            LEFT JOIN user_locations ul ON a.location_id = ul.location_id
            LEFT JOIN table_barangay tb ON ul.barangay_id = tb.barangay_id
            LEFT JOIN table_municipality tm ON tb.municipality_id = tm.municipality_id
            LEFT JOIN table_province tp ON tm.province_id = tp.province_id
            JOIN orderstatus c ON a.status_id = c.status_id
            LEFT JOIN user_details d ON a.rider = d.user_id
            WHERE a.user_id = :user_id
            ORDER BY a.$sortBy $sortOrder
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
