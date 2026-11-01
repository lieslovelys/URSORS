<?php
// PHP development-server router. Do not expose backups, credentials or source.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$root = __DIR__;
$file = realpath($root . $path);
$blocked = preg_match('~(?:^|/)(?:\.[^/]*|_unused|Database|includes|scripts|docs|var|node_modules)(?:/|$)~i', $path)
    || in_array($path, ['/config.php','/router.php','/package.json','/pnpm-lock.yaml'], true);
if ($blocked || ($file && !str_starts_with($file, $root . '/'))) {
    require $root . '/includes/bootstrap.php';
    fail_request('Choose a page from the menu to continue.', 404);
}
if ($path === '/') { require $root . '/index.php'; return true; }
$redirect = match ($path) { '/login.html' => '/login.php', '/register.html' => '/register.php', default => '' };
if (preg_match('~^/(ce|cpe)_subject_class_page\.html$~', $path, $match)) $redirect = '/course_page.php?program=' . $match[1];
if (preg_match('~^/((?:ce|cpe)_(?:core|elect|prof|gen_ed))\.html$~', $path, $match)) $redirect = '/course_page.php?course=' . $match[1];
if ($redirect) { header('Location: ' . $redirect, true, 302); return true; }
if (str_starts_with($path, '/uploads/')) {
    require $root . '/includes/bootstrap.php';
    require_login();
    if (!$file || !is_file($file) || !preg_match('/\.(jpg|jpeg|png|gif|webp|pdf|docx?|xlsx?|txt|zip|rar)$/i', $file)) fail_request('This file is not available. Please ask the person who shared it.', 404);
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('X-Content-Type-Options: nosniff');
    return false;
}
if ($file && is_file($file) && (preg_match('~^/assets/.*\.(css|js|png|jpe?g|gif|svg|webp|ico)$~i', $path) || preg_match('~^/[a-z_]+\.php$~', $path))) return false;
require $root . '/includes/bootstrap.php';
fail_request('This link may be old. Open Home or choose a subject from the menu.', 404);
