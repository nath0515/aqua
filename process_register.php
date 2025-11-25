<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'] ?? '';
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
                    $globalquery = "UPDATE users SET username = :username, password = :password, verification_token = :verification_token, fp_token = :fp_token, role_id = 0 WHERE email = :email";
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

            $mailsent= sendVerificationEmail($email, $verification_token);
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

    function sendVerificationEmail($email, $verification_token) {
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
            $mail->Subject = 'Email Verification';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; color: #343a40;'>
                    <h2 style='color: #343a40;'>Hi,</h2>
                    <p>Thank you for signing up! Please click the button below to verify your email address:</p>
                    
                    <a href='http://aqua-drop.shop/verify_email.php?token=$verification_token' 
                    style='display: inline-block; padding: 12px 24px; color: #ffffff; background-color: #0d6efd; 
                            text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        Verify Email
                    </a>
                    
                    <p style='margin-top: 20px;'>If you did not sign up for this account, you can ignore this email.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>
                    <strong>AquaDrop Team</strong><br>
                    <small><a href='http://aqua-drop.shop' style='color: #0d6efd; text-decoration: none;'>www.aqua-drop.shop</a></small></p>
                </div>
            ";
            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

    ob_end_flush();
?>
