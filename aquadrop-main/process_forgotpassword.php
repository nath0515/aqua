<?php
    require 'db.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $token = $_POST['token'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            $sql = "SELECT email FROM users WHERE fp_token = :fp_token";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':fp_token', $token);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $email = $stmt->fetchColumn();
                $globalquery = "UPDATE users SET password = :password, fp_token = NULL WHERE fp_token = :fp_token AND email = :email";
            }
            if ($password == $confirm_password) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            } else {
                header("Location: forgot_password.php?status=notmatch&token=".$token);
                exit();
            }
            $stmt = $conn->prepare($globalquery);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':fp_token', $token);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            header("Location: forgot_password.php?status=success");
            exit();
            
        } catch (PDOException $e) {
            header("Location: fogrot_password.php?status=error");
            exit();
        }
    }

?>