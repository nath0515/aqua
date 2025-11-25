<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'db.php';
    session_start();
    date_default_timezone_set('Asia/Manila');
    

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    ob_start();

    $globalquery = "INSERT INTO users (username, email, password, verification_token, fp_token, role_id, created_at) VALUES (:username, :email, :password, :verification_token, :fp_token, 0, :created_at)";
    $verification_token = bin2hex(random_bytes(16));
    $fp_token = bin2hex(random_bytes(16));
    $date = date('Y-m-d H:i:s');

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $username = $_GET['username'] ?? '';
        $email = $_GET['email'];
        $password = $_GET['password'];
        $confirm_password = $_GET['confirm_password'];

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
                    $globalquery = "UPDATE users SET username = :username, password = :password, verification_token = :verification_token, fp_token = :fp_token, role_id = 0, created_at = :created_at WHERE email = :email";
                }
            }
            if ($password == $confirm_password) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            } else {
                header("Location: register.php?status=notmatch&email=".$email);
                exit();
            }
            $stmt = $conn->prepare($globalquery);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':verification_token', $verification_token);
            $stmt->bindParam(':fp_token', $fp_token);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();

            $mailsent = sendVerificationEmail($email, $username, $verification_token);
            if ($mailsent){
                header("Location: register.php?status=success");
                exit();
            }
            else{
                header("Location: register.php?status=error");
                exit();
            }
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            header("Location: register.php?status=error");
            exit();
        }
    }

    function sendVerificationEmail($email, $username, $verification_token) {
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
        $mail->Subject = 'Verify Your Aqua Drop Account';

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; color: #343a40;'>
            <h2 style='color: #0d6efd;'>Hello " . htmlspecialchars($username) . ",</h2>
            <p>Thank you for creating an account with Aqua Drop. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
            
            <p style='text-align: center; margin: 30px 0;'>
                <a href='http://aqua-drop.shop/verify_email.php?token=$verification_token' 
                   style='display: inline-block; padding: 12px 25px; font-size: 16px; color: #ffffff; 
                   background-color: #0d6efd; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                   Verify Email
                </a>
            </p>

            <p>If you did not create this account, please ignore this email or contact our support team.</p>

            <p>Best regards,<br>
            <strong>Aqua Drop Support Team</strong><br>
            <small><a href='http://aqua-drop.shop' style='color: #0d6efd; text-decoration: none;'>www.aqua-drop.shop</a></small></p>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}


    ob_end_flush();
?>
