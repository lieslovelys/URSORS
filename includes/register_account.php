<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
function register_account(array $input, bool $adminCreates): array
{
    global $conn;
    $values = [];
    $errors = [];
    foreach (['first_name','middle_name','last_name','email','username','role'] as $key) $values[$key] = trim($input[$key] ?? '');
    foreach (['first_name'=>'First name','last_name'=>'Last name','username'=>'Username'] as $key=>$label) {
        if ($values[$key] === '') $errors[$key] = 'Please enter your ' . strtolower($label) . '.';
        elseif (strlen($values[$key]) > 50) $errors[$key] = $label . ' is too long. Please use a shorter one.';
    }
    if (strlen($values['middle_name']) > 50) $errors['middle_name'] = 'Middle name is too long.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 100) $errors['email'] = 'Enter a complete email address, like name@example.com.';
    if (!in_array($values['role'], $adminCreates ? ['student','teacher','admin'] : ['student','teacher'], true)) $errors['role'] = 'Please choose your role at school.';
    $plain = $input['password'] ?? '';
    if (strlen($plain) < 8 || strlen($plain) > 72) $errors['password'] = 'Use a password with 8–72 bytes. For simple English text, that is 8–72 characters.';
    if ($errors) return ['errors'=>$errors,'status'=>400];
    $password = password_hash($plain, PASSWORD_BCRYPT);
    $approved = $adminCreates ? 1 : 0;
    $stmt = $conn->prepare('INSERT INTO users (first_name,middle_name,last_name,email,username,password,role,is_approved) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('sssssssi', $values['first_name'],$values['middle_name'],$values['last_name'],$values['email'],$values['username'],$password,$values['role'],$approved);
    try { $stmt->execute(); } catch (mysqli_sql_exception $error) {
        if ($error->getCode() === 1062) return ['errors'=>['username'=>'That username or email is already registered. Try logging in, or use a different one.'],'status'=>409];
        throw $error;
    }
    $id = $conn->insert_id;
    if (!$approved) {
        $admins = $conn->query("SELECT id FROM users WHERE role='admin' AND is_approved=1");
        while ($admin = $admins->fetch_assoc()) notify_user($conn,(int)$admin['id'],'New account waiting for approval: ' . $values['first_name'] . ' ' . $values['last_name']);
    }
    return ['errors'=>[],'id'=>$id];
}
