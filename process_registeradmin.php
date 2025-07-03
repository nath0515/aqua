<?php
require 'db.php'; // Make sure it connects to your database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize input
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $contact_number = trim($_POST['contact_number']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: adminaccount.php?status=notmatch");
        exit();
    }

    // Check if email or username already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        header("Location: adminaccount.php?status=exist");
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();

        // Insert into users
        $stmt1 = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, 1)");
        $stmt1->bindParam(':username', $username);
        $stmt1->bindParam(':email', $email);
        $stmt1->bindParam(':password', $hashedPassword);
        $stmt1->execute();

        $user_id = $conn->lastInsertId();

        // Insert into user_details
        $stmt2 = $conn->prepare("INSERT INTO user_details (user_id, firstname, lastname, contact_number) VALUES (:user_id, :firstname, :lastname, :contact_number)");
        $stmt2->bindParam(':user_id', $user_id);
        $stmt2->bindParam(':firstname', $firstname);
        $stmt2->bindParam(':lastname', $lastname);
        $stmt2->bindParam(':contact_number', $contact_number);
        $stmt2->execute();

        $conn->commit();
        header("Location: adminaccount.php?status=success");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Admin registration failed: " . $e->getMessage());
        header("Location: adminaccount.php?status=error");
        exit();
    }

} else {
    // Prevent direct access
    header("Location: adminaccount.php");
    exit();
}
?>
