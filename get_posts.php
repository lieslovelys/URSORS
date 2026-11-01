<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$sql = "SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.is_archived = 0 
        ORDER BY posts.created_at DESC";
$result = $conn->query($sql);


$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}

echo json_encode(['status' => 'success', 'posts' => $posts]);
$conn->close();
?>
