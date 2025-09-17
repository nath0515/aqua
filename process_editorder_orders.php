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

            // Get order details for notifications
            $order_sql = "SELECT o.user_id, o.amount, ud.firstname, ud.lastname, os.status_name 
                         FROM orders o 
                         JOIN user_details ud ON o.user_id = ud.user_id 
                         JOIN orderstatus os ON o.status_id = os.status_id 
                         WHERE o.order_id = :order_id";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bindParam(':order_id', $order_id);
            $order_stmt->execute();
            $order_data = $order_stmt->fetch();
            
            $now = date('Y-m-d H:i:s');
            
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
                    
                    $notification_sql = "INSERT INTO activity_logs (message, date, destination, user_id) VALUES (:message, :date, 'rider_orderdetails.php?id=$order_id', :user_id)";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $notification_stmt->execute([
                        ':message' => $notification_message,
                        ':date' => $now,
                        ':user_id' => $rider
                    ]);
                }
            }
            
            // Create notification for customer about order status change
            if ($order_data) {
                $customer_name = $order_data['firstname'] . ' ' . $order_data['lastname'];
                $status_name = $order_data['status_name'];
                $amount = number_format($order_data['amount'], 2);
                
                $customer_notification_message = "Order #$order_id status updated to: $status_name - Amount: ₱$amount";
                
                $customer_notification_sql = "INSERT INTO activity_logs (message, date, destination, user_id) VALUES (:message, :date, 'costumer_orderdetails.php?id=$order_id', :user_id)";
                $customer_notification_stmt = $conn->prepare($customer_notification_sql);
                $customer_notification_stmt->execute([
                    ':message' => $customer_notification_message,
                    ':date' => $now,
                    ':user_id' => $order_data['user_id']
                ]);
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