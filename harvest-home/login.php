<?php
require_once 'includes/functions.php';

// Only allow redirects back to a page on this site
$next = basename($_GET['next'] ?? $_POST['next'] ?? 'account.php');
if (!preg_match('/^[a-z_]+\.php$/', $next)) {
    $next = 'account.php';
}
if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : $next);
}

$email = '';
$error = '';
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'until' => 0];

if (is_post()) {
    check_csrf();
    $email = mb_strtolower(post('email'));
    $password = $_POST['password'] ?? '';

    if ($attempts['until'] > time()) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email address and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($_SESSION['login_attempts']);
            login_user($user);
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            }
            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect($next);
        }
        // Same message for unknown email and wrong password
        $error = 'The email address or password is incorrect.';
        $attempts['count']++;
        if ($attempts['count'] >= 5) {
            $attempts = ['count' => 0, 'until' => time() + 300];
        }
        $_SESSION['login_attempts'] = $attempts;
    }
}

$pageTitle = 'Log In';
$active = 'login.php';
require 'includes/header.php';
?>
<div class="ctr">
    <img class="lg" src="assets/img/logo.png?v=2" alt="">
    <h1>Welcome Back</h1>
    <p class="lead-text">Log in to check out and track your orders.</p>
    <form class="card" method="post" action="login.php" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= h($next) ?>">
        <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
        <label for="email">Email Address</label>
        <input id="email" type="email" name="email" value="<?= h($email) ?>" placeholder="you@example.com" autocomplete="email" data-rule="email" required>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
        <button class="btn block lgx" type="submit"><?= icon('box-arrow-in-right') ?>Log In</button>
        <p class="text-center mut my-2">or</p>
        <a class="btn o block mt-0" href="shop.php"><?= icon('basket2') ?>Continue as Guest</a>
        <p class="text-center mt-3 mb-0">Don't have an account? <a href="register.php?next=<?= h($next) ?>">Register here</a></p>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
