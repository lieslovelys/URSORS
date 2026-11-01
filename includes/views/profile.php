<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$id=(int)($_GET['user_id'] ?? $_SESSION['user_id']);
$stmt=$conn->prepare('SELECT * FROM users WHERE id=?');$stmt->execute([$id]);$user=$stmt->get_result()->fetch_assoc();
if (!$user) fail_request('We could not find this person. Try searching for their username.',404);
$own=$id==$_SESSION['user_id'];
require_once __DIR__ . '/../post_components.php';
$postsPage = max(1, min(100000, (int)($_GET['posts_page'] ?? 1)));
$postsOffset = ($postsPage - 1) * 10;
$postsLimit = 11;
$stmt = $conn->prepare('SELECT p.*,u.first_name,u.last_name,u.username,u.profile_picture,u.role FROM posts p JOIN users u ON u.id=p.user_id WHERE p.user_id=? AND p.is_archived=0 ORDER BY p.created_at DESC,p.id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('iii', $id, $postsLimit, $postsOffset);
$stmt->execute();
$profilePosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$hasMorePosts = count($profilePosts) > 10;
$profilePosts = array_slice($profilePosts, 0, 10);
page_header($own ? 'My Profile' : display_name($user),'profile'); heading($own ? 'My Profile' : 'Profile','A little about you and your learning space.');
$camera = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 5l2-3h4l2 3h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="4"/></svg>';
?>
<section class="panel profile-card">
    <div class="profile-cover-wrap">
        <img class="cover" src="<?= e(profile_image($user['cover_photo'],'cover')) ?>" alt="Cover photo for <?= e(display_name($user)) ?>">
        <?php if ($own): ?><button type="button" class="photo-edit cover-edit" data-photo-open="edit-cover" aria-label="Change cover photo" aria-haspopup="dialog" aria-controls="edit-cover" title="Change cover photo"><?= $camera ?></button><?php endif; ?>
    </div>
    <div class="profile-avatar-wrap">
        <img class="profile-avatar" src="<?= e(profile_image($user['profile_picture'],'profile')) ?>" alt="Profile photo for <?= e(display_name($user)) ?>">
        <?php if ($own): ?><button type="button" class="photo-edit avatar-edit" data-photo-open="edit-profile" aria-label="Change profile photo" aria-haspopup="dialog" aria-controls="edit-profile" title="Change profile photo"><?= $camera ?></button><?php endif; ?>
    </div>
    <h2><?= e(display_name($user)) ?></h2>
    <p>@<?= e($user['username']) ?> <span class="badge"><?= e(ucfirst($user['role'])) ?></span></p>
</section>
<?php if ($own): ?>
    <?php if (!current_user()['can_post']): ?>
        <div class="notice" role="status"><h2>Posting is turned off for your account.</h2><p>Please contact an admin. You can still read posts and join discussions.</p></div>
    <?php else: ?>
        <section class="panel profile-composer" aria-labelledby="create-title">
            <h2 id="create-title">Create Post</h2>
            <p class="hint">Sharing to Community posts · General</p>
            <?php form_open('/post_handler.php','',true); ?>
                <input type="hidden" name="source" value="profile">
                <input type="hidden" name="section" value="General">
                <input type="hidden" name="deep_sub_section" value="General">
                <div class="profile-composer-fields">
                <div class="field"><label for="content">What would you like to share?</label><textarea id="content" name="content" required placeholder="Share a resource, ask a question, or explain something helpful."></textarea></div>
                <div class="field"><label for="file">Attach a file (photo, PDF, etc.)</label><input type="file" id="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"><span class="hint">Optional. Up to 10 MB. Photos, PDF, Word, Excel, text, ZIP, or RAR.</span></div>
                <button class="profile-composer-submit">Post</button>
                </div>
                <p class="hint">Be kind. Share only materials you have permission to share. Never post passwords or private information.</p>
            </form>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?php if ($own) profile_navigation(); ?>
<section id="profile-posts" class="profile-posts" aria-labelledby="profile-posts-title">
    <div class="page-head"><h2 id="profile-posts-title"><?= $own ? 'My posts' : 'Posts' ?></h2><p class="muted">Posts by <?= e(display_name($user)) ?> · Newest first</p></div>
    <?php if (!$profilePosts): ?><div class="panel"><p><?= $postsPage > 1 ? 'No more posts on this page.' : ($own ? 'You have not shared any posts yet.' : 'No posts to show yet.') ?></p></div><?php endif; ?>
    <?php foreach ($profilePosts as $post) post_card($post); ?>
    <?php if ($postsPage > 1 || $hasMorePosts): ?><nav class="pagination" aria-label="Profile post pages">
        <?php if ($postsPage > 1): ?><a class="button secondary" href="/profile.php?<?= e(http_build_query(['user_id'=>$id, 'posts_page'=>$postsPage-1])) ?>#profile-posts">Previous posts</a><?php endif; ?>
        <?php if ($hasMorePosts): ?><a class="button secondary" href="/profile.php?<?= e(http_build_query(['user_id'=>$id, 'posts_page'=>$postsPage+1])) ?>#profile-posts">More posts</a><?php endif; ?>
    </nav><?php endif; ?>
</section>
<?php if ($own):
    foreach (['profile_picture'=>['profile','Change profile photo','upload_profile.php'], 'cover_photo'=>['cover','Change cover photo','upload_cover.php']] as $field=>[$kind,$title,$action]): ?>
    <dialog class="photo-editor" id="edit-<?= e($kind) ?>" aria-labelledby="edit-<?= e($kind) ?>-title" aria-describedby="hint-<?= e($field) ?>">
        <h2 id="edit-<?= e($kind) ?>-title"><?= e($title) ?></h2>
        <?php form_open('/'.$action,'',true); ?>
        <div class="field">
            <label for="<?= e($field) ?>">Choose a new <?= e($kind) ?> photo</label>
            <input type="file" id="<?= e($field) ?>" name="<?= e($field) ?>" accept=".jpg,.jpeg,.png,.gif,.webp" data-preview="preview-<?= e($kind) ?>" aria-describedby="hint-<?= e($field) ?>" required>
            <span class="hint" id="hint-<?= e($field) ?>">JPG, PNG, GIF, or WebP. Up to 10 MB. Check the preview, then save.</span>
            <img class="image-preview" id="preview-<?= e($kind) ?>" alt="Preview of your chosen <?= e($kind) ?> photo" hidden>
        </div>
        <div class="actions"><button type="button" class="secondary" data-photo-cancel>Cancel</button><button>Save <?= e($kind) ?> photo</button></div>
        </form>
    </dialog>
    <?php endforeach;
endif;
page_footer();
