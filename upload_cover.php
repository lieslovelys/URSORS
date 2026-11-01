<?php
session_start();
include 'config.php';

if (isset($_FILES['cover_photo']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $target_dir = "uploads/";
    $file_name = "cover_" . $user_id . "_" . basename($_FILES["cover_photo"]["name"]);
    $target_file = $target_dir . $file_name;
    
    // Check if file is an image
    $check = getimagesize($_FILES["cover_photo"]["tmp_name"]);
    if ($check !== false && move_uploaded_file($_FILES["cover_photo"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
        $stmt->bind_param("si", $file_name, $user_id);
        $stmt->execute();
        $_SESSION['cover_photo'] = $file_name;
        header("Location: profile.php");
    } else {
        echo "Error uploading cover photo.";
    }
}
?>
