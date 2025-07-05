<?php
ob_start();

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

    // File upload handling
    $profile_pic = null;
    $upload_dir = 'uploads/profile_pictures/';
    $upload_path = '';

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = basename($_FILES['profile_pic']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($file_ext, $allowed_exts)) {
            $response['status'] = 'error';
            $response['error'] = 'Invalid image format. Only JPG, PNG, GIF, and WEBP allowed.';
            echo json_encode($response);
            exit;
        }

        $new_file_name = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $upload_path)) {
            $response['status'] = 'error';
            $response['error'] = 'Failed to upload image.';
            echo json_encode($response);
            exit;
        }

        $profile_pic = $new_file_name;
    }

    try {
        // Update users
        $sql = "UPDATE users SET email = :email WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $user_id
        ]);

        // Update user_details with or without profile picture
        if ($profile_pic) {
            $sql = "UPDATE user_details 
                    SET firstname = :firstname, lastname = :lastname, 
                        contact_number = :contact_number, address = :address, 
                        profile_pic = :profile_pic 
                    WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':contact_number' => $contact_number,
                ':address' => $address,
                ':profile_pic' => $profile_pic,
                ':user_id' => $user_id
            ]);
        } else {
            $sql = "UPDATE user_details 
                    SET firstname = :firstname, lastname = :lastname, 
                        contact_number = :contact_number, address = :address 
                    WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':contact_number' => $contact_number,
                ':address' => $address,
                ':user_id' => $user_id
            ]);
        }

        $response['status'] = 'success';
    } catch (PDOException $e) {
        $response['status'] = 'error';
        $response['error'] = $e->getMessage();
    }
} else {
    $response['status'] = 'error';
    $response['error'] = 'Invalid request method';
}

ob_end_clean();
echo json_encode($response);
exit;
?>
