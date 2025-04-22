<?php
    require 'db.php';
    session_start();
    date_default_timezone_set('Asia/Manila');

    ob_start();

    $globalquery = "INSERT INTO users (email, password, verification_token, role_id, created_at) VALUES (:email, :password,  3, :created_at)";
    $date = date('Y-m-d H:i:s');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            //check if email exists
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $data = $stmt->fetch();
                if($data['role_id'] != 0){
                    header("Location: register.php?status=exist&email=".$email);
                    exit();
                }
                else{
                    $globalquery = "UPDATE users SET password = :password, role_id = 0 WHERE email = :email";
                }
            }
            if ($password == $confirm_password) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            } else {
                header("Location: register.php?status=notmatch&email=".$email);
                exit();
            }
            $stmt = $conn->prepare($globalquery);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();

            
        } catch (PDOException $e) {
            header("Location: register.php?status=error");
            exit();
        }
    }

    ob_end_flush();
?>