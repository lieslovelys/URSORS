<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}
$course = $_GET['course'] ?? '';
$subject = $_GET['subject'] ?? '';
$catalogue = dirname(__DIR__) . '/' . $course . '.html';
if (!preg_match('/^(cpe|ce)_(core|elect|prof|gen_ed)$/', $course) || !is_file($catalogue)) fail_request('Unknown course.', 404);
preg_match_all('/subject=([^"&]+)/', file_get_contents($catalogue), $matches);
if (!in_array($subject, array_map('urldecode', $matches[1]), true)) fail_request('Unknown subject.', 404);
$isCpe = str_starts_with($course, 'cpe_');
$section = 'Bachelor of Science in ' . ($isCpe ? 'Computer' : 'Civil') . ' Engineering';
$category = substr($course, strpos($course, '_') + 1);
$subSection = match ($category) {
    'core' => 'Core Engineering Subjects', 'gen_ed' => 'General Education Subjects',
    'prof' => 'Professional ' . ($isCpe ? 'Computer' : 'Civil') . ' Engineering Subjects',
    'elect' => ($isCpe ? 'Computer' : 'Civil') . ' Engineering Electives'
};
$subjectAliases = [
    'Engineering Mathematics 1' => 'Engineering Mathematics 1 (Algebra and Trigonometry)',
    'Engineering Mathematics 2' => 'Engineering Mathematics 2 (Analytic Geometry and Calculus)',
    'Engineering Mechanics' => 'Engineering Mechanics (Statics and Dynamics)',
    'Artificial Intelligence' => 'Artificial Intelligence and Machine Learning',
    'Internet of Things' => 'Internet of Things (IoT)',
    'Building Information Modeling' => 'Building Information Modeling (BIM)',
    'Construction Safety and Health' => 'Construction Safety and Health Management',
    'Microprocessor and Microcontroller System' => 'Microprocessor and Microcontroller Systems',
    'Internship' => 'Internship / On-the-Job Training (OJT)',
    'Geology and Geotech' => 'Geology and Geotechnical Engineering',
    'Hydraulics' => 'Hydraulics and Hydrology', 'Soil Mechanics' => 'Soil Mechanics and Foundations',
    'Structural Theory' => 'Structural Theory and Analysis',
    'Komunikasyon at Pananaliksik' => 'Komunikasyon at Pananaliksik sa Wika at Kulturang Filipino',
    'Pagbasa at Pagsusuri' => "Pagbasa at Pagsusuri ng Iba't-Ibang Teksto Tungo sa Pananaliksik"
];
$subject = $subjectAliases[$subject] ?? $subject;
$subjectContext = ['section' => $section, 'sub_section' => $subSection, 'nested_sub_section' => $subject];
