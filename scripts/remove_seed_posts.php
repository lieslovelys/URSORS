<?php
// Archive only this seed version. No real posts or notifications are changed.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config.php';
try {
    $stmt = $conn->prepare('SELECT GET_LOCK(?,30)');
    $stmt->execute(['sors_starter_seed_v1']);
    if ((int)$stmt->get_result()->fetch_row()[0] !== 1) throw new RuntimeException('Another seed operation is running.');
    $stmt = $conn->prepare('UPDATE posts SET is_archived=1 WHERE seed_marker LIKE ? AND is_archived=0');
    $stmt->execute(['seed:v1:%']);
    echo 'Archived seeded posts: ' . $stmt->affected_rows . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
} finally {
    $stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->execute(['sors_starter_seed_v1']);
}
