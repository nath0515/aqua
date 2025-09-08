<?php
require 'session.php';
require 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$filter_range = $_GET['filter_range'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Apply same date logic as sales.php
if ($filter_range) {
    $today = date('Y-m-d');
    
    switch ($filter_range) {
        case 'today':
            $start_date = $today;
            $end_date = $today;
            break;
        case 'week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            break;
    }
}

// Build SQL query (same as sales.php)
$sql = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.address, b.contact_number, c.status_name, CONCAT(r.firstname, ' ', r.lastname) as rider FROM orders a
JOIN user_details b ON a.user_id = b.user_id
LEFT JOIN user_details r ON a.rider = r.user_id
JOIN orderstatus c ON a.status_id = c.status_id WHERE a.status_id IN (4, 5)";

$params = [];

if ($start_date && $end_date) {
    $start_date_time = $start_date . ' 00:00:00';
    $end_date_time = $end_date . ' 23:59:59';
    
    $sql .= " AND a.date BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date_time;
    $params[':end_date'] = $end_date_time;
}

$sql .= " ORDER BY a.date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$total_orders = count($order_data);
foreach ($order_data as $order) {
    $total_amount += floatval($order['amount']);
}

// Generate PDF content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report - AquaDrop</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #0077b6; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary h3 { margin-top: 0; color: #0077b6; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-row strong { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #0077b6; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .amount { text-align: right; }
        .status-delivered { color: #28a745; font-weight: bold; }
        .status-completed { color: #007bff; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AquaDrop Sales Report</h1>
        <p>Generated on: ' . date('F j, Y \a\t g:i A') . '</p>
    </div>
    
    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-row">
            <span><strong>Total Orders:</strong></span>
            <span>' . $total_orders . '</span>
        </div>
        <div class="summary-row">
            <span><strong>Total Amount:</strong></span>
            <span>₱' . number_format($total_amount, 2) . '</span>
        </div>
        <div class="summary-row">
            <span><strong>Date Range:</strong></span>
            <span>' . ($start_date && $end_date ? date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) : 'All Time') . '</span>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Status</th>
                <th>Rider</th>
            </tr>
        </thead>
        <tbody>';

foreach ($order_data as $row) {
    $status_class = '';
    if ($row['status_name'] == 'Delivered') $status_class = 'status-delivered';
    if ($row['status_name'] == 'Completed') $status_class = 'status-completed';
    
    $html .= '
            <tr>
                <td>' . $row['order_id'] . '</td>
                <td>' . date("M j, Y - h:i A", strtotime($row['date'])) . '</td>
                <td class="amount">₱' . number_format($row['amount'], 2) . '</td>
                <td>' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '</td>
                <td>' . htmlspecialchars($row['contact_number']) . '</td>
                <td>' . htmlspecialchars($row['address']) . '</td>
                <td class="' . $status_class . '">' . htmlspecialchars($row['status_name']) . '</td>
                <td>' . htmlspecialchars($row['rider'] ?: 'Unassigned') . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p>This report was generated by AquaDrop Management System</p>
        <p>For questions, contact the system administrator</p>
    </div>
</body>
</html>';

// Set headers for PDF download
$filename = 'Sales_Report_' . date('Y-m-d_H-i-s') . '.html';
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

echo $html;
?>
