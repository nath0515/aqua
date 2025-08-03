<?php
require ('db.php');
require ('session.php');

// Check if product ID is provided
if (isset($_GET['id'])) {
    $productId = intval($_GET['id']); // Always sanitize inputs

    try {

        $sql = "SELECT product_name FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        $product_name = $stmt->fetchColumn();
        // Prepare and execute delete query
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $productId);
        $now = date("Y-m-d H:i:s");
        $stmt->execute();

        $message = "Deleted the product: $product_name.";
        $destination = "stock.php";
        
        $sql = "INSERT INTO activity_logs (message, date, destination) VALUES (:message, :date, :destination)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':date', $now);
        $stmt->bindParam(':destination', $destination);
        $stmt->execute();

        // Redirect back with success flag
        header("Location: stock.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        // Log error and redirect with error flag
        error_log("Delete error: " . $e->getMessage());
        header("Location: stock.php?deleted=0");
        exit();
    }
} else {
    // Redirect if no ID provided
    header("Location: stock.php");
    exit();
}
?>
