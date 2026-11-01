<?php
session_start();
include 'config.php';

if (isset($_FILES['profile_picture']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $target_dir = "uploads/";
    $file_name = "profile_" . $user_id . "_" . basename($_FILES["profile_picture"]["name"]);
    $target_file = $target_dir . $file_name;
    
    // Check if file is an image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check !== false && move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $file_name, $user_id);
        $stmt->execute();
        $_SESSION['profile_picture'] = $file_name;
        header("Location: profile.php");
    } else {
        echo "Error uploading profile picture.";
    }
}
?>
