<?php
// Usage: /usr/bin/php8.3 scripts/seed_starter_posts.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config.php';
define('SORS_APP', true);
require __DIR__ . '/../includes/catalogue.php';
$content = require __DIR__ . '/starter_content.php';
$locked = false;
try {
    $stmt = $conn->prepare('SELECT GET_LOCK(?, 30)');
    $stmt->execute(['sors_starter_seed_v1']);
    $locked = (int)$stmt->get_result()->fetch_row()[0] === 1;
    if (!$locked) throw new RuntimeException('Another starter seed operation is running.');
    $stmt = $conn->prepare('SELECT id, role FROM users WHERE username=?');
    $stmt->execute(['admin001']);
    $admin = $stmt->get_result()->fetch_assoc();
    if (!$admin || $admin['role'] !== 'admin') throw new RuntimeException('admin001 must exist and have the admin role.');
    $subjects = [];
    foreach (catalogue() as $course => $names) {
        foreach ($names as $name) {
            if (!isset($content[$name])) throw new RuntimeException('Missing starter content: ' . $name);
            $subjects[] = [$course, $name, subject_context($course, $name)];
        }
    }
    // Apply the saved, repeatable, non-destructive migration using prepared statements.
    $migration = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/../Database/migrations/002_seed_marker.sql'));
    foreach (explode(';', $migration) as $sql) if (trim($sql) !== '') $conn->prepare(trim($sql))->execute();
    $conn->begin_transaction();
    $find = $conn->prepare('SELECT id FROM posts WHERE seed_marker=?');
    $insert = $conn->prepare('INSERT INTO posts (user_id,content,section,sub_section,nested_sub_section,deep_sub_section,is_archived,created_at,seed_marker) VALUES (?,?,?,?,?,?,0,?,?)');
    $created = 0; $skipped = 0; $index = 0; $total = count($subjects) * 4;
    $now = time();
    foreach ($subjects as [$course, $name, $context]) {
        [$program, $category] = explode('_', $course, 2);
        [$overview, $topics, $books] = $content[$name];
        $application = $program === 'ce' ? 'Use these ideas when thinking about buildings, roads, water systems, and public safety.' : 'Use these ideas when thinking about computing devices, software, connected systems, and their users.';
        $resource = 'MIT OpenCourseWare — free course materials: https://ocw.mit.edu/';
        if ($category === 'gen_ed') $resource = 'Khan Academy — free lessons in arts, humanities, and mathematics: https://www.khanacademy.org/';
        if (in_array($name, ['Komunikasyon at Pananaliksik', 'Pagbasa at Pagsusuri'], true)) $resource = 'Komisyon sa Wikang Filipino — language resources: https://kwf.gov.ph/';
        if (in_array($name, ['Life and Works of Rizal', 'Readings in Philippine History'], true)) $resource = 'National Historical Commission of the Philippines — history resources: https://nhcp.gov.ph/';
        if ($name === 'Advanced Web Development') $resource = 'MDN Web Docs — web development guides: https://developer.mozilla.org/';
        $posts = [
            'welcome' => ['General', "Welcome to {$name}\n{$overview}. {$application} Start with the basics and ask questions as you learn."],
            'topics' => ['General', "Study Topics: {$name}\n- " . implode("\n- ", explode(';', $topics)) . "\nPractice: " . ($program === 'ce' ? 'Apply one topic to a civil engineering project.' : 'Apply one topic to a computer engineering project.')],
            'references' => ['General', "References: {$name}\n- " . implode("\n- ", explode(';', $books)) . "\n- {$resource}\nThese are starting points, not a required reading list. Ask your teacher which chapters fit your class."],
            'professor' => ['Professor Only', "Teaching Materials: {$name}\nTeachers can share syllabi, exams they have permission to share, and teaching materials here for " . program_name($program) . ". Please label the topic and term clearly and keep confidential exam answers private."],
        ];
        foreach ($posts as $type => [$audience, $text]) {
            $marker = 'seed:v1:' . $course . ':' . hash('sha256', $context['nested_sub_section']) . ':' . $type;
            $date = date('Y-m-d H:i:s', $now - 28 * 86400 + (int)(($index + 1) * (28 * 86400 - 60) / $total));
            $index++;
            $find->execute([$marker]);
            if ($find->get_result()->fetch_assoc()) { $skipped++; continue; }
            $insert->execute([$admin['id'], $text, $context['section'], $context['sub_section'], $context['nested_sub_section'], $audience, $date, $marker]);
            $created++;
        }
    }
    $conn->commit();
    echo 'Subjects found: ' . count($subjects) . "; expected posts: {$total}; created: {$created}; already present: {$skipped}\n";
    echo "program | category | subject | General | Professor Only\n";
    $count = $conn->prepare("SELECT SUM(deep_sub_section='General') AS general_count,SUM(deep_sub_section='Professor Only') AS professor_count FROM posts WHERE section=? AND sub_section=? AND nested_sub_section=? AND seed_marker LIKE ? AND is_archived=0");
    $missing = []; $actual = 0;
    foreach ($subjects as [$course, $name, $context]) {
        [$program, $category] = explode('_', $course, 2);
        $count->execute([$context['section'], $context['sub_section'], $context['nested_sub_section'], 'seed:v1:%']);
        $row = $count->get_result()->fetch_assoc();
        $general = (int)$row['general_count']; $professor = (int)$row['professor_count'];
        echo "{$program} | {$category} | {$name} | {$general} | {$professor}\n";
        $actual += $general + $professor;
        if ($general !== 3 || $professor !== 1) $missing[] = $course . ': ' . $name;
    }
    echo "Total active seeded posts: {$actual}\nMissing or unexpected counts: " . ($missing ? implode(', ', $missing) : 'none') . "\n";
    if ($missing || $actual !== $total) throw new RuntimeException('Seed verification failed. Archived seeds are not restored or duplicated.');
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
} finally {
    if ($locked) { $stmt = $conn->prepare('SELECT RELEASE_LOCK(?)'); $stmt->execute(['sors_starter_seed_v1']); }
}
