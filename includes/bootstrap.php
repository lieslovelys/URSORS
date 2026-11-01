<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
define('SORS_APP', true);
require_once __DIR__ . '/app.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$logDirectory = dirname(__DIR__) . '/var/log';
if (!is_dir($logDirectory)) mkdir($logDirectory, 0755, true);
ini_set('error_log', $logDirectory . '/app.log');
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
require_once __DIR__ . '/layout.php';
set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    http_response_code(500);
    page_header('Something went wrong');
    heading('Something went wrong.', 'We could not finish that request. Please try again in a moment.');
    echo '<a class="button" href="/welcome.php">Go back Home</a><p><a href="/help.php">Get help</a></p>';
    page_footer();
});
set_error_handler(function (int $level, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $level)) return false;
    throw new ErrorException($message, 0, $level, $file, $line);
});
require_once dirname(__DIR__) . '/config.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) fail_request('This form has expired. Go back, refresh the page, and try again.', 403);
}
