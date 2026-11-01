<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
function catalogue(): array
{
    static $data;
    return $data ??= json_decode(file_get_contents(__DIR__ . '/catalogue.json'), true, 512, JSON_THROW_ON_ERROR);
}
function program_name(string $program): string { return $program === 'cpe' ? 'Computer Engineering' : 'Civil Engineering'; }
function category_info(string $category): array
{
    return match ($category) {
        'gen_ed' => ['General Education', 'Language, history, math, and other shared subjects.'],
        'core' => ['Core Subjects', 'Build your engineering foundations.'],
        'prof' => ['Professional Subjects', 'Learn the skills for your engineering program.'],
        'elect' => ['Elective Subjects', 'Explore special topics and interests.'],
        default => ['Subjects', 'Choose a subject to get started.']
    };
}
function canonical_subject(string $subject): string
{
    return [
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
    ][$subject] ?? $subject;
}
function subject_context(string $course, string $subject): array
{
    if (!isset(catalogue()[$course]) || !in_array($subject, catalogue()[$course], true)) fail_request('This subject is not in our list. Please choose a subject again.', 404);
    [$program, $category] = explode('_', $course, 2);
    return ['section' => 'Bachelor of Science in ' . program_name($program), 'sub_section' => match ($category) {
        'core' => 'Core Engineering Subjects', 'gen_ed' => 'General Education Subjects',
        'prof' => 'Professional ' . program_name($program) . ' Subjects',
        'elect' => program_name($program) . ' Electives'
    }, 'nested_sub_section' => canonical_subject($subject)];
}
function breadcrumbs(array $items): void
{
    echo '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="/welcome.php">Home</a></li>';
    foreach ($items as [$name, $url]) echo '<li>' . ($url ? '<a href="' . e($url) . '">' . e($name) . '</a>' : '<span aria-current="page">' . e($name) . '</span>') . '</li>';
    echo '</ol></nav>';
}
