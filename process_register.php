<?php
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'db.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    ob_start();

    $globalquery = "INSERT INTO users (email, password, verification_token, role_id) VALUES (:email, :password, :verification_token, 0)";
    $verification_token = bin2hex(random_bytes(16));

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
                    $globalquery = "UPDATE users SET password = :password, verification_token = :verification_token, role_id = 0 WHERE email = :email";
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
            $stmt->bindParam(':verification_token', $verification_token);
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
            header("Location: register.php?status=error");
            exit();
        }
    }

    function sendVerificationEmail($email, $verification_token) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'techsupport@aqua-drop.shop';
            $mail->Password = '8=4u?LaKm062';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 465;
            $mail->SMTPDebug = 2;
    
            $mail->setFrom('techsupport@aqua-drop.shop', 'Aqua Drop');
            $mail->addAddress($email);
    
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification';
            $mail->Body = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa;'>
                            <h2 style='color: #343a40;'>Hi,</h2>
                            <p>Click the link below to verify your email address:</p>
                            <a href='http://aqua-drop.shop/aquadrop/verify_email.php?token=$verification_token' 
                            style='display: inline-block; padding: 10px 20px; color: #fff; background-color: #0d6efd; 
                                    text-decoration: none; border-radius: 5px;'>
                            Verify Email
                            </a>
                            <p style='margin-top: 20px;'>Thank you.</p>
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