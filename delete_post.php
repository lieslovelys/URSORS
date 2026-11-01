<?php
session_start();
include 'config.php';

$previous_url = $_SESSION['referrer'];
if (!isset($previous_url)) {
    $previous_url = $_SERVER['HTTP_REFERER'];
    $_SESSION['referrer'] = $previous_url;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("login.php");
    exit;
}

// Check if a post ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    
        // Post belongs to user, proceed with deletion
        $delete_stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $delete_stmt->bind_param("i", $post_id);
        if ($delete_stmt->execute()){
            // Notify the user
            $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $message = "Your post titled '{$post_title}' has been deleted by an admin or teacher.";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $post['user_id'], $message);
            $stmt->execute();
        }
        $delete_stmt->close();
    
        


}

// Redirect back to the news feed
header("Location: $previous_url");
exit;
?>
