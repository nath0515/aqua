<?php
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'db.php';
    session_start();
    date_default_timezone_set('Asia/Manila');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $email = $_GET['email'];
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $fp_token = bin2hex(random_bytes(16));
            $sql = "UPDATE users SET fp_token = :fp_token";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':fp_token', $fp_token);
            $stmt->execute();
            
            $mailsent= sendVerificationEmail($email, $fp_token);
            if ($mailsent){
                header("Location: login.php?fstatus=success");
                exit();
            }
            else{
                header("Location: login.php?fstatus=error");
                exit();
            }
        }
        else{
            header("Location: login.php?fstatus=404");
            exit();
        }
    }

    function sendVerificationEmail($email, $fp_token) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'techsupport@aqua-drop.shop';
            $mail->Password = '8=4u?LaKm062';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->SMTPDebug = 2;
    
            $mail->setFrom('techsupport@aqua-drop.shop', 'Aqua Drop');
            $mail->addAddress($email);
    
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Link';
            $mail->Body = "Hi,<br><br>Click the link below to recover your account:<br><br>
                           <a href='http://aqua-drop.shop/forgot_password.php?token=$fp_token'>Recover Password</a><br><br>Thank you.";
    
            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

?>