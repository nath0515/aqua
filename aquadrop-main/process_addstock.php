<?php
require 'db.php';
require 'session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $product_id = $_POST['product_id'];
        $stock = $_POST['stock'];

        $sql = "SELECT stock FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $data = $stmt->fetch();

        $currentStock = $data['stock'];
        $newStock = $currentStock + $stock;

        $sql = "UPDATE products SET stock = :stock WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stock', $newStock);
        $stmt->bindParam(':product_id', $product_id);
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
