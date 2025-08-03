<?php
require 'db.php';
require 'session.php';

if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    $query = "SELECT * FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false]);
    }
    
} else {
    echo json_encode(["success" => false]);
}
?>
