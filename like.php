<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$previous_url = $_SESSION['referrer'];
if (!isset($previous_url)) {
    $previous_url = $_SERVER['HTTP_REFERER'];
    $_SESSION['referrer'] = $previous_url;

// Check if post ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the user has already liked this post
    $check_sql = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User has already liked, so unlike (delete the like)
        $delete_sql = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    } else {
        // User has not liked, so add the like
        $insert_sql = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }

    $stmt->close();
}
}
// Redirect back to the news feed
header("Location: $previous_url");
exit;

?>
