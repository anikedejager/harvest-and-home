<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) redirect('index.php');

$email = '';
$error = '';
$attempts = $_SESSION['admin_attempts'] ?? ['count' => 0, 'until' => 0];

if (is_post()) {
    check_csrf();
    $email = mb_strtolower(post('email'));
    $password = $_POST['password'] ?? '';

    if ($attempts['until'] > time()) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($_SESSION['admin_attempts']);
            login_user($user);
            redirect('index.php');
        }
        $error = 'Invalid administrator email or password.';
        $attempts['count']++;
        if ($attempts['count'] >= 5) $attempts = ['count' => 0, 'until' => time() + 300];
        $_SESSION['admin_attempts'] = $attempts;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Admin Login | Harvest &amp; Home</title>
    <meta name="theme-color" content="#183420">
    <link rel="icon" href="../assets/img/logo.png?v=2">
    <?= head_assets('../') ?>
</head>
<body class="auth">
<main class="ctr">
    <img class="lg" src="../assets/img/logo.png?v=2" alt="">
    <h1>Admin Login</h1>
    <p class="lead-text">Manage products, orders and promotions.</p>
    <?= show_flash() ?>
    <form class="card" method="post" action="login.php" data-validate>
        <?= csrf_field() ?>
        <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= h($email) ?>" autocomplete="username" data-rule="email" required>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>
        <button class="btn block lgx" type="submit"><?= icon('shield-lock') ?>Log In</button>
        <p class="text-center mt-3 mb-0"><a href="../index.php"><?= icon('arrow-left') ?> Back to the shop</a></p>
    </form>
</main>
<script src="../assets/js/main.js"></script>
</body>
</html>
