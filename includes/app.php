<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function fail_request(string $message, int $status = 400): never
{
    http_response_code($status);
    exit(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
}
function feed_page(): string
{
    return match ($_SESSION['role'] ?? '') {
        'admin' => 'home.php', 'teacher' => 'teacher_feed.php', default => 'student_feed.php'
    };
}
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.html');
        exit;
    }
    global $conn;
    $stmt = $conn->prepare('SELECT role, is_approved FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || !$user['is_approved']) {
        $_SESSION = [];
        fail_request('Account is not approved.', 403);
    }
    $_SESSION['role'] = $user['role'];
}
function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') fail_request('Access denied. Admins only.', 403);
}
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        fail_request('POST required.', 405);
    }
}
function redirect_back(): never
{
    $referer = parse_url($_SERVER['HTTP_REFERER'] ?? '');
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $path = $referer['path'] ?? '';
    $safe = $path !== '' && !str_starts_with($path, '//')
        && (!isset($referer['host']) || $referer['host'] === explode(':', $host)[0])
        && preg_match('~^/[a-zA-Z0-9_]+\.(php|html)$~', $path);
    header('Location: ' . ($safe ? $path . (isset($referer['query']) ? '?' . $referer['query'] : '') : '/' . feed_page()));
    exit;
}
function notify_user(mysqli $conn, int $userId, string $message): void
{
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
    $stmt->bind_param('is', $userId, $message);
    $stmt->execute();
}
function can_post(mysqli $conn): void
{
    $stmt = $conn->prepare('SELECT can_post FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    if (!(int) ($stmt->get_result()->fetch_assoc()['can_post'] ?? 0)) fail_request('Your posting permission is disabled.', 403);
}
function uploaded_file(string $field, string $folder, bool $imageOnly = false): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 10 * 1024 * 1024) fail_request('Upload failed or exceeds 10 MB.');
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed = $imageOnly ? $images : array_merge($images, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']);
    if (!in_array($extension, $allowed, true)
        || (in_array($extension, $images, true) && @getimagesize($file['tmp_name']) === false)) fail_request('Unsupported or invalid file.');
    $directory = dirname(__DIR__) . '/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) fail_request('Unable to create upload directory.', 500);
    $path = 'uploads/' . $folder . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $path)) fail_request('Unable to save upload.', 500);
    return $path;
}
function profile_image(?string $name, string $kind): string
{
    $path = 'uploads/' . ltrim($name ?? '', '/');
    return $name && is_file(dirname(__DIR__) . '/' . $path) ? '/' . $path : '/assets/images/default_' . $kind . '.svg';
}
