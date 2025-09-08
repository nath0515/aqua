<?php
require 'db.php';

echo "<h2>🧪 Test Dashboard Data Connection</h2>";

try {
    $today = date('Y-m-d');
    
    // Test the exact same queries as the dashboard
    echo "<h3>📊 Dashboard Queries Test</h3>";
    
    // 1. Today's sales
    $sql = "SELECT SUM(amount) as total_sales FROM orders WHERE status_id = 5 AND DATE(date) = :dateNow";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $today);
    $stmt->execute();
    $amount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Sales Today:</strong> ₱" . number_format($amount['total_sales'] ?? 0, 2) . "</p>";
    
    // 2. Today's expenses
    $sql = "SELECT SUM(amount) as total_expense FROM expense WHERE DATE(date) = :dateNow";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $today);
    $stmt->execute();
    $amount1 = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Expenses Today:</strong> ₱" . number_format($amount1['total_expense'] ?? 0, 2) . "</p>";
    
    // 3. Today's orders
    $sql = "SELECT count(*) as order_count FROM orders WHERE DATE(date) = :dateNow";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':dateNow', $today);
    $stmt->execute();
    $orders = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Orders Today:</strong> " . ($orders['order_count'] ?? 0) . "</p>";
    
    // 4. Net Income calculation
    $net_income = (($amount['total_sales'] ?? 0) - ($amount1['total_expense'] ?? 0));
    echo "<p><strong>Net Income Today:</strong> ₱" . number_format($net_income, 2) . "</p>";
    
    // 5. Check for any large expense values
    echo "<h3>🔍 Check for Large Expense Values</h3>";
    $sql = "SELECT * FROM expense WHERE amount > 1000000 ORDER BY amount DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $large_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($large_expenses) {
        echo "<p>⚠️ Found large expense values:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Comment</th><th>Amount</th></tr>";
        foreach ($large_expenses as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['expense_id'] . "</td>";
            echo "<td>" . $expense['date'] . "</td>";
            echo "<td>" . htmlspecialchars($expense['comment']) . "</td>";
            echo "<td>₱" . number_format($expense['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>✅ No large expense values found</p>";
    }
    
    // 6. Test chart data
    echo "<h3>📈 Chart Data Test</h3>";
    $sql = "SELECT DATE(date) AS day, SUM(amount) AS total_expense FROM expense GROUP BY DATE(date) ORDER BY day ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $chart_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Chart Expenses Data:</strong></p>";
    if ($chart_expenses) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Date</th><th>Total Expense</th></tr>";
        foreach ($chart_expenses as $row) {
            echo "<tr>";
            echo "<td>" . $row['day'] . "</td>";
            echo "<td>₱" . number_format($row['total_expense'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No chart data found</p>";
    }
    
    echo "<p><a href='index.php'>View Dashboard</a> | <a href='debug_expenses.php'>Debug Expenses</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
