<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (($_POST['action'] ?? '')!=='mark_read') fail_request('Please choose Mark all as read.');
    $stmt=$conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?');$stmt->execute([$_SESSION['user_id']]);
    flash('All notifications are marked as read.');go('/notifications.php');
}
$page=max(1,min(100000,(int)($_GET['page'] ?? 1)));$offset=($page-1)*20;
$stmt=$conn->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 21 OFFSET '.$offset);$stmt->execute([$_SESSION['user_id']]);$items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$hasNext=count($items)>20;$items=array_slice($items,0,20);
page_header('Notifications','notifications');heading('Notifications','Blue highlights are unread. Open a notification to see the related post.');
form_open('/notifications.php'); ?><input type="hidden" name="action" value="mark_read"><button class="secondary">Mark all as read</button></form><div style="margin-top:1.5rem">
<?php if (!$items) empty_state('You are all caught up.','Replies, likes, and account updates will appear here.');
foreach ($items as $item) {
    $message=$item['message'];$postId=null;
    if (preg_match('/\s*\[post:(\d+)\]$/',$message,$match)) { $postId=(int)$match[1];$message=substr($message,0,-strlen($match[0])); }
    $href=$postId ? '/feed.php?post_id='.$postId : ((str_contains(strtolower($message),'approval') && $_SESSION['role']==='admin') ? '/admin_pendings.php' : '/feed.php?mine=1');
    ?><a class="panel notification <?= $item['is_read'] ? '' : 'unread' ?>" href="<?= e($href) ?>"><?php if (!$item['is_read']): ?><span class="badge">Unread</span><?php endif; ?><strong><?= e($message) ?></strong><span class="hint"><?= e(date('M j, Y · g:i a',strtotime($item['created_at']))) ?> · <?= $postId ? 'Open post →' : 'Open related page →' ?></span></a><?php
}
?></div><nav class="pagination" aria-label="Notification pages"><?php if ($page>1): ?><a class="button secondary" href="?page=<?= $page-1 ?>">Newer notifications</a><?php endif; if ($hasNext): ?><a class="button secondary" href="?page=<?= $page+1 ?>">Older notifications</a><?php endif; ?></nav><?php page_footer();
