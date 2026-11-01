<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/catalogue.php';
if (in_array($screen, ['login', 'register', 'create_account'], true)) {
    if ($screen === 'create_account') require_admin();
    require __DIR__ . '/views/auth.php';
    return;
}
require_login();
if (in_array($screen, ['admin', 'accounts', 'pending'], true) || str_starts_with(basename($_SERVER['SCRIPT_NAME']), 'admin_')) require_admin();
switch ($screen) {
    case 'home': require __DIR__ . '/views/home.php'; break;
    case 'catalogue': require __DIR__ . '/views/catalogue.php'; break;
    case 'feed': case 'archive': require __DIR__ . '/views/feed.php'; break;
    case 'profile': require __DIR__ . '/views/profile.php'; break;
    case 'notifications': require __DIR__ . '/views/notifications.php'; break;
    case 'ranking': require __DIR__ . '/views/ranking.php'; break;
    case 'admin': case 'accounts': case 'pending': require __DIR__ . '/views/admin.php'; break;
    case 'search': require __DIR__ . '/views/search.php'; break;
    default: fail_request('Choose a page from the menu to continue.', 404);
}
