<?php
require 'db.php';

echo "<h2>🔍 Debug Expenses Data</h2>";

try {
    // Check today's expenses
    $today = date('Y-m-d');
    echo "<h3>📅 Today's Expenses ($today)</h3>";
    
    $sql = "SELECT SUM(amount) as total_expense FROM expense WHERE DATE(date) = :today";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $today_expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total Expenses Today:</strong> ₱" . number_format($today_expense['total_expense'] ?? 0, 2) . "</p>";
    
    // Check all expenses
    echo "<h3>📊 All Expenses</h3>";
    $sql = "SELECT * FROM expense ORDER BY date DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($expenses) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Type ID</th><th>Comment</th><th>Amount</th></tr>";
        foreach ($expenses as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['expense_id'] . "</td>";
            echo "<td>" . $expense['date'] . "</td>";
            echo "<td>" . $expense['expensetype_id'] . "</td>";
            echo "<td>" . htmlspecialchars($expense['comment']) . "</td>";
            echo "<td>₱" . number_format($expense['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No expenses found in database</p>";
    }
    
    // Check expense types
    echo "<h3>🏷️ Expense Types</h3>";
    $sql = "SELECT * FROM expensetype";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $expense_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($expense_types) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th></tr>";
        foreach ($expense_types as $type) {
            echo "<tr>";
            echo "<td>" . $type['expensetype_id'] . "</td>";
            echo "<td>" . $type['expensetype_name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the dashboard query
    echo "<h3>🧪 Dashboard Query Test</h3>";
    $sql = "SELECT DATE(date) AS day, SUM(amount) AS total_expense FROM expense GROUP BY DATE(date) ORDER BY day ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $chart_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Chart Data:</strong></p>";
    echo "<pre>";
    print_r($chart_expenses);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
