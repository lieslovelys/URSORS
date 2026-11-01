<?php
require __DIR__ . '/includes/bootstrap.php';
go(empty($_SESSION['user_id']) ? '/login.php' : '/welcome.php');
