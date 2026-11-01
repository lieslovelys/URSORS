<?php
session_start();
include 'config.php';

$previous_url = $_SESSION['referrer'];
if (!isset($previous_url)) {
    $previous_url = $_SERVER['HTTP_REFERER'];
    $_SESSION['referrer'] = $previous_url;
}


if (isset($_POST['action']) && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $action = $_POST['action']; // 'archive' or 'restore'

    if ($action === 'archive') {
        $sql = "UPDATE posts SET is_archived = 1 WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);

        if ($stmt->execute()) {
            header("Location: $previous_url");
        } else {
            echo "Error: " . $conn->error;
        }

        $stmt->close();
        $conn->close();

    } elseif ($action === 'restore') {
        $sql = "UPDATE posts SET is_archived = 0 WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);

        if ($stmt->execute()) {
            header("Location: archived_posts.php");
        } else {
            echo "Error: " . $conn->error;
        }

        $stmt->close();
        $conn->close();

    } else {
        echo "Invalid action.";
        exit;
    }

    
}
?>
