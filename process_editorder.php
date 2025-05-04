<?php 
    require ('db.php');
    require ('session.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try{
            $status_id = $_POST['status_id'];
            $order_id = $_POST['order_id'];
            $rider = $_POST['rider'];

            $sql = "UPDATE orders SET status_id = :status_id, rider = :rider WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status_id', $status_id);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':rider', $rider);
            $stmt->execute();

            $sql = "SELECT firstname, lastname FROM orders JOIN users ON orders.rider = users.user_id WHERE orders.order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            $rider_data = $stmt->fetch();
            $firstname = $rider_data['firstname'];
            $lastname = $rider_data['lastname'];

            
            $message = "ORDER #{$order_id} - Successfully delivered by Rider: {$firstname} {$lastname}.";
            $now = date('Y-m-d H:i:s');

            $sql = "INSERT INTO activity_logs (message, date, destination) VALUES (:message, :date, :destination)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':destination', $destination);
            $stmt->execute();

            header('Location: deliveryhistory.php?editstatus=success');
            exit();

        }
        catch (PDOException $e) {
            header("Location: deliveryhistory.php?editstatus=error");
            exit();
        }
    }

?>