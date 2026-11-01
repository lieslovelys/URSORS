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
function page_header(string $title, string $active = '', bool $narrow = false): void
{
    $logged = !empty($_SESSION['user_id']);
    $admin = ($_SESSION['role'] ?? '') === 'admin';
    $count = 0;
    global $conn;
    if ($logged && isset($conn)) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
        $stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute(); $count = (int) $stmt->get_result()->fetch_row()[0];
    }
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?= e($_SESSION['csrf_token']) ?>"><title><?= e($title) ?> · URS SORS</title><link rel="icon" href="/assets/images/title_icon.png"><link rel="stylesheet" href="/assets/css/app.css"><script src="/assets/js/app.js" defer></script></head><body>
    <a class="skip" href="#main">Skip to content</a><header class="site-header"><div class="header-inner"><a class="brand" href="<?= $logged ? '/welcome.php' : '/login.php' ?>"><img src="/assets/images/sors_logo.png" alt="URS SORS home"><small>Learn. Share. Help each other.</small></a><button type="button" class="secondary menu-toggle" data-menu aria-controls="site-nav" aria-expanded="false">Menu</button><nav id="site-nav" class="nav" aria-label="Main navigation">
    <?php $items = $logged ? ['home'=>['Home','/welcome.php'],'subjects'=>['Subjects','/course_page.php'],'notifications'=>['Notifications' . ($count ? ' (' . $count . ')' : ''),'/notifications.php'],'ranking'=>['Ranking','/ranking_board.php'],'profile'=>['Profile','/profile.php']] : ['login'=>['Log in','/login.php'],'register'=>['Register','/register.php']];
    if ($logged && $admin) $items['admin'] = ['Admin','/admin_home.php'];
    $items['help'] = ['Help','/help.php'];
    foreach ($items as $key => [$label,$href]): ?><a href="<?= e($href) ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
    <?php if ($logged): ?><form method="post" action="/logout.php"><?= csrf_field() ?><button>Logout</button></form><?php endif; ?></nav></div></header><main id="main" class="page <?= $narrow ? 'narrow' : '' ?>">
    <?php foreach ($_SESSION['flash'] ?? [] as $item): ?><div class="flash <?= e($item['type']) ?>" role="status"><?= e($item['message']) ?></div><?php endforeach; unset($_SESSION['flash']);
}
function page_footer(): void
{
    ?></main><footer class="footer"><strong>URS SORS</strong> · A place to learn and share.<br><a href="/help.php">Need help? Start here.</a></footer><dialog id="confirm-dialog" aria-labelledby="confirm-title"><h2 id="confirm-title">Please confirm</h2><p data-confirm-text></p><div class="actions"><button type="button" class="secondary" data-cancel>Cancel</button><button type="button" data-yes>Yes, continue</button></div></dialog></body></html><?php
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
