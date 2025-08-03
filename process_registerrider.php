<?php
require 'db.php';
session_start();
date_default_timezone_set('Asia/Manila');
ob_start();

$sql1 = "INSERT INTO users (email, password, username, role_id, verification_token, fp_token, created_at) 
         VALUES (:email, :password, :username, 3, :verification_token, :fp_token, :created_at)";
$sql2 = "INSERT INTO user_details (firstname, lastname, contact_number, user_id, address, drivers_license) 
         VALUES (:firstname, :lastname, :contact_number, :user_id, :address, :license_pic)";
$date = date('Y-m-d H:i:s');
$verification_token = bin2hex(random_bytes(16));
$fp_token = bin2hex(random_bytes(16));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = $_POST['address'] ?? 'Address not set';

    try {
        // ✅ Check if email exists
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $now = date("Y-m-d H:i:s");
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            header("Location: rideraccount.php?status=exist&email=" . $email);
            exit();
        }

        // ✅ Validate password match
        if ($password === $confirm_password) {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        } else {
            header("Location: rideraccount.php?status=notmatch&email=" . $email);
            exit();
        }

        // ✅ Handle driver's license upload
        $license_filename = null;
        if (isset($_FILES['driver_license_pic']) && $_FILES['driver_license_pic']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['driver_license_pic']['tmp_name'];
            $original_name = basename($_FILES['driver_license_pic']['name']);
            $ext = pathinfo($original_name, PATHINFO_EXTENSION);
            $license_filename = 'license_' . uniqid() . '.' . $ext;
            $upload_path = 'uploads/' . $license_filename;

            if (!move_uploaded_file($tmp_name, $upload_path)) {
                header("Location: rideraccount.php?status=uploadfail");
                exit();
            }
        }

        // ✅ Insert into users table
        $stmt = $conn->prepare($sql1);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password_hashed);
        $stmt->bindParam(':verification_token', $verification_token);
        $stmt->bindParam(':fp_token', $fp_token);
        $stmt->bindParam(':created_at', $date);
        $stmt->execute();
        $user_id = $conn->lastInsertId();

        // ✅ Insert into user_details with license filename
        $stmt = $conn->prepare($sql2);
        $stmt->bindParam(':firstname', $firstname);
        $stmt->bindParam(':lastname', $lastname);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':license_pic', $license_filename); // may be null
        $stmt->execute();

        // ✅ Log activity
        $message = "Rider account created: {$firstname} {$lastname} has successfully registered.";
        $destination = "accounts.php";
        $sql = "INSERT INTO activity_logs (message, date, destination) VALUES (:message, :date, :destination)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':date', $now);
        $stmt->bindParam(':destination', $destination);
        $stmt->execute();

        header("Location: accounts.php?status=success");
        exit();

    } catch (PDOException $e) {
        error_log("Rider registration error: " . $e->getMessage());
        header("Location: rideraccount.php?status=error");
        exit();
    }
}

ob_end_flush();
?>
