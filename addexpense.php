<?php 

    require ('session.php');
	require ('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $expensetype_id = $_POST['expensetype_id'];
    $comment = $_POST['comment'];
    $amount = $_POST['amount'];
    $date = $_POST['startDate'];
    $time = $_POST['startTime'];
    $datetime = (!empty($_POST['startDate']) && !empty($_POST['startTime'])) ? $_POST['startDate'] . ' ' . $_POST['startTime'] . ':00' : date('Y-m-d H:i:s');

    try{
        $sql_insert = "INSERT INTO expense (expensetype_id, comment, amount, date) VALUES (:expensetype_id, :comment, :amount, :date)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bindParam(':expensetype_id', $expensetype_id);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':date', $datetime);
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