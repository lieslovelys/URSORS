<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') !== 'dismiss_guide') fail_request('Please use a button on this page.');
    $_SESSION['guide_dismissed'] = true;
    setcookie('sors_guide_' . (int) $_SESSION['user_id'], '1', ['expires' => time()+31536000, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
    flash('Quick Guide hidden. You can always find these steps in Help.'); go('/welcome.php');
}
$user = current_user();
page_header('Home', 'home');
?><section class="hero"><p class="eyebrow">Your learning space</p><h1>Hello, <?= e($user['first_name']) ?>!</h1><p><?= e(display_name($user)) ?> <span class="badge"><?= e(ucfirst($user['role'])) ?></span></p><p>What do you want to do?</p><a class="button" href="/course_page.php">Browse Subjects</a></section>
<div class="grid three"><?php card('/feed.php?mine=1','See My Posts','Find the things you have shared.','See my posts'); card('/notifications.php','Notifications','See replies and updates for you.','See updates'); card('/ranking_board.php','Ranking','See how many posts people have shared.','View ranking'); ?></div>
<?php if (empty($_SESSION['guide_dismissed']) && empty($_COOKIE['sors_guide_' . (int) $user['id']])): ?><section class="panel" style="margin-top:1.5rem"><h2>New here? A quick guide</h2><ol class="guide-list"><li>Choose Browse Subjects.</li><li>Pick your program, subject group, and subject.</li><li>Open General to read or share resources.</li><li>Use Notifications to keep up with replies.</li></ol><?php form_open('/welcome.php'); ?><input type="hidden" name="action" value="dismiss_guide"><button class="secondary">Got it. Hide this guide.</button></form></section><?php endif; page_footer();
