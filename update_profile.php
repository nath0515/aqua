<?php
require 'session.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);

    $name_parts = preg_split('/\s+/', $fullname, 2);
    $firstname = $name_parts[0];
    $lastname = isset($name_parts[1]) ? $name_parts[1] : '';

    try {
        // Update users
        $sql = "UPDATE users SET email = :email WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $user_id
        ]);

        // Update user_details
        $sql = "UPDATE user_details SET firstname = :firstname, lastname = :lastname, contact_number = :contact_number WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':contact_number' => $contact_number,
            ':user_id' => $user_id
        ]);

        // Show SweetAlert success
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
        exit;

    } catch (PDOException $e) {
        // Show SweetAlert error
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
        exit;
    }
} else {
    header("Location: profile.php");
    exit;
}
