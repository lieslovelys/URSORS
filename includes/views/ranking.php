<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$page=max(1,min(100000,(int)($_GET['page'] ?? 1)));$offset=($page-1)*30;
$result=$conn->query('SELECT u.id,u.first_name,u.last_name,u.username,COUNT(p.id) AS post_count FROM users u LEFT JOIN posts p ON p.user_id=u.id AND p.is_archived=0 GROUP BY u.id,u.first_name,u.last_name,u.username ORDER BY post_count DESC,u.username ASC LIMIT 31 OFFSET '.$offset);
$users=$result->fetch_all(MYSQLI_ASSOC);$hasNext=count($users)>30;$users=array_slice($users,0,30);
page_header('Ranking','ranking');heading('Community Ranking','Ranking is based on number of posts shared. It is not grades.');
echo '<p class="hint">Only posts that are not archived are counted. Keep sharing helpful resources!</p><ol class="rank-list" start="'.($offset+1).'">';
foreach ($users as $index=>$user): ?><li class="rank-row"><span class="rank-number">#<?= $offset+$index+1 ?></span><div><a href="/profile.php?user_id=<?= (int)$user['id'] ?>"><?= e(display_name($user)) ?></a><div class="hint">@<?= e($user['username']) ?></div></div><span class="count"><?= (int)$user['post_count'] ?> posts</span></li><?php endforeach;
echo '</ol>';
?><nav class="pagination" aria-label="Ranking pages"><?php if ($page>1): ?><a class="button secondary" href="?page=<?= $page-1 ?>">Previous people</a><?php endif; if ($hasNext): ?><a class="button secondary" href="?page=<?= $page+1 ?>">More people</a><?php endif; ?></nav><?php page_footer();
