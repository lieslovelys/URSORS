<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_login();
require_post();
can_post($conn);
$content = trim($_POST['content'] ?? '');
if ($content === '') {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Post content is required.']));
}
$stmt = $conn->prepare('INSERT INTO posts (user_id, content) VALUES (?, ?)');
$stmt->bind_param('is', $_SESSION['user_id'], $content);
$stmt->execute();
echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'message' => 'Your post was shared. Thank you!']);
