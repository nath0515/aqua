<?php
require 'db.php';
require 'session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $product_name = $_POST['product_name'];
        $water_price = $_POST['water_price'];
        $container_price = $_POST['container_price'];
        $stock = $_POST['stock'];
        $now = date("Y-m-d H:i:s");

        $sql = "SELECT * FROM products WHERE product_name = :product_name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            header("Location: stock.php?status=exist");
            exit();
        }

        $target_dir = "uploads/";
        $file_name = basename($_FILES["product_photo"]["name"]);
        $unique_name = time() . "_" . $file_name;
        $target_file = $target_dir . $unique_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ["jpg", "jpeg", "png"];
        if (!in_array($file_type, $allowed_types)) {
            header("Location: stock.php?status=filetype");
            exit();
        }

        if (move_uploaded_file($_FILES["product_photo"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO products (product_name, product_photo, water_price, container_price, stock) 
                    VALUES (:product_name, :product_photo, :water_price, :container_price, :stock)";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':product_photo', $target_file);
            $stmt->bindParam(':water_price', $water_price);
            $stmt->bindParam(':container_price', $container_price);
            $stmt->bindParam(':stock', $stock);

            if ($stmt->execute()) {
                $message = "New product added: $product_name – Water Price: ₱" . number_format($water_price, 2) .
                ", Container Price: ₱" . number_format($container_price, 2) .
                ", Stock: $stock stock.";
                 $destination = "stock.php";
                 
                 $sql = "INSERT INTO activity_logs (message, date, destination) VALUES (:message, :date, :destination)";
                 $stmt = $conn->prepare($sql);
                 $stmt->bindParam(':message', $message);
                 $stmt->bindParam(':date', $now);
                 $stmt->bindParam(':destination', $destination);
                 $stmt->execute();
                header("Location: stock.php?status=success");
                exit();
            } else {
                header("Location: stock.php?status=error");
                exit();
            }
        } else {
            header("Location: stock.php?status=error");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: stock.php?status=error");
        exit();
    }
} else {
    header("Location: stock.php?status=error");
    exit();
}
?>
