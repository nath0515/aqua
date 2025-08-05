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

            // Create notification for assigned rider
            if ($rider > 0) {
                $rider_sql = "SELECT firstname, lastname FROM user_details WHERE user_id = :rider_id";
                $rider_stmt = $conn->prepare($rider_sql);
                $rider_stmt->bindParam(':rider_id', $rider);
                $rider_stmt->execute();
                $rider_data = $rider_stmt->fetch();
                
                if ($rider_data) {
                    $rider_name = $rider_data['firstname'] . ' ' . $rider_data['lastname'];
                    $notification_message = "New delivery assigned: Order #$order_id - Please check your delivery history";
                    $now = date('Y-m-d H:i:s');
                    
                    $notification_sql = "INSERT INTO activity_logs (message, date, destination) VALUES (:message, :date, 'rider')";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $notification_stmt->execute([
                        ':message' => $notification_message,
                        ':date' => $now
                    ]);
                }
            }

            header('Location: orders.php?editstatus=success');
            exit();

        }
        catch (PDOException $e) {
            header("Location: stock.php?editstatus=error");
            exit();
        }
    }

?>