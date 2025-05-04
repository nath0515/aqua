<?php 

    require ('session.php');
	require ('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $expensetype = $_POST['expensetype'];

    try{
        $sql_insert = "INSERT INTO expensetype (expensetype_name) VALUES (:expensetype_name)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bindParam(':expensetype_name', $expensetype);
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