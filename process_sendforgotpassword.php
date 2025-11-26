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
            $sql = "UPDATE users SET fp_token = :fp_token WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':fp_token', $fp_token);
            $stmt->bindParam(':email', $email);
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
    
            $mail->setFrom('techsupport@aqua-drop.shop', 'Aqua Drop');
            $mail->addAddress($email);
    
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Link';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; color: #343a40;'>
                    <h2 style='color: #0d6efd;'>Password Reset Request</h2>
                    <p>Dear User,</p>
                    <p>We received a request to reset the password associated with this email address. If you made this request, please click the button below to reset your password:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='http://aqua-drop.shop/forgot_password.php?token=$fp_token' 
                            style='display: inline-block; padding: 12px 25px; font-size: 16px; color: #ffffff; background-color: #0d6efd; text-decoration: none; border-radius: 5px;'>
                            Reset Password
                        </a>
                    </p>
                    <p>If you did not request a password reset, please disregard this email or contact our support team if you have any concerns.</p>
                    <p>Thank you,<br><strong>Aqua Drop Support Team</strong></p>
                </div>
            ";
    
            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

?>