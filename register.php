<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.html');
    exit;
}
require __DIR__ . '/includes/register_account.php';
echo 'Registration successful! Wait for admin approval. <a href="/login.html">Login</a>';
