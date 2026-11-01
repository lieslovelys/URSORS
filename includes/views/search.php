<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$query=trim($_GET['query'] ?? '');
page_header('Find people','profile');heading('Find people','Search for a name or username.');
?><form method="get" action="/search.php" class="page-search"><div class="field"><label for="query">Name or username</label><input type="search" name="query" id="query" value="<?= e($query) ?>" required></div><button>Search people</button></form><?php
if ($query) {
    $like='%'.$query.'%';$stmt=$conn->prepare('SELECT id,first_name,last_name,username,role FROM users WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? ORDER BY username LIMIT 50');$stmt->execute([$like,$like,$like]);$users=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!$users) empty_state('No matching people.','Check the spelling or try their username.','/search.php','Try another search');
    echo '<div class="grid">';foreach ($users as $user) card('/profile.php?user_id='.$user['id'],display_name($user),'@'.$user['username'].' · '.ucfirst($user['role']),'View profile');echo '</div>';
}
page_footer();
