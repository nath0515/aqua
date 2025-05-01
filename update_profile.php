<?php
ob_start(); // Ensure no output before headers

require 'session.php';
require 'db.php';

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
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $user_id
        ]);

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

        // SweetAlert success
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Profile updated successfully!',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'profile.php';
                });
            </script>
        ";
        ob_end_flush();
        exit;

    } catch (PDOException $e) {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Update failed!',
                    text: '". addslashes($e->getMessage()) ."',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.history.back();
                });
            </script>
        ";
        ob_end_flush();
        exit;
    }
} else {
    header("Location: profile.php");
    exit;
}
