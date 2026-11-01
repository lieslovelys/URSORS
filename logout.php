<?php
require __DIR__ . '/includes/bootstrap.php';
require_post();
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
flash('You are logged out. See you next time!');
go('/login.php');
