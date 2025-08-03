<?php 
    require ('db.php');
    require ('session.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try{
            $status_id = $_POST['status_id'];
            $order_id = $_POST['order_id'];
            $rider = $_POST['rider'];

            $sql = "SELECT date FROM orders WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindparam(':order_id', $order_id);
            $stmt->execute();
            $date = $stmt->fetchColumn();

            $sql = "UPDATE orders SET status_id = :status_id, rider = :rider, date = :date WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status_id', $status_id);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':rider', $rider);
            $stmt->execute();

            header('Location: orders.php?editstatus=success');
            exit();

        }
        catch (PDOException $e) {
            header("Location: stock.php?editstatus=error");
            exit();
        }
    }

?>