<?php
require 'db.php';

echo "<h2>🔍 Debug Product Data</h2>";

try {
    // Check product ID 8 specifically
    $product_id = 8;
    $sql = "SELECT * FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<h3>📦 Product ID: $product_id</h3>";
        echo "<ul>";
        echo "<li><strong>Product Name:</strong> " . htmlspecialchars($product['product_name']) . "</li>";
        echo "<li><strong>Water Price:</strong> ₱" . number_format($product['water_price'], 2) . "</li>";
        echo "<li><strong>Container Price:</strong> ₱" . number_format($product['container_price'], 2) . "</li>";
        echo "<li><strong>Stock:</strong> " . $product['stock'] . "</li>";
        echo "</ul>";
        
        if ($product['container_price'] <= 0) {
            echo "<p>✅ <strong>Container price is 0 or negative</strong> - 'With Container' option should be hidden</p>";
        } else {
            echo "<p>⚠️ <strong>Container price is greater than 0</strong> - 'With Container' option will be shown</p>";
        }
    } else {
        echo "<p>❌ Product ID $product_id not found</p>";
    }
    
    // Check all products
    echo "<h3>📋 All Products</h3>";
    $sql = "SELECT * FROM products ORDER BY product_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($products) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Water Price</th><th>Container Price</th><th>Stock</th><th>Should Hide Container?</th></tr>";
        foreach ($products as $prod) {
            $shouldHide = $prod['container_price'] <= 0 ? 'YES' : 'NO';
            $hideColor = $shouldHide === 'YES' ? 'background-color: #d4edda;' : 'background-color: #f8d7da;';
            echo "<tr style='$hideColor'>";
            echo "<td>" . $prod['product_id'] . "</td>";
            echo "<td>" . htmlspecialchars($prod['product_name']) . "</td>";
            echo "<td>₱" . number_format($prod['water_price'], 2) . "</td>";
            echo "<td>₱" . number_format($prod['container_price'], 2) . "</td>";
            echo "<td>" . $prod['stock'] . "</td>";
            echo "<td><strong>" . $shouldHide . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the API endpoint
    echo "<h3>🧪 Test API Endpoint</h3>";
    echo "<p>Testing process_getproductdata.php for product ID $product_id:</p>";
    
    // Simulate the POST request
    $_POST['product_id'] = $product_id;
    ob_start();
    include 'process_getproductdata.php';
    $api_response = ob_get_clean();
    
    echo "<pre>";
    echo htmlspecialchars($api_response);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
