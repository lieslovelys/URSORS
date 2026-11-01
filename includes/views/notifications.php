<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'unread', 'archived'], true)) $filter = 'all';
$filterUrl = '/notifications.php?filter=' . $filter;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?');
        $stmt->execute([$_SESSION['user_id']]);
        flash('All notifications are marked as read.');
    } elseif (in_array($action, ['archive', 'restore'], true)) {
        $id = filter_var($_POST['notification_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) fail_request('Please choose a notification.', 400);
        $stmt = $conn->prepare('UPDATE notifications SET is_archived=? WHERE id=? AND user_id=?');
        $stmt->execute([$action === 'archive' ? 1 : 0, $id, $_SESSION['user_id']]);
        flash($action === 'archive' ? 'Notification archived. You can restore it from Archived.' : 'Notification restored.');
    } else {
        fail_request('Please choose a notification action.', 400);
    }
    go($filterUrl);
}
$page = max(1, min(100000, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * 20;
$where = $filter === 'archived' ? 'is_archived=1' : 'is_archived=0';
if ($filter === 'unread') $where .= ' AND is_read=0';
$stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id=? AND ' . $where . ' ORDER BY created_at DESC,id DESC LIMIT 21 OFFSET ' . $offset);
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$hasNext = count($items) > 20;
$items = array_slice($items, 0, 20);
page_header('Notifications', 'notifications');
heading('Notifications', 'Blue highlights are unread. Archive updates to keep your list tidy without deleting them.');
?><div class="notification-toolbar"><nav class="subnav" aria-label="Notification filters">
<?php foreach (['all'=>'All', 'unread'=>'Unread', 'archived'=>'Archived'] as $key=>$label): ?>
<a href="?filter=<?= e($key) ?>" <?= $filter === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?></nav>
<?php form_open($filterUrl); ?><input type="hidden" name="action" value="mark_read"><button class="secondary">Mark all as read</button></form></div><div style="margin-top:1.5rem">
<?php
if (!$items) {
    $empty = ['all'=>['You are all caught up.', 'Replies, likes, and account updates will appear here.'], 'unread'=>['No unread notifications.', 'You have read all your active notifications.'], 'archived'=>['No archived notifications.', 'Archived updates will appear here. You can restore them at any time.']];
    empty_state($empty[$filter][0], $empty[$filter][1], '/notifications.php?filter=all', 'View all notifications');
}
foreach ($items as $item) {
    $message = $item['message']; $postId = null;
    if (preg_match('/\s*\[post:(\d+)\]$/', $message, $match)) { $postId = (int)$match[1]; $message = substr($message, 0, -strlen($match[0])); }
    $href = $postId ? '/feed.php?post_id='.$postId : ((str_contains(strtolower($message), 'approval') && $_SESSION['role']==='admin') ? '/admin_pendings.php' : '/feed.php?mine=1');
    ?><article class="panel notification <?= $item['is_read'] ? '' : 'unread' ?>">
    <a class="notification notification-link" href="<?= e($href) ?>"><?php if (!$item['is_read']): ?><span class="badge">Unread</span><?php endif; ?><strong><?= e($message) ?></strong><span class="hint"><?= e(date('M j, Y · g:i a', strtotime($item['created_at']))) ?> · <?= $postId ? 'Open post →' : 'Open related page →' ?></span></a>
    <div class="notification-tools"><?php form_open($filterUrl); ?><input type="hidden" name="notification_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="action" value="<?= $filter === 'archived' ? 'restore' : 'archive' ?>"><button class="secondary" aria-label="<?= e(($filter === 'archived' ? 'Restore: ' : 'Archive: ') . $message) ?>"><?= $filter === 'archived' ? 'Restore' : 'Archive' ?></button></form></div>
    </article><?php
}
?></div><nav class="pagination" aria-label="Notification pages"><?php if ($page>1): ?><a class="button secondary" href="?filter=<?= e($filter) ?>&amp;page=<?= $page-1 ?>">Newer notifications</a><?php endif; if ($hasNext): ?><a class="button secondary" href="?filter=<?= e($filter) ?>&amp;page=<?= $page+1 ?>">Older notifications</a><?php endif; ?></nav><?php page_footer();
