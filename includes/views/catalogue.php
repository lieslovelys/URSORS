<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$course = $_GET['course'] ?? '';
$program = $_GET['program'] ?? '';
$subject = $_GET['subject'] ?? '';
if ($course && !isset(catalogue()[$course])) fail_request('Choose a program and subject from the Subjects page.', 404);
if ($course) [$program, $category] = explode('_', $course, 2);
if ($program && !in_array($program, ['ce','cpe'], true)) fail_request('Please choose Civil Engineering or Computer Engineering.', 404);
if ($subject && !$course) fail_request('Choose a subject group first.', 404);
$crumbs = [['Subjects','/course_page.php']];
if ($program) $crumbs[] = [program_name($program), '/course_page.php?program=' . $program];
if ($course) $crumbs[] = [category_info($category)[0], '/course_page.php?course=' . $course];
if ($subject) $crumbs[] = [$subject, null];
else $crumbs[count($crumbs)-1][1] = null;
if ($subject) $context = subject_context($course, $subject);
page_header($subject ?: ($course ? category_info($category)[0] : 'Subjects'), 'subjects'); breadcrumbs($crumbs);
if ($subject) {
    heading($subject, 'Choose which posts you want to see.');
    echo '<div class="grid">';
    foreach (['General' => 'Everyone can read and share here when posting is allowed.', 'Professor Only' => 'Read resources shared by teachers and admins. Students can read and comment.'] as $audience => $description)
        card('/' . feed_page() . '?' . http_build_query($context + ['deep_sub_section' => $audience]), $audience, $description, 'Open posts');
    echo '</div>';
} elseif ($course) {
    heading(category_info($category)[0], 'Choose the subject you need.');
    $q = trim($_GET['q'] ?? '');
    ?><form class="page-search" method="get" action="/course_page.php"><input type="hidden" name="course" value="<?= e($course) ?>"><div class="field"><label for="q">Find a subject in this group</label><input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Type a subject name"></div><button>Search subjects</button></form><div class="grid"><?php
    $found = 0;
    foreach (catalogue()[$course] as $name) {
        if ($q && stripos($name, $q) === false) continue;
        $found++; card('/course_page.php?' . http_build_query(['course'=>$course,'subject'=>$name]), $name, 'Read and share learning resources.', 'Choose subject');
    }
    echo '</div>';
    if (!$found) empty_state('No matching subject.', 'Try a shorter name or clear your search.', '/course_page.php?course=' . $course, 'Show all subjects');
} elseif ($program) {
    heading(program_name($program), 'Choose a subject group.'); echo '<div class="grid">';
    foreach (['gen_ed','core','prof','elect'] as $category) {
        [$label,$description] = category_info($category);
        card('/course_page.php?course=' . $program . '_' . $category, $label, $description . ' ' . count(catalogue()[$program . '_' . $category]) . ' subjects.', 'Choose group');
    }
    echo '</div>';
} else {
    heading('Find your subject', 'Start with your program. (Piliin ang iyong kurso.)'); echo '<div class="grid">';
    card('/course_page.php?program=ce', 'Civil Engineering', 'Find Civil Engineering subjects and learning resources.', 'Choose program');
    card('/course_page.php?program=cpe', 'Computer Engineering', 'Find Computer Engineering subjects and learning resources.', 'Choose program'); echo '</div>';
}
page_footer();
