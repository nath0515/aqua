<?php 
require ('db.php');
require ('session.php');
try {
    $today = date('Y-m-d H:i:s');

    $sql = "SELECT status FROM store_status WHERE ss_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $status = $stmt->fetchColumn();

    if ($status) {
        $sql = "UPDATE store_status SET status = 0 WHERE ss_id = 1";

        $sql1 = "SELECT order_id FROM orders WHERE DATE(date) = :today";
        $stmt = $conn->prepare($sql1);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $orders_data = $stmt->fetchAll();

        $sql1 = "SELECT SUM(amount) AS total_sales FROM orders WHERE DATE(date) = :today";
        $stmt = $conn->prepare($sql1);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $total_sales = $stmt->fetchColumn();

        $sql1 = "SELECT expense_id FROM expense WHERE DATE(date) = :today";
        $stmt = $conn->prepare($sql1);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $expense_data = $stmt->fetchAll();

        $sql1 = "SELECT SUM(amount) AS total_expense FROM expense WHERE DATE(date) = :today";
        $stmt = $conn->prepare($sql1);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $total_expense = $stmt->fetchColumn();

        $total_income = $total_sales - $total_expense;

        $sql1 = "INSERT INTO reports (date) VALUES (:today)";
        $stmt = $conn->prepare($sql1);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $lastInsertId = $conn->lastInsertId();

        foreach ($orders_data as $row) {
            $sql2 = "INSERT INTO report_content (report_id, order_id) VALUES (:report_id, :order_id)";
            $stmt = $conn->prepare($sql2);
            $stmt->bindParam(':report_id', $lastInsertId);
            $stmt->bindParam(':order_id', $row['order_id']);
            $stmt->execute();
        }

        foreach($expense_data as $row){
            $sql2 = "INSERT INTO report_expense (report_id, expense_id) VALUES (:report_id, :expense_id)";
            $stmt = $conn->prepare($sql2);
            $stmt->bindParam(':report_id', $lastInsertId);
            $stmt->bindParam(':expense_id', $row['expense_id']);
            $stmt->execute();
        }

        $sql2 = "INSERT INTO report_income (report_id, income) VALUES (:report_id, :income)";
        $stmt = $conn->prepare($sql2);
        $stmt->bindParam(':report_id', $lastInsertId);
        $stmt->bindParam(':income', $total_income);
        $stmt->execute();

    } else {
        $sql = "UPDATE store_status SET status = 1 WHERE ss_id = 1";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    header('Location: index.php');
    exit();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>