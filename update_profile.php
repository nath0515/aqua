<?php
ob_start(); // Ensure no output before headers

require 'session.php';
require 'db.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    try {
        // Update users
        $sql = "UPDATE users SET email = :email WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([ ':email' => $email, ':user_id' => $user_id ]);

        // Update user_details
        $sql = "UPDATE user_details 
                SET firstname = :firstname, lastname = :lastname, contact_number = :contact_number, address = :address 
                WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([ 
            ':firstname' => $firstname, 
            ':lastname' => $lastname, 
            ':contact_number' => $contact_number, 
            ':address' => $address, 
            ':user_id' => $user_id 
        ]);

        // Success response
        $response['status'] = 'success';
    } catch (PDOException $e) {
        // Error response
        $response['status'] = 'error';
        $response['error'] = $e->getMessage();
    }
} else {
    // If it's not a POST request
    $response['status'] = 'error';
    $response['error'] = 'Invalid request method';
}

ob_end_flush();
echo json_encode($response);
exit;
?>
