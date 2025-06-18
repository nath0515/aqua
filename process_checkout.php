<?php
header('Content-Type: application/json');
require 'session.php';
require 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$now = date('Y-m-d H:i:s');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate input
if (!isset($_POST['items']) || !isset($_POST['payment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$data_items = json_decode($_POST['items'], true);
$payment_id = intval($_POST['payment_id']);

if (!is_array($data_items) || $payment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Handle file upload
$proof_path = null;
if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
    $filename = uniqid("proof_", true) . '.' . $ext;
    $upload_dir = __DIR__ . '/uploads/proofs/';
    $target_path = $upload_dir . $filename;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target_path)) {
        $proof_path = 'uploads/proofs/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload proof of payment']);
        exit;
    }
}

try {
    $total_amount = 0;

    // Calculate total amount
    foreach ($data_items as $item) {
        $quantity = intval($item['quantity']);
        $with_container = intval($item['with_container']);
        $container_quantity = intval($item['container_quantity']);
        $product_id = intval($item['product_id']);

        $sql = "SELECT water_price, water_price_promo, container_price FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $prices = $stmt->fetch();

        $item_price = ($quantity >= 10) ? $prices['water_price_promo'] : $prices['water_price'];
        $container_price = $prices['container_price'];

        $item_total = $quantity * $item_price;
        if ($with_container) {
            $item_total += $container_quantity * $container_price;
        }

        $total_amount += $item_total;
    }

    $conn->beginTransaction();

    // Insert order with proof of payment
    $stmt = $conn->prepare("
        INSERT INTO orders (date, amount, user_id, status_id, rider, payment_id, proofofpayment) 
        VALUES (:now, :amount, :user_id, 1, 0, :payment_id, :proofofpayment)
    ");
    $stmt->execute([
        ':now' => $now,
        ':amount' => $total_amount,
        ':user_id' => $user_id,
        ':payment_id' => $payment_id,
        ':proofofpayment' => $proof_path
    ]);
    $order_id = $conn->lastInsertId();

    // Insert order items
    $item_stmt = $conn->prepare("
        INSERT INTO orderitems (order_id, product_id, quantity, with_container, container_quantity) 
        VALUES (:order_id, :product_id, :quantity, :with_container, :container_quantity)
    ");

    foreach ($data_items as $item) {
        $item_stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => intval($item['product_id']),
            ':quantity' => intval($item['quantity']),
            ':with_container' => intval($item['with_container']),
            ':container_quantity' => intval($item['container_quantity'])
        ]);
    }

    // Remove items from cart
    $cart_stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = :cart_id");
    foreach ($data_items as $item) {
        $cart_stmt->execute([':cart_id' => $item['cart_id']]);
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Order failed: ' . $e->getMessage()]);
}
?>
