<?php 
require 'db.php';
require 'session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $water_price = $_POST['water_price'];
        $water_price_promo = $_POST['water_price_promo'];
        $container_price = $_POST['container_price'];
        $stock = $_POST['stock'];

        // Check if product name already exists (except for the current product)
        $sql = "SELECT * FROM products WHERE product_name = :product_name AND product_id != :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            header("Location: stock.php?status=exist");
            exit();
        }

        // Check if a new file was uploaded
        if ($_FILES["product_photo"]["size"] > 0) {
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
                // Update product with new photo
                $sql = "UPDATE products SET 
                    product_name = :product_name, 
                    product_photo = :product_photo, 
                    water_price = :water_price, 
                    water_price_promo = :water_price_promo,
                    container_price = :container_price,
                    stock = :stock
                    WHERE product_id = :product_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':product_photo', $target_file);
            } else {
                header("Location: stock.php?status=error");
                exit();
            }
        } else {
            // No new file uploaded, keep the existing photo
            $sql = "UPDATE products SET 
                product_name = :product_name,  
                water_price = :water_price, 
                water_price_promo = :water_price_promo,
                container_price = :container_price,
                stock = :stock
                WHERE product_id = :product_id";
            $stmt = $conn->prepare($sql);
        }

        // Bind common parameters
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':water_price', $water_price);
        $stmt->bindParam(':water_price_promo', $water_price_promo);
        $stmt->bindParam(':container_price', $container_price);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':product_id', $product_id);

        if ($stmt->execute()) {
            header("Location: stock.php?edit=success");
            exit();
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