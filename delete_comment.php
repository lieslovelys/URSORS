<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    $is_admin = $_SESSION['is_admin'];

    // Verify ownership or admin status
    $sql = "SELECT user_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();

    if ($comment && ($comment['user_id'] == $user_id || $is_admin)) {
        // Delete the comment
        $delete_sql = "DELETE FROM comments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $comment_id);
        if ($delete_stmt->execute()) {
            header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect back to the previous page
            exit;
        } else {
            echo "Error deleting comment.";
        }
    } else {
        echo "Unauthorized action.";
    }
} else {
    echo "Invalid request.";
}
?>
