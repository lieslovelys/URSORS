<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
require_post();
// Preserve posts and dependent data: deletion is a reversible archive operation.
$postId = (int) ($_POST['post_id'] ?? 0);
$stmt = $conn->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) fail_request('Post not found.', 404);
$stmt = $conn->prepare('UPDATE posts SET is_archived = 1 WHERE id = ?');
$stmt->bind_param('i', $postId);
$stmt->execute();
notify_user($conn, (int) $post['user_id'], 'Your post was archived by an admin.', $postId);
flash('Post removed from the feed. It is kept in Archived Posts.');
redirect_back();
