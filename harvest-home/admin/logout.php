<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    check_csrf();
    logout_user();
    flash('success', 'You have been logged out.');
    redirect('login.php');
}
$pageTitle = 'Log Out';
require 'includes/header.php';
?>
<div class="ctr">
    <div class="success-icon"><?= icon('box-arrow-right') ?></div>
    <h1>Log out?</h1>
    <p class="lead-text">Are you sure you want to log out of the admin site?</p>
    <form class="card" method="post" action="logout.php">
        <?= csrf_field() ?>
        <button class="btn block lgx mt-0" type="submit"><?= icon('box-arrow-right') ?>Yes, Log Out</button>
        <a class="btn o block" href="index.php">Cancel</a>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
