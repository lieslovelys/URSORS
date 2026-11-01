<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $content = $_POST['content'];
    $section = $_POST['section'];
    $sub_section = !empty($_POST['sub_section']) ? $_POST['sub_section'] : null;
    $nested_sub_section = !empty($_POST['nested_sub_section']) ? $_POST['nested_sub_section'] : null;
    $deep_sub_section = !empty($_POST['deep_sub_section']) ? $_POST['deep_sub_section'] : null;

    $image_path = null;
    $file_path = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image_name = basename($_FILES['image']['name']);
        $image_path = 'uploads/images/' . uniqid() . '_' . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['file']['name']);
        $file_path = 'uploads/files/' . uniqid() . '_' . $file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
    }

    // Insert post into database
    $sql = "INSERT INTO posts (user_id, content, section, sub_section, nested_sub_section, deep_sub_section, image_path, file_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssss", $user_id, $content, $section, $sub_section, $nested_sub_section, $deep_sub_section, $image_path, $file_path);

    if ($stmt->execute()) {
        if ($_SESSION['role'] == 'admin') {
            header("Location: home.php?section=" . urlencode($section) . "&sub_section=" . urlencode($sub_section) . "&nested_sub_section=" . urlencode($nested_sub_section). "&deep_sub_section=" . urlencode($deep_sub_section));
        } elseif ($_SESSION['role'] == 'teacher'){
            header("Location: teacher_feed.php?section=" . urlencode($section) . "&sub_section=" . urlencode($sub_section) . "&nested_sub_section=" . urlencode($nested_sub_section). "&deep_sub_section=" . urlencode($deep_sub_section));
        }
        else {
            header("Location: student_feed.php?section=" . urlencode($section) . "&sub_section=" . urlencode($sub_section) . "&nested_sub_section=" . urlencode($nested_sub_section). "&deep_sub_section=" . urlencode($deep_sub_section));
        }
        
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
    
}
?>
