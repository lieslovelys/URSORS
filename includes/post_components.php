<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
function return_field(): string { return '<input type="hidden" name="_return" value="' . e($_SERVER['REQUEST_URI']) . '">'; }
function post_icon(string $name): string
{
    $paths = [
        'like' => '<path d="M7 10v11H3V10zm0 0 5-8c3 0 3 3 2 7h5a2 2 0 0 1 2 2l-2 8a2 2 0 0 1-2 2H7"/>',
        'reply' => '<path d="M21 11a8 8 0 0 1-8 8H7l-5 3 2-6a8 8 0 1 1 17-5Z"/><path d="M8 10h8m-8 4h5"/>',
        'open' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
    ];
    return '<svg class="post-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}
function attachment(?string $path, bool $image = false): void
{
    if (!$path) return;
    $root = dirname(__DIR__);
    $file = realpath($root . '/' . ltrim($path, '/'));
    if (!$file || !str_starts_with($file, $root . '/uploads/') || !is_file($file)) {
        echo '<p class="hint">An older attachment is unavailable. Ask the person who shared it for a new copy.</p>'; return;
    }
    if ($image) echo '<a href="/' . e(ltrim($path,'/')) . '"><img class="post-image" src="/' . e(ltrim($path,'/')) . '" alt="Photo shared with this post" loading="lazy"></a>';
    else echo '<p><a class="button secondary" href="/' . e(ltrim($path,'/')) . '" download>Download attached file</a></p>';
}
function post_card(array $post): void
{
    global $conn;
    $id = (int) $post['id'];
    $stmt = $conn->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(user_id=?),0) AS mine FROM likes WHERE post_id=?');
    $stmt->execute([$_SESSION['user_id'],$id]); $likes = $stmt->get_result()->fetch_assoc();
    $stmt = $conn->prepare('SELECT c.*,u.first_name,u.last_name,u.username,u.profile_picture FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=? AND c.is_deleted=0 ORDER BY c.created_at,c.id');
    $stmt->execute([$id]); $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $moderator = in_array($_SESSION['role'],['teacher','admin'],true);
    ?><article class="post" id="post-<?= $id ?>" aria-label="Post by <?= e(display_name($post)) ?>"><div class="post-head"><img class="avatar" src="<?= e(profile_image($post['profile_picture'],'profile')) ?>" alt=""><div><a href="/profile.php?user_id=<?= (int)$post['user_id'] ?>"><strong><?= e(display_name($post)) ?></strong></a><p class="hint"><?= e(ucfirst($post['role'])) ?> · <time datetime="<?= e(date('c',strtotime($post['created_at']))) ?>"><?= e(date('M j, Y · g:i a',strtotime($post['created_at']))) ?></time></p></div></div>
    <div class="post-context"><span class="subject-chip"><?= nav_icon('subjects') ?><?= e($post['nested_sub_section'] ?: 'Community posts') ?></span><span class="audience-chip"><?= e($post['deep_sub_section'] ?: 'General') ?></span><?php if ($post['is_archived']): ?><span class="archive-chip">Archived</span><?php endif; ?></div>
    <?php
    // Starter posts have a known title line. Keep all user-authored text intact.
    $parts = !empty($post['seed_marker']) ? explode("\n", $post['content'], 2) : null;
    if ($parts && count($parts) === 2): ?><h2 class="post-title"><?= e($parts[0]) ?></h2><p class="post-content"><?= e($parts[1]) ?></p><?php else: ?><p class="post-content"><?= e($post['content']) ?></p><?php endif; ?>
    <?php attachment($post['image_path'],true); attachment($post['file_path']); ?>
    <div class="post-actions"><?php if (!$post['is_archived']): form_open('/like.php'); echo return_field(); ?><input type="hidden" name="post_id" value="<?= $id ?>"><button class="secondary" aria-pressed="<?= $likes['mine'] ? 'true' : 'false' ?>"><?= post_icon('like') ?><span><?= $likes['mine'] ? 'Unlike' : 'Like' ?></span><span class="action-count"><?= (int)$likes['total'] ?></span></button></form><?php endif; ?><a class="button quiet" href="/feed.php?post_id=<?= $id ?>"><span>Open discussion</span><?= post_icon('open') ?></a></div>
    <?php if ($moderator): ?><details class="post-manage"><summary>Manage post</summary><div class="actions"><?php form_open('/post_actions.php', $post['is_archived'] ? 'Restore this post? It will be visible in the feed again.' : 'Archive this post? It will be hidden from the feed. You can restore it later.'); echo return_field(); ?><input type="hidden" name="post_id" value="<?= $id ?>"><input type="hidden" name="action" value="<?= $post['is_archived'] ? 'restore' : 'archive' ?>"><button class="secondary"><?= $post['is_archived'] ? 'Restore post' : 'Archive post' ?></button></form><?php if ($_SESSION['role']==='admin' && !$post['is_archived']): form_open('/delete_post.php','Remove this post from the feed? It will be kept in Archived Posts.'); echo return_field(); ?><input type="hidden" name="post_id" value="<?= $id ?>"><button class="danger">Remove post</button></form><?php endif; ?></div></details><?php endif; ?>
    <details class="comments" <?= isset($_GET['post_id']) ? 'open' : '' ?>><summary><span class="comments-label"><?= post_icon('reply') ?>Comments <span class="action-count"><?= count($comments) ?></span></span><span class="comments-hint">Read or reply</span></summary>
    <?php if (!$comments): ?><p class="hint">No comments yet. Ask a question or say thank you.</p><?php endif;
    foreach ($comments as $comment): ?>
    <div class="comment" id="comment-<?= (int)$comment['id'] ?>">
        <div class="comment-head">
            <a class="comment-avatar-link" href="/profile.php?user_id=<?= (int)$comment['user_id'] ?>" aria-label="View profile of <?= e(display_name($comment)) ?>"><img class="avatar" src="<?= e(profile_image($comment['profile_picture'], 'profile')) ?>" alt="" loading="lazy" width="40" height="40"></a>
            <div class="comment-author">
                <a href="/profile.php?user_id=<?= (int)$comment['user_id'] ?>"><strong><?= e(display_name($comment)) ?></strong></a>
                <time class="hint" datetime="<?= e(date('c', strtotime($comment['created_at']))) ?>"><?= e(date('M j, Y · g:i a', strtotime($comment['created_at']))) ?></time>
            </div>
        </div>
        <p class="comment-content"><?= e($comment['content']) ?></p>
        <?php attachment($comment['file_path']); ?>
        <?php if ($comment['user_id']==$_SESSION['user_id'] || $_SESSION['role']==='admin'): form_open('/delete_comment.php','Remove this comment? It will no longer appear in this discussion.'); echo return_field(); ?><input type="hidden" name="comment_id" value="<?= (int)$comment['id'] ?>"><button class="danger">Remove comment</button></form><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (!$post['is_archived']): form_open('/add_comment.php','',true); echo return_field(); ?><input type="hidden" name="post_id" value="<?= $id ?>"><div class="field"><label for="comment-<?= $id ?>-text">Write a comment</label><textarea id="comment-<?= $id ?>-text" name="comment_content" required placeholder="Ask a question or share a helpful reply."></textarea></div><details><summary>Attach a file to your comment</summary><label for="comment-<?= $id ?>-file">Comment attachment (optional)</label><input id="comment-<?= $id ?>-file" name="comment_file" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"><p class="hint">Up to 10 MB. Photos, PDF, Word, Excel, text, ZIP, or RAR.</p></details><button>Send comment</button></form><?php else: ?><p class="hint">This post is archived. Restore it before adding a comment.</p><?php endif; ?></details></article><?php
}
