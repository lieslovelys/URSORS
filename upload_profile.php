<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_post();
$path = uploaded_file('profile_picture', 'profiles', true);
if (!$path) fail_request('Choose a profile picture.');
$filename = substr($path, strlen('uploads/'));
$stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
$stmt->bind_param('si', $filename, $_SESSION['user_id']);
$stmt->execute();
flash('Your profile photo was changed.');
header('Location: /' . ($_SESSION['role'] === 'admin' ? 'admin_profile.php' : 'profile.php'));
exit;
