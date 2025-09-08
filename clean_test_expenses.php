<?php
require 'db.php';

echo "<h2>🧹 Clean Test Expense Data</h2>";

try {
    // Check for large expense values that look like test data
    echo "<h3>🔍 Finding Large Test Expenses</h3>";
    $sql = "SELECT * FROM expense WHERE amount > 1000000 ORDER BY amount DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $large_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($large_expenses) {
        echo "<p>⚠️ Found " . count($large_expenses) . " large expense entries that look like test data:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Type ID</th><th>Comment</th><th>Amount</th></tr>";
        foreach ($large_expenses as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['expense_id'] . "</td>";
            echo "<td>" . $expense['date'] . "</td>";
            echo "<td>" . $expense['expensetype_id'] . "</td>";
            echo "<td>" . htmlspecialchars($expense['comment']) . "</td>";
            echo "<td>₱" . number_format($expense['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>🗑️ Clean Up Options</h3>";
        echo "<form method='POST' style='margin: 10px 0;'>";
        echo "<button type='submit' name='delete_large' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px;'>Delete All Large Test Expenses (>₱1,000,000)</button>";
        echo "</form>";
        
        echo "<form method='POST' style='margin: 10px 0;'>";
        echo "<button type='submit' name='delete_test' style='padding: 10px 20px; background: #ffc107; color: black; border: none; border-radius: 5px;'>Delete Test Expenses (comments like 'hhjhjh', 'asd')</button>";
        echo "</form>";
        
    } else {
        echo "<p>✅ No large test expenses found.</p>";
    }
    
    // Handle deletion requests
    if (isset($_POST['delete_large'])) {
        $sql = "DELETE FROM expense WHERE amount > 1000000";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $deleted = $stmt->rowCount();
        echo "<p>✅ Deleted $deleted large test expenses.</p>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }
    
    if (isset($_POST['delete_test'])) {
        $sql = "DELETE FROM expense WHERE comment IN ('hhjhjh', 'asd', 'test', 'Test')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $deleted = $stmt->rowCount();
        echo "<p>✅ Deleted $deleted test expenses.</p>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }
    
    // Show current expense summary
    echo "<h3>📊 Current Expense Summary</h3>";
    $sql = "SELECT 
                COUNT(*) as total_expenses,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MAX(amount) as max_amount,
                MIN(amount) as min_amount
            FROM expense";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    echo "<li><strong>Total Expenses:</strong> " . $summary['total_expenses'] . "</li>";
    echo "<li><strong>Total Amount:</strong> ₱" . number_format($summary['total_amount'], 2) . "</li>";
    echo "<li><strong>Average Amount:</strong> ₱" . number_format($summary['avg_amount'], 2) . "</li>";
    echo "<li><strong>Max Amount:</strong> ₱" . number_format($summary['max_amount'], 2) . "</li>";
    echo "<li><strong>Min Amount:</strong> ₱" . number_format($summary['min_amount'], 2) . "</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>View Dashboard</a> | <a href='expenses.php'>View Expenses</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
