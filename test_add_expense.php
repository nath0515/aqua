<?php
require 'db.php';

echo "<h2>🧪 Test Adding Expense Data</h2>";

try {
    // Add a test expense
    $test_comment = "Test expense - " . date('Y-m-d H:i:s');
    $test_amount = 1000;
    
    $sql = "INSERT INTO expense (expensetype_id, comment, amount) VALUES (2, :comment, :amount)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':comment', $test_comment);
    $stmt->bindParam(':amount', $test_amount);
    $stmt->execute();
    
    echo "<p>✅ Added test expense: ₱" . number_format($test_amount, 2) . "</p>";
    
    // Check today's total
    $today = date('Y-m-d');
    $sql = "SELECT SUM(amount) as total_expense FROM expense WHERE DATE(date) = :today";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $today_expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total Expenses Today:</strong> ₱" . number_format($today_expense['total_expense'] ?? 0, 2) . "</p>";
    
    echo "<p><a href='index.php'>View Dashboard</a> | <a href='debug_expenses.php'>Debug Expenses</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
