<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_post();
can_post($conn);
$userId = (int) $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
if ($content === '') fail_request('Post content is required.');
$section = $_POST['section'] ?? 'General';
if ($section === 'All' || $section === '') $section = 'General';
$subSection = $_POST['sub_section'] ?? '';
$nested = $_POST['nested_sub_section'] ?? '';
$deep = $_POST['deep_sub_section'] ?? '';
if ($deep === 'Professor Only' && $_SESSION['role'] === 'student') fail_request('Only teachers and admins may post in Professor Only.', 403);
foreach ([$section, $subSection, $nested, $deep] as $value) {
    if (strlen($value) > 255) fail_request('Course context is too long.');
}
$image = uploaded_file('image', 'images', true);
$file = uploaded_file('file', 'files');
if (!$image && $file && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) { $image = $file; $file = null; }
$stmt = $conn->prepare('INSERT INTO posts (user_id, content, section, sub_section, nested_sub_section, deep_sub_section, image_path, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('isssssss', $userId, $content, $section, $subSection, $nested, $deep, $image, $file);
$stmt->execute();
flash('Your post was shared. Thank you!');
header('Location: /' . feed_page() . '?' . http_build_query(['section' => $section, 'sub_section' => $subSection, 'nested_sub_section' => $nested, 'deep_sub_section' => $deep]));
exit;
