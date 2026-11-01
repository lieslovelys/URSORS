<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.html');
    exit;
}
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user || !password_verify($password, $user['password'])) fail_request('Invalid username or password.', 401);
if (!$user['is_approved']) fail_request('Your account is pending admin approval.', 403);
session_regenerate_id(true);
$_SESSION = ['user_id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
header('Location: /' . ($user['role'] === 'admin' ? 'admin_home.php' : 'welcome.php'));
exit;
