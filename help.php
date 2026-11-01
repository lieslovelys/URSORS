<?php
require __DIR__ . '/includes/bootstrap.php';
page_header('Help', 'help');
heading('How can we help?', 'Short answers to get you started.');
?><section class="panel help-section"><h2>For students and teachers</h2>
<details open><summary>How do I register?</summary><p>Choose Register. Fill in your name, email, username, password, and role. An admin must approve your account before you can log in. You do not need to register again while waiting.</p><a href="/register.php">Create an account</a></details>
<details><summary>How do I find a subject?</summary><p>Open Subjects. Choose your program, a subject group, and your subject. Choose General for everyone's posts, or Professor Only for posts shared by teachers and admins.</p><a href="/course_page.php">Browse subjects</a></details>
<details><summary>How do I post?</summary><p>Open a subject feed. Write in the Create Post box. You can attach a photo or file up to 10 MB. Choose Post (I-share). Do not share passwords, private information, or files you do not have permission to share.</p></details>
<details><summary>Why can't I log in?</summary><p>Check your username and password. Passwords use capital and small letters. If your account is waiting for approval, contact your school admin. There is no automatic password reset yet; ask an admin for help.</p></details>
<details><summary>Why can't I post?</summary><p>An admin may have turned off posting for your account. Contact an admin to ask about it. Students can read Professor Only posts, but only teachers and admins can create posts there.</p></details>
<details><summary>Where are my posts and notifications?</summary><p>Choose See My Posts on Home. Choose Notifications to see updates. A blue highlight means you have not marked that update as read. Ranking counts shared posts; it is not grades.</p></details></section>
<section class="panel help-section"><h2>For administrators</h2><ol class="guide-list"><li>Open Admin, then Pending Accounts. Check the person's name, email, and role before approving.</li><li>Use All Accounts to search for someone and allow or stop their posting.</li><li>Use Create Account when you need to create an approved account yourself.</li><li>Use Archived Posts to bring a post back. Archiving hides a post; it does not erase it.</li></ol><p>Reject keeps an account unapproved. It does not erase the account. There is no automatic email delivery; tell the person your decision outside the site if needed.</p></section>
<?php page_footer();
