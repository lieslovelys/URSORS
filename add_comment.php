<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $content = trim($_POST['comment_content']);
    $file_path = null;

    if (isset($_FILES['comment_file']) && $_FILES['comment_file']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $upload_dir = 'uploads/comments/';
        $file_name = basename($_FILES['comment_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file extension
        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = uniqid('comment_') . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            // Create directory if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Move the uploaded file to the target directory
            if (!move_uploaded_file($_FILES['comment_file']['tmp_name'], $file_path)) {
                $file_path = null;
            }
        } else {
            echo "Unsupported file type.";
            exit;
        }
    }

    // Insert the comment into the database
    $sql = "INSERT INTO comments (post_id, user_id, content, file_path, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $post_id, $user_id, $content, $file_path);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Error adding comment: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
