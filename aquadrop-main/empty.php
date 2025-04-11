<?php

require ('db.php');

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $sql = "SELECT * FROM users WHERE verification_token = :token AND role_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $sql_update = "UPDATE users SET role_id = 2, verification_token = NULL WHERE verification_token = :token";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':token', $token);
        $stmt_update->execute();

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Email Verified!',
                text: 'Your email has been successfully verified!',
                showConfirmButton: true,
            }).then(function() {
                window.location = 'login.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid or Expired Link',
                text: 'The verification link is either invalid or has expired.',
                showConfirmButton: true,
            }).then(function() {
                window.location = 'login.php';
            });
        </script>";
    }
} else {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'No Token Provided',
            text: 'Invalid verification token.',
            showConfirmButton: true,
        }).then(function() {
                window.location = 'login.php';
            });
    </script>";
}
?>