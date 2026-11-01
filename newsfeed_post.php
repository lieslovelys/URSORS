<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /Admin/FrontEnd/login.html");
    exit;
}

// Check if content is posted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['content'])) {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    // Insert the post into the database
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $content);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the news feed
header("Location: home.php");
exit;
?>
