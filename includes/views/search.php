<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$query=trim($_GET['query'] ?? '');
page_header('Find people','home');heading('Find people','Search for a name or username. Open a result to view their profile.');
?><form method="get" action="/search.php" class="page-search"><div class="field"><label for="query">Name or username</label><input type="search" name="query" id="query" value="<?= e($query) ?>" required></div><button>Search people</button></form><?php
if ($query) {
    $like='%'.$query.'%';$stmt=$conn->prepare("SELECT id,first_name,last_name,username,role,profile_picture FROM users WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT_WS(' ', first_name, last_name) LIKE ? ORDER BY username LIMIT 50");$stmt->execute([$like,$like,$like,$like]);$users=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!$users) empty_state('No matching people.','Check the spelling or try their username.','/search.php','Try another search');
    ?><div class="people-results"><?php foreach ($users as $user): ?>
        <a class="person-result" href="/profile.php?user_id=<?= (int)$user['id'] ?>">
            <img class="avatar" src="<?= e(profile_image($user['profile_picture'], 'profile')) ?>" alt="" width="52" height="52" loading="lazy">
            <div class="person-result-info"><strong><?= e(display_name($user)) ?></strong><span class="muted">@<?= e($user['username']) ?></span><span class="badge"><?= e(ucfirst($user['role'])) ?></span></div>
            <span class="person-result-action">View profile <span aria-hidden="true">→</span></span>
        </a>
    <?php endforeach; ?></div><?php
}
page_footer();
