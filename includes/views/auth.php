<?php
if (!defined('SORS_APP')) { http_response_code(404); exit; }
$isLogin = $screen === 'login';
$isAdmin = $screen === 'create_account';
$error = '';
$errors = [];
$sent = !$isLogin && !$isAdmin && isset($_GET['sent']) && !empty($_SESSION['registration_sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isLogin) {
        $username = trim($_POST['username'] ?? '');
        $stmt = $conn->prepare('SELECT * FROM users WHERE username=?');
        $stmt->bind_param('s', $username); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc();
        if (!$user || !password_verify($_POST['password'] ?? '', $user['password'])) {
            $error = 'Wrong username or password. Try again.'; http_response_code(401);
        } elseif (!$user['is_approved']) {
            $error = 'Your account is waiting for approval. An admin needs to approve it before you can log in. Please contact your school admin. You do not need to register again.'; http_response_code(403);
        } else {
            session_regenerate_id(true);
            $_SESSION = ['user_id' => $user['id'], 'username' => $user['username'], 'role' => $user['role'], 'csrf_token' => bin2hex(random_bytes(32))];
            flash('You are logged in. Welcome back!');
            go($user['role'] === 'admin' ? '/admin_home.php' : '/welcome.php');
        }
    } else {
        // The shared registration service validates every field server-side.
        require_once __DIR__ . '/../register_account.php';
        $result = register_account($_POST, $isAdmin);
        $errors = $result['errors'];
        if ($errors) { $error = 'Please check the form below. ' . implode(' ', $errors); http_response_code($result['status']); }
        elseif ($isAdmin) { flash('Account created. This person can now log in.'); go('/manage_accounts.php'); }
        else { $_SESSION['registration_sent'] = true; go('/register.php?sent=1'); }
    }
}
page_header($isLogin ? 'Log in' : ($isAdmin ? 'Create account' : 'Register'), $isAdmin ? 'admin' : ($isLogin ? 'login' : 'register'), true);
if ($sent) {
    heading('Your account was sent for approval.', 'An admin will approve it soon. You can log in after that.');
    echo '<div class="panel"><h2>What happens next?</h2><ol class="guide-list"><li>Your school admin checks your details.</li><li>Ask your admin if you have not heard back. There is no automatic approval email.</li><li>Once approved, log in with the username and password you chose.</li></ol><a class="button" href="/login.php">Go to login</a></div>';
    page_footer(); return;
}
heading($isLogin ? 'Welcome to URS SORS' : ($isAdmin ? 'Create an account' : 'Create your account'), $isLogin ? 'Find your subjects. Share resources. Learn together.' : ($isAdmin ? 'This account will be approved and ready to use.' : 'For students and teachers. An admin will check your account first.'));
if ($error) echo '<div class="flash error" role="alert">' . e($error) . '</div>';
?><section class="panel"><form method="post" action="<?= $isLogin ? '/login.php' : ($isAdmin ? '/admin_register.php' : '/register.php') ?>" data-validate><?= csrf_field() ?>
<?php if (!$isLogin):
field('first_name', 'First name', 'text', 'Use the name your school knows you by.', true, $_POST['first_name'] ?? '');
field('last_name', 'Last name', 'text', '', true, $_POST['last_name'] ?? '');
field('email', 'Email address', 'email', 'Use an email address you can access.', true, $_POST['email'] ?? '');
?><div class="field"><label for="role">I am a</label><select id="role" name="role" required><option value="">Choose your role</option><?php foreach ($isAdmin ? ['student','teacher','admin'] : ['student','teacher'] as $role): ?><option value="<?= $role ?>" <?= ($_POST['role'] ?? '') === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option><?php endforeach; ?></select><span class="hint">Choose your real role at school.</span></div><?php endif;
field('username', 'Username', 'text', $isLogin ? 'Enter the username you registered.' : 'Use your student/staff ID or a name you can remember.', true, $_POST['username'] ?? '');
field('password', 'Password', 'password', $isLogin ? 'Capital and small letters matter.' : 'Use 8–72 characters. Do not reuse a password from another site.');
?><button type="submit"><?= $isLogin ? 'Log in' : ($isAdmin ? 'Create account' : 'Send for approval') ?></button></form></section>
<?php if ($isLogin): ?><p>New here? <a href="/register.php">Create an account</a></p><p><a href="/help.php">Can't log in? Get help.</a></p><?php elseif (!$isAdmin): ?><p>Already registered? <a href="/login.php">Log in</a></p><?php endif; page_footer();
