<?php
require __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) go('/welcome.php');
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>URS SORS · Share knowledge. Build futures.</title>
    <link rel="icon" href="/assets/images/title_icon.png">
    <link rel="preload" href="/assets/images/login_bg.webp" as="image" type="image/webp">
    <link rel="stylesheet" href="/assets/css/landing.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<main id="main">
    <section class="intro" aria-labelledby="intro-title">
        <div class="intro-inner">
            <img class="seal" src="/assets/images/sors_logo.png" alt="URS SORS" width="240" height="100">
            <div>
                <p class="motto">Nurturing Tomorrow’s Noblest</p>
                <h1 id="intro-title">University of Rizal System</h1>
                <p class="description">URS SORS is your learning and resource-sharing community. Explore subjects, share helpful materials, and learn together.</p>
                <p class="signature">Share knowledge. Build futures.</p>
                <div class="actions"><a class="button primary" href="/login.php">Log in</a><a class="button" href="/register.php">Register</a></div>
            </div>
        </div>
    </section>
    <section class="how" aria-labelledby="how-title">
        <div class="container">
            <div class="how-heading"><div><p class="section-label">YOUR LEARNING JOURNEY</p><h2 id="how-title">A little sharing.<br>A lot of learning.</h2></div></div>
            <div class="steps">
                <article><span class="step" aria-label="Step 1">01 / JOIN</span><h3>Find your place</h3><p>Create your student or teacher account, then wait for admin approval before logging in.</p></article>
                <article><span class="step" aria-label="Step 2">02 / DISCOVER</span><h3>Explore your subjects</h3><p>Browse subjects and discover learning materials, community posts, and helpful discussions.</p></article>
                <article><span class="step" aria-label="Step 3">03 / CONTRIBUTE</span><h3>Pass it forward</h3><p>Share resources, ask questions, and join discussions to help your learning community grow.</p></article>
            </div>
        </div>
    </section>
</main>
<footer>
    <div class="container">
        <div class="footer-info"><div><h2>University of Rizal System — SORS</h2><p>A space for sharing knowledge and building futures together.</p></div><p><strong>Need help?</strong> For account approval or access questions, contact your school admin or <a href="/help.php">visit Help</a>.</p></div>
        <p class="copyright">© <?= date('Y') ?> University of Rizal System I Esponilla, Alyssa Mae I Juralbal, John Kenneth</p>
    </div>
</footer>
</body>
</html>
