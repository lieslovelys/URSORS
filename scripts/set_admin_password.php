<?php
// CLI only: do not accept passwords through URLs or store plaintext in git.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/config.php';
$password = getenv('ADMIN_PASSWORD');
if (!$password || strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "Set ADMIN_PASSWORD to an 8–72 byte password.\n");
    exit(1);
}
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin001' AND role = 'admin'");
$stmt->bind_param('s', $hash);
$stmt->execute();
echo "Admin password updated.\n";
