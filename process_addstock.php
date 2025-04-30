<?php
require 'db.php';
require 'session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $product_id = $_POST['product_id'];
        $stock = $_POST['stock'];
        $now = date("Y-m-d H:i:s");

        $sql = "SELECT product_name, stock FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $data = $stmt->fetch();

        $currentStock = $data['stock'];
        $newStock = $currentStock + $stock;
        $product_name = $data['product_name'];

        $sql = "UPDATE products SET stock = :stock WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stock', $newStock);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();

        $message = "Added ".$stock." stock of ".$product_name." to inventory.";

        $sql = "INSERT INTO activity_logs (message, date) VALUES (':message', :date)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':date', $now);
        $stmt->execute();


        header("Location: stock.php?stock=success");
        exit();
        
    } catch (PDOException $e) {
        header("Location: stock.php?status=error");
        exit();
    }
} else {
    header("Location: stock.php?status=error");
    exit();
}
?>
