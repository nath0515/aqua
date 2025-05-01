<?php
    require 'db.php';
    session_start();
    date_default_timezone_set('Asia/Manila');
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $token = $_POST['token'];
        $username = $_POST['username'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $address = $_POST['address'];
        $contact_number = $_POST['contact_number'];

       
        if (strlen($contact_number) != 11 && substr($contact_number, 0, 2) != "09") {
            header('Location: verify_email.php?status=invalidphone&token='.$token);
            exit();
        }


        try {
            $sql = "SELECT user_id, role_id FROM users WHERE verification_token = :verification_token";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':verification_token', $token);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                header('Location: 401.html');
                exit();
            }
            $lastId = $user['user_id'];
            
            $sql = "SELECT username FROM users WHERE username = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                header('Location: verify_email.php?status=exist&token='.$token);
                exit();
            }
            $sql = "UPDATE users SET username = :username, role_id = 2 WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':user_id', $lastId);
            $stmt->execute();

            $sql = "INSERT INTO user_details (user_id, firstname, lastname, address, contact_number) 
                VALUES (:user_id, :firstname, :lastname, :address, :contact_number)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $lastId);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->execute();

            $sql = "UPDATE users SET verification_token = NULL WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $lastId);
            $stmt->execute();

            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $lastId;
            $_SESSION['loggedin'] = true;
            $_SESSION['role_id'] = $user['role_id'];

            header("Location: home.php");
            exit();
            
        } catch (PDOException $e) {
            header("Location: verify_email.php?status=error&token=".$token);
            exit();
        }
    }

?>