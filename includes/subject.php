<?php
// Compatibility helper for the old subject landing implementation.
if (!defined('SORS_APP')) { http_response_code(404); exit; }
require_once __DIR__ . '/catalogue.php';
$course = $_GET['course'] ?? '';
$subject = $_GET['subject'] ?? '';
$subjectContext = subject_context($course, $subject);
$section = $subjectContext['section'];
$subSection = $subjectContext['sub_section'];
$subject = $subjectContext['nested_sub_section'];
