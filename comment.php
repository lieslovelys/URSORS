<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if content and post ID are provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content'], $_POST['post_id'])) {
    $content = $_POST['content'];
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Insert the comment into the database
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $content);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the news feed
header("Location: home.php");
exit;
?>
