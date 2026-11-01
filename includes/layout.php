<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
function e(mixed $value): string { return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">'; }
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'][] = ['message' => $message, 'type' => $type]; }
function go(string $path): never { header('Location: ' . $path, true, 302); exit; }
function current_user(): ?array
{
    global $conn;
    if (empty($_SESSION['user_id']) || !isset($conn)) return null;
    static $loaded = false, $user = null;
    if (!$loaded) {
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); $loaded = true;
    }
    return $user;
}
function display_name(array $user): string { return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username']; }
function nav_icon(string $name): string
{
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v11h-6v-7H9v7H3z"/>',
        'subjects' => '<path d="M12 5v16M3 3l9 2 9-2v16l-9 2-9-2z"/>',
        'notifications' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'ranking' => '<path d="M8 3h8v7a4 4 0 0 1-8 0zM8 5H3v3a5 5 0 0 0 5 5m8-8h5v3a5 5 0 0 1-5 5M12 14v7m-5 0h10"/>',
        'profile' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'help' => '<circle cx="12" cy="12" r="10"/><path d="M9 8a3 3 0 0 1 6 0c0 3-3 2-3 6m0 3v1"/>',
        'logout' => '<path d="M10 3H3v18h7m5-15 6 6-6 6M8 12h13"/>',
        'admin' => '<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6zM8 12l3 3 5-6"/>',
        'pending' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'accounts' => '<circle cx="9" cy="7" r="3"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M16 4a3 3 0 0 1 0 6m3 11v-3a7 7 0 0 0-2-5"/>',
        'create_account' => '<circle cx="9" cy="7" r="3"/><path d="M2 21v-3a7 7 0 0 1 14 0v3m3-14v8m-4-4h8"/>',
        'archive' => '<path d="M3 3h18v5H3zm2 5v13h14V8m-10 4h6"/>',
    ];
    return '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['admin']) . '</svg>';
}
function page_header(string $title, string $active = '', bool $narrow = false): void
{
    $auth = in_array($active, ['login', 'register', 'logout'], true);
    $logged = !empty($_SESSION['user_id']);
    $admin = ($_SESSION['role'] ?? '') === 'admin';
    $count = 0;
    global $conn;
    if ($logged && isset($conn)) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0 AND is_archived=0');
        $stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute(); $count = (int) $stmt->get_result()->fetch_row()[0];
    }
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    if ($auth || $logged) {
        $background = $auth ? 'login_bg.webp' : 'home_bg.webp';
        header('Link: </assets/images/' . $background . '>; rel=preload; as=image; type="image/webp"');
    }
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="csrf-token" content="<?= e($_SESSION['csrf_token']) ?>"><title><?= e($title) ?> · URS SORS</title><link rel="icon" href="/assets/images/title_icon.png"><link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/layout.css"><script src="/assets/js/app.js" defer></script></head><body class="<?= $auth ? 'auth-page auth-' . e($active) : ($logged ? 'with-sidebar' : '') ?>">
    <a class="skip" href="#main">Skip to content</a><header class="site-header"><div class="header-inner"><a class="brand" href="<?= $logged ? '/welcome.php' : '/login.php' ?>"><img src="/assets/images/sors_logo.png" alt="URS SORS home"><small>Learn. Share. Help each other.</small></a><?php if ($logged && !$auth): ?><span class="mobile-page-title"><?= e($title) ?></span><?php endif; ?>
    <?php if ($logged && !$auth && ($account = current_user())): ?>
    <a class="sidebar-account" href="/profile.php" aria-label="View your profile: <?= e(display_name($account)) ?>">
        <img src="<?= e(profile_image($account['profile_picture'] ?? null, 'profile')) ?>" alt="" width="48" height="48">
        <span class="sidebar-account-details"><strong><?= e(display_name($account)) ?></strong><span>@<?= e($account['username']) ?></span><small><?= e(ucfirst($account['role'])) ?></small></span>
    </a>
    <?php endif; ?>
    <nav id="site-nav" class="nav" aria-label="Main navigation">
    <?php $items = $logged ? ['home'=>['Home','/welcome.php'],'subjects'=>['Subjects','/course_page.php'],'notifications'=>['Notifications','/notifications.php'],'ranking'=>['Ranking','/ranking_board.php'],'profile'=>['Profile','/profile.php']] : ['login'=>['Log in','/login.php'],'register'=>['Register','/register.php']];
    if ($logged && $admin) {
        $items += ['admin'=>['Admin','/admin_home.php'], 'pending'=>['Pending Accounts','/admin_pendings.php'], 'accounts'=>['All Accounts','/manage_accounts.php'], 'create_account'=>['Create Account','/admin_register.php'], 'archive'=>['Archived Posts','/admin_archive.php']];
        $adminTabs = ['admin_home.php'=>'admin', 'admin_dashboard.php'=>'admin', 'admin_pendings.php'=>'pending', 'manage_accounts.php'=>'accounts', 'admin_register.php'=>'create_account', 'admin_archive.php'=>'archive'];
        $active = $adminTabs[basename($_SERVER['SCRIPT_NAME'])] ?? $active;
    }
    $items['help'] = ['Help','/help.php'];
    $mobileTabs = $admin ? ['home','pending','accounts','notifications','profile'] : ['home','subjects','notifications','ranking','profile'];
    if ($logged && $admin) {
        $ordered = [];
        foreach ($mobileTabs as $key) $ordered[$key] = $items[$key];
        $items = $ordered + $items;
    }
    $mobileProfileActive = !in_array($active, $mobileTabs, true);
    foreach ($items as $key => [$label,$href]): ?><a class="<?= in_array($key, $mobileTabs, true) ? 'mobile-tab' : 'desktop-tab' ?><?= $key === 'profile' && $mobileProfileActive ? ' mobile-current' : '' ?>" href="<?= e($href) ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><?= nav_icon($key) ?><span class="tab-label"><?php if ($key === 'pending' || $key === 'accounts'): ?><span class="desktop-label"><?= e($label) ?></span><span class="mobile-label"><?= $key === 'pending' ? 'Pending' : 'Accounts' ?></span><?php else: ?><?= e($label) ?><?php endif; ?></span><?php if ($key === 'notifications' && $count): ?><span class="badge" aria-label="<?= $count ?> unread notifications"><?= $count > 99 ? '99+' : $count ?></span><?php endif; ?></a><?php endforeach; ?>
    <?php if ($logged): ?><form class="desktop-tab" method="post" action="/logout.php" data-confirm="Are you sure you want to log out?" data-confirm-title="Log out?" data-confirm-button="Yes, log out"><?= csrf_field() ?><button><?= nav_icon('logout') ?><span>Logout</span></button></form><?php endif; ?></nav></div></header><main id="main" tabindex="-1" class="page <?= $narrow ? 'narrow' : '' ?>">
    <?php foreach ($_SESSION['flash'] ?? [] as $item): ?><div class="flash <?= e($item['type']) ?>" role="status"><?= e($item['message']) ?></div><?php endforeach; unset($_SESSION['flash']);
}
function page_footer(): void
{
    ?></main><footer class="footer"><strong>URS SORS</strong> · A place to learn and share.<br><a href="/help.php">Need help? Start here.</a></footer><dialog id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-description"><img class="confirm-logo" src="/assets/images/sors_logo.png" alt="URS SORS"><h2 id="confirm-title">Please confirm</h2><p id="confirm-description" data-confirm-text></p><div class="actions"><button type="button" class="secondary" data-cancel>Cancel</button><button type="button" data-yes>Yes, continue</button></div></dialog></body></html><?php
}
function profile_navigation(): void
{
    $items = ['help'=>['Help','/help.php']];
    if (($_SESSION['role'] ?? '') === 'admin') {
        $items = ['admin'=>['Admin dashboard','/admin_home.php'], 'subjects'=>['Subjects','/course_page.php'], 'ranking'=>['Ranking','/ranking_board.php'], 'create_account'=>['Create account','/admin_register.php'], 'archive'=>['Archived posts','/admin_archive.php']] + $items;
    }
    $descriptions = ['admin'=>'Manage your community', 'subjects'=>'Browse learning resources', 'ranking'=>'See community contributions', 'create_account'=>'Add an approved account', 'archive'=>'Review archived posts', 'help'=>'Find guides and answers'];
    echo '<section class="panel profile-navigation" aria-labelledby="profile-options-title"><h2 id="profile-options-title">More options</h2><p class="hint">Quick links and account settings</p><nav aria-label="Profile and more options">';
    foreach ($items as $key => [$label, $href]) echo '<a class="button secondary" href="' . e($href) . '"><span class="option-icon">' . nav_icon($key) . '</span><span class="option-copy"><strong>' . e($label) . '</strong><small>' . e($descriptions[$key]) . '</small></span><span class="option-arrow" aria-hidden="true"></span></a>';
    echo '</nav>';
    form_open('/logout.php', 'Are you sure you want to log out?');
    echo '<button class="secondary profile-logout"><span class="option-icon">' . nav_icon('logout') . '</span><span class="option-copy"><strong>Logout</strong><small>Sign out of your account</small></span><span class="option-arrow" aria-hidden="true"></span></button></form></section>';
}
function heading(string $title, string $description = ''): void
{
    echo '<div class="page-head"><h1>' . e($title) . '</h1>' . ($description ? '<p class="muted">' . e($description) . '</p>' : '') . '</div>';
}
function card(string $href, string $title, string $text, string $action = 'Open'): void
{
    echo '<a class="card" href="' . e($href) . '"><strong>' . e($title) . '</strong><span>' . e($text) . '</span><span class="card-action">' . e($action) . ' →</span></a>';
}
function field(string $name, string $label, string $type = 'text', string $hint = '', bool $required = true, string $value = ''): void
{
    global $errors, $screen;
    $password = $type === 'password';
    ?><div class="field"><label for="<?= e($name) ?>"><?= e($label) ?></label><?= $password ? '<div class="password-row">' : '' ?><input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" <?= $required ? 'required' : '' ?> <?= $password ? 'minlength="' . ($screen === 'login' ? '1' : '8') . '" maxlength="72" autocomplete="' . ($screen === 'login' ? 'current-password' : 'new-password') . '"' : 'maxlength="' . ($type === 'email' ? '100' : '50') . '"' ?> value="<?= $password ? '' : e($value) ?>" <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= e($name) ?>-hint <?= e($name) ?>-error"><?php if ($password): ?><button type="button" class="secondary" data-password="<?= e($name) ?>" aria-pressed="false" aria-label="Show or hide password">Show</button></div><?php endif; ?><span id="<?= e($name) ?>-hint" class="hint"><?= e($hint) ?></span><span class="field-error" id="<?= e($name) ?>-error" aria-live="polite"><?= e($errors[$name] ?? '') ?></span></div><?php
}
function form_open(string $action, string $confirm = '', bool $upload = false): void
{
    echo '<form method="post" action="' . e($action) . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . ($upload ? ' enctype="multipart/form-data"' : '') . '>' . csrf_field();
}
function empty_state(string $title, string $text, string $href = '/course_page.php', string $button = 'Browse subjects'): void
{
    echo '<div class="empty"><h2>' . e($title) . '</h2><p>' . e($text) . '</p><a class="button secondary" href="' . e($href) . '">' . e($button) . '</a></div>';
}
