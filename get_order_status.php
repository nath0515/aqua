<?php 
require 'db.php';

$order_id = $_GET['order_id'];
$sql = "SELECT status_name FROM orders a JOIN orderstatus b ON a.status_id = b.status_id WHERE a.order_id = :order_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':order_id' => $order_id]);
$status = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($status);
?>