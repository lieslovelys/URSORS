<?php
require __DIR__ . '/includes/bootstrap.php';
require_post();
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
page_header('Logged out', 'logout', true);
heading('You have logged out');
echo '<a class="button logout-login" href="/login.php">Log in again</a>';
page_footer();
