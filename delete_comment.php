<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_post();
$commentId = (int) ($_POST['comment_id'] ?? 0);
$stmt = $conn->prepare('SELECT user_id FROM comments WHERE id = ? AND is_deleted = 0');
$stmt->bind_param('i', $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();
if (!$comment) fail_request('Comment not found.', 404);
if ($comment['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') fail_request('Unauthorized action.', 403);
$stmt = $conn->prepare('UPDATE comments SET is_deleted = 1 WHERE id = ?');
$stmt->bind_param('i', $commentId);
$stmt->execute();
flash('The comment was removed from the discussion.');
redirect_back();
