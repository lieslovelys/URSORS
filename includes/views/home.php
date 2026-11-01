<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') !== 'dismiss_guide') fail_request('Please use a button on this page.');
    $_SESSION['guide_dismissed'] = true;
    setcookie('sors_guide_' . (int) $_SESSION['user_id'], '1', ['expires' => time()+31536000, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
    flash('Quick Guide hidden. You can always find these steps in Help.'); go('/welcome.php');
}
require_once __DIR__ . '/../post_components.php';
$feedPage = max(1, min(100000, (int)($_GET['page'] ?? 1)));
$feedOffset = ($feedPage - 1) * 10;
$feedLimit = 11;
$stmt = $conn->prepare('SELECT p.*,u.first_name,u.last_name,u.username,u.profile_picture,u.role FROM posts p JOIN users u ON p.user_id=u.id WHERE p.is_archived=0 ORDER BY p.created_at DESC,p.id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $feedLimit, $feedOffset);
$stmt->execute();
$homePosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$hasMorePosts = count($homePosts) > 10;
$homePosts = array_slice($homePosts, 0, 10);
page_header('Home', 'home');
?>
<section class="home-signature" aria-label="URS SORS motto">
    <p>Share knowledge. Build futures.</p>
</section>
<section class="home-people-search" aria-label="Search account">
    <form method="get" action="/search.php" role="search" aria-label="Find accounts">
        <div class="field"><input type="search" id="home-account-query" name="query" aria-label="Search account by name or username" placeholder="Search account" maxlength="100" required></div>
        <button type="submit" aria-label="Search account" title="Search account"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg></button>
    </form>
</section>
<section id="community-feed" class="home-community" aria-labelledby="community-feed-title" data-home-feed>
    <div class="home-feed-heading"><div><h1 id="community-feed-title">Community posts</h1><p class="muted">See what others are sharing · Newest first</p></div><a class="button secondary" href="/course_page.php">Browse Subjects</a></div>
    <div data-feed-posts>
        <?php foreach ($homePosts as $post) post_card($post); ?>
    </div>
    <?php if (!$homePosts): ?><div class="panel"><p><?= $feedPage > 1 ? 'No more posts on this page.' : 'No posts yet. Browse a subject and share something helpful!' ?></p></div><?php endif; ?>
    <nav class="pagination home-feed-pagination" aria-label="Community post pages">
        <?php if ($feedPage > 1): ?><a class="button secondary" href="/welcome.php?page=<?= $feedPage-1 ?>#community-feed">Previous posts</a><?php endif; ?>
        <?php if ($hasMorePosts): ?><a class="button secondary" data-feed-next href="/welcome.php?page=<?= $feedPage+1 ?>#community-feed">More posts</a><?php endif; ?>
    </nav>
    <p class="hint feed-status" role="status" data-feed-status><?= $hasMorePosts ? 'Scroll to see more posts.' : ($homePosts ? 'You have reached the end of the posts.' : '') ?></p>
</section>
<?php page_footer();
