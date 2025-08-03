<?php
require 'db.php';
require 'session.php';

if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    $query = "SELECT a.order_id, a.date, a.amount, b.firstname, b.lastname, b.address, b.contact_number, a.status_id, c.status_name, a.rider FROM orders a
    JOIN user_details b ON a.user_id = b.user_id
    JOIN orderstatus c ON a.status_id = c.status_id
    WHERE order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":order_id", $order_id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $query2 = "SELECT a.orderitems_id, a.order_id, a.product_id, a.quantity, 
    b.product_name, (b.water_price * a.quantity) as price
    FROM orderitems WHERE order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":order_id", $order_id);
    $stmt->execute();
    $data2 = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(["success" => true, "data" => $data, "data2" => $data2]);
    } else {
        echo json_encode(["success" => false]);
    }
    
} else {
    echo json_encode(["success" => false]);
}
?>
