<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_post();
$path = uploaded_file('cover_photo', 'covers', true);
if (!$path) fail_request('Choose a cover photo.');
$filename = substr($path, strlen('uploads/'));
$stmt = $conn->prepare('UPDATE users SET cover_photo = ? WHERE id = ?');
$stmt->bind_param('si', $filename, $_SESSION['user_id']);
$stmt->execute();
flash('Your cover photo was changed.');
header('Location: /' . ($_SESSION['role'] === 'admin' ? 'admin_profile.php' : 'profile.php'));
exit;
