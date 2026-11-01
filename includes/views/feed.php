<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
require_once __DIR__ . '/../post_components.php';
$archived = $screen === 'archive';
$mine = !empty($_GET['mine']);
$single = isset($_GET['post_id']);
$params = []; $where = [];
if ($single) {
    $where[] = 'p.id=?'; $params[] = (int)$_GET['post_id'];
    if (!in_array($_SESSION['role'],['teacher','admin'],true)) $where[]='p.is_archived=0';
} else {
    $where[] = 'p.is_archived=?'; $params[] = $archived ? 1 : 0;
}
$context = [];
foreach (['section','sub_section','nested_sub_section','deep_sub_section'] as $key) {
    $context[$key] = trim($_GET[$key] ?? '');
    if ($context[$key] && $context[$key] !== 'All' && !$single) { $where[]='p.' . $key . '=?'; $params[]=$context[$key]; }
}
if ($mine) { $where[]='p.user_id=?'; $params[]=$_SESSION['user_id']; }
$page = max(1,min(100000,(int)($_GET['page'] ?? 1))); $offset=($page-1)*10;
$stmt = $conn->prepare('SELECT p.*,u.first_name,u.last_name,u.username,u.profile_picture,u.role FROM posts p JOIN users u ON p.user_id=u.id WHERE ' . implode(' AND ',$where) . ' ORDER BY p.created_at DESC,p.id DESC LIMIT 11 OFFSET ' . $offset);
$stmt->execute($params); $posts=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $hasNext=count($posts)>10; $posts=array_slice($posts,0,10);
if ($single && !$posts) fail_request('This post may be archived or its link may be old. Choose a subject to find other resources.',404);
$title = $archived ? 'Archived Posts' : ($single ? 'Post and replies' : ($mine ? 'My Posts' : ($context['nested_sub_section'] ?: 'Community posts')));
page_header($title, $context['nested_sub_section'] ? 'subjects' : 'home');
$crumbs=[['Subjects','/course_page.php']];
if ($context['nested_sub_section']) {
    foreach (catalogue() as $course=>$names) foreach ($names as $name) {
        $test=subject_context($course,$name);
        if ($test['section']===$context['section'] && $test['nested_sub_section']===$context['nested_sub_section'] && $test['sub_section']===$context['sub_section']) {
            [$program,$category]=explode('_',$course,2);
            $crumbs[]=[program_name($program),'/course_page.php?program='.$program];
            $crumbs[]=[category_info($category)[0],'/course_page.php?course='.$course];
            $crumbs[]=[$name,'/course_page.php?'.http_build_query(['course'=>$course,'subject'=>$name])];
            break 2;
        }
    }
}
$crumbs[]=[$context['deep_sub_section'] ?: $title,null]; breadcrumbs($crumbs);
heading($title,$archived ? 'These posts are hidden from the active feed. Teachers and admins can restore them.' : ($mine ? 'The posts you have shared. Archived posts are in the archive.' : ($single ? 'Read the full discussion and add a reply.' : (($context['deep_sub_section']==='Professor Only') ? 'Professor Only: Teachers and admins share here. Students can read and comment.' : 'General: Read and share helpful learning resources.'))));
if (!$single && !$archived) {
    $user=current_user();
    if (!$user['can_post']) echo '<div class="notice" role="status"><h2>Posting is turned off for your account.</h2><p>Please contact an admin. You can still read posts and join discussions.</p></div>';
    elseif ($context['deep_sub_section']==='Professor Only' && $_SESSION['role']==='student') echo '<div class="notice"><h2>Teachers and admins share posts here.</h2><p>You can read resources and leave comments. To share your own post, choose General.</p><a href="?' . e(http_build_query(array_merge($context,['deep_sub_section'=>'General']))) . '">Open General posts</a></div>';
    else {
        ?><section class="panel" aria-labelledby="create-title"><h2 id="create-title">Create Post</h2><p class="hint">Sharing to <?= e($context['nested_sub_section'] ?: 'Community posts') ?> · <?= e($context['deep_sub_section'] ?: 'General') ?></p><?php form_open('/post_handler.php','',true); ?>
        <?php foreach ($context as $key=>$value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
        <div class="field"><label for="content">What would you like to share?</label><textarea id="content" name="content" required placeholder="Share a resource, ask a question, or explain something helpful."></textarea></div>
        <div class="field"><label for="file">Attach a file (photo, PDF, etc.)</label><input type="file" id="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"><span class="hint">Optional. Up to 10 MB. Photos, PDF, Word, Excel, text, ZIP, or RAR.</span></div><p class="hint">Be kind. Share only materials you have permission to share. Never post passwords or private information.</p><button>Post (I-share)</button></form></section><?php
    }
}
if (!$posts) empty_state($mine ? 'You have not shared a post yet.' : ($archived ? 'No archived posts.' : 'No posts yet.'),$archived ? 'Posts you archive will appear here.' : 'Be the first to share something! Open a subject and write a post.');
foreach ($posts as $post) post_card($post);
if (!$single): ?><nav class="pagination" aria-label="Post pages"><?php if ($page>1): ?><a class="button secondary" href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page-1]))) ?>">Previous posts</a><?php endif; if ($hasNext): ?><a class="button secondary" href="?<?= e(http_build_query(array_merge($_GET,['page'=>$page+1]))) ?>">More posts</a><?php endif; ?></nav><?php endif;
if (!$archived) echo '<p><a href="/archived_posts.php' . ($mine ? '?mine=1' : '') . '">View archived posts</a></p>';
page_footer();
