<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}
// Called only from public registration or the protected admin registration page.
$firstName = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$plainPassword = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$adminCreates = basename($_SERVER['SCRIPT_NAME']) === 'admin_register.php' && ($_SESSION['role'] ?? '') === 'admin';
if (!in_array($role, $adminCreates ? ['student', 'teacher', 'admin'] : ['student', 'teacher'], true)) fail_request('Invalid role.');
if ($firstName === '' || $lastName === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($plainPassword) < 8 || strlen($plainPassword) > 72) fail_request('Complete all required fields with a valid email and an 8–72 byte password.');
foreach ([$firstName, $middleName, $lastName, $username] as $value) {
    if (strlen($value) > 50) fail_request('Names and username must be at most 50 bytes.');
}
if (strlen($email) > 100) fail_request('Email is too long.');
$password = password_hash($plainPassword, PASSWORD_BCRYPT);
$approved = $adminCreates ? 1 : 0;
$stmt = $conn->prepare('INSERT INTO users (first_name, middle_name, last_name, email, username, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssssi', $firstName, $middleName, $lastName, $email, $username, $password, $role, $approved);
try {
    $stmt->execute();
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) fail_request('Username or email already registered.', 409);
    throw $error;
}
if (!$approved) {
    $admins = $conn->query("SELECT id FROM users WHERE role = 'admin' AND is_approved = 1");
    while ($admin = $admins->fetch_assoc()) notify_user($conn, (int) $admin['id'], 'New account pending approval: ' . $username);
}
