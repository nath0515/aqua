<?php 

    require ('session.php');
	require ('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $expensetype_id = $_POST['expensetype_id'];
    $comment = $_POST['comment'];
    $amount = $_POST['amount'];

    try{
        $sql_insert = "INSERT INTO expense (expensetype_id, comment, amount) VALUES (:expensetype_id, :comment, :amount)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bindParam(':expensetype_id', $expensetype_id);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();

        header("Location: expenses.php?status=success");
        exit();
        
    
    }
    catch (PDOException $e) {
        header("Location: expenses.php?status=error");
        exit();
    }
}
?>