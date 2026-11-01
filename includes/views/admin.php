<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id=(int)($_POST['user_id'] ?? 0);
    $stmt=$conn->prepare('SELECT * FROM users WHERE id=?');$stmt->execute([$id]);$target=$stmt->get_result()->fetch_assoc();
    if (!$target) fail_request('We could not find that account. Refresh the accounts list and try again.',404);
    $action=$_POST['action'] ?? (isset($_POST['can_post']) ? ((int)$_POST['can_post']===1 ? 'allow_posting' : 'stop_posting') : '');
    $name=display_name($target);
    switch ($action) {
        case 'approve':
            $stmt=$conn->prepare('UPDATE users SET is_approved=1 WHERE id=?');$stmt->execute([$id]);
            notify_user($conn,$id,'Your account is approved. You can now log in.');flash($name . ' is approved and can now log in.');break;
        case 'reject':
            if ($target['role']==='admin') fail_request('Admin accounts cannot be rejected here.',403);
            $stmt=$conn->prepare('UPDATE users SET is_approved=0 WHERE id=?');$stmt->execute([$id]);
            notify_user($conn,$id,'Your account was not approved. Please contact an admin.');flash($name . ' was not approved. The account is kept for review and cannot log in.');break;
        case 'allow_posting': case 'stop_posting':
            $allowed=$action==='allow_posting' ? 1 : 0;
            $stmt=$conn->prepare('UPDATE users SET can_post=? WHERE id=?');$stmt->execute([$allowed,$id]);
            notify_user($conn,$id,$allowed ? 'Posting is now allowed for your account.' : 'Posting is turned off for your account. Please contact an admin.');
            flash($allowed ? $name . ' can now share posts.' : 'Posting is now turned off for ' . $name . '.');break;
        default: fail_request('Choose an action from the account list.');
    }
    redirect_back();
}
$counts=$conn->query("SELECT COUNT(*) AS total,SUM(is_approved=0) AS pending,SUM(role='student') AS students,SUM(role='teacher') AS teachers FROM users")->fetch_assoc();
$archived=(int)$conn->query('SELECT COUNT(*) FROM posts WHERE is_archived=1')->fetch_row()[0];
page_header($screen==='admin' ? 'Admin' : ($screen==='pending' ? 'Pending Accounts' : 'All Accounts'),'admin');
if ($screen==='admin') {
    heading('Admin, made simple.','Hello, ' . current_user()['first_name'] . '. Choose one task to get started.');
    ?><div class="grid"><?php card('/admin_pendings.php','Pending Accounts (' . (int)$counts['pending'] . ')','Check new accounts and let people in.','Review accounts'); card('/manage_accounts.php','All Accounts (' . (int)$counts['total'] . ')','Find a person and manage their posting.','Find an account'); card('/admin_register.php','Create Account','Create an account that is ready to use.','Create an account'); card('/admin_archive.php','Archived Posts (' . $archived . ')','Bring back a post that was hidden.','View archive'); card('/admin_ranking.php','Ranking','See how many posts people have shared.','View ranking'); ?></div>
    <section class="panel" style="margin-top:1.5rem"><h2>How to use the admin pages</h2><ol class="guide-list"><li><strong>Start with Pending Accounts.</strong> Check each person's name, role, and email. Choose Approve if their details are correct.</li><li><strong>Use All Accounts to find someone.</strong> Allow or stop their posting when needed.</li><li><strong>Archive instead of erasing.</strong> Archived Posts lets you bring content back.</li></ol><p>Approval does not send an email. Let the person know outside the site if needed.</p><a href="/help.php">Read the short help guide</a></section><?php page_footer();return;
}
$pending=$screen==='pending';$query=trim($_GET['q'] ?? '');$page=max(1,min(100000,(int)($_GET['page'] ?? 1)));$offset=($page-1)*20;
heading($pending ? 'Pending Accounts (' . (int)$counts['pending'] . ')' : 'All Accounts','Check the person’s details before making a change.');
?><div class="actions" style="margin-bottom:1rem"><a class="button secondary" href="/admin_home.php">Back to Admin</a><a class="button secondary" href="/admin_register.php">Create account</a></div>
<form method="get" class="page-search" action="<?= $pending ? '/admin_pendings.php' : '/manage_accounts.php' ?>"><div class="field"><label for="q">Find an account</label><input type="search" id="q" name="q" value="<?= e($query) ?>" placeholder="Name, username, or email"></div><button>Search accounts</button></form>
<?php if ($pending): ?><p class="form-note">Choose Approve to let someone log in. Reject keeps the account unapproved; it is not erased and stays available for review.</p><?php endif;
$params=[];$where=$pending ? ['is_approved=0'] : ['1=1'];
if ($query) { $where[]="(first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ? OR CONCAT_WS(' ', first_name, last_name) LIKE ?)";$params=array_fill(0,5,'%'.$query.'%'); }
$stmt=$conn->prepare('SELECT * FROM users WHERE '.implode(' AND ',$where).' ORDER BY created_at DESC,id DESC LIMIT 21 OFFSET '.$offset);$stmt->execute($params);$users=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$hasNext=count($users)>20;$users=array_slice($users,0,20);
if (!$users) empty_state($pending ? 'No accounts need review.' : 'No accounts found.',$query ? 'Try another name, username, or email.' : 'New registrations will appear here.','/admin_home.php','Back to Admin');
echo '<div class="account-list">';
foreach ($users as $user): ?><article class="account-row"><div class="account-info"><h2><?= e(display_name($user)) ?></h2><p><span class="badge"><?= e(ucfirst($user['role'])) ?></span> @<?= e($user['username']) ?></p><p><?= e($user['email']) ?></p><p class="status-line"><?= $user['is_approved'] ? 'Approved' : 'Not approved — cannot log in' ?> · Posting <?= $user['can_post'] ? 'allowed' : 'turned off' ?></p></div><div class="actions"><?php if (!$user['is_approved']): form_open('/admin_pendings.php');echo return_field_admin(); ?><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="approve"><button>Approve <?= e($user['first_name']) ?></button></form><?php if ($user['role']!=='admin'): form_open('/admin_pendings.php','Reject this account? This person will not be able to log in. The account will be kept for review.');echo return_field_admin(); ?><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="reject"><button class="danger">Reject <?= e($user['first_name']) ?></button></form><?php endif; endif;
if (!$pending): form_open('/manage_accounts.php',$user['can_post'] ? 'Stop posting for this account? The person can still read and comment.' : 'Allow this account to share posts?');echo return_field_admin(); ?><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="<?= $user['can_post'] ? 'stop_posting' : 'allow_posting' ?>"><button class="secondary"><?= $user['can_post'] ? 'Stop posting' : 'Allow posting' ?></button></form><?php endif; ?></div></article><?php endforeach; echo '</div>';
?><nav class="pagination" aria-label="Account pages"><?php if ($page>1): ?><a class="button secondary" href="?<?= e(http_build_query(['q'=>$query,'page'=>$page-1])) ?>">Previous accounts</a><?php endif; if ($hasNext): ?><a class="button secondary" href="?<?= e(http_build_query(['q'=>$query,'page'=>$page+1])) ?>">More accounts</a><?php endif; ?></nav><?php page_footer();
function return_field_admin(): string { return '<input type="hidden" name="_return" value="'.e($_SERVER['REQUEST_URI']).'">'; }
