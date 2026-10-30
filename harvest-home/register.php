<?php
require_once 'includes/functions.php';

$next = basename($_GET['next'] ?? $_POST['next'] ?? 'account.php');
if (!preg_match('/^[a-z_]+\.php$/', $next)) {
    $next = 'account.php';
}
if (is_logged_in()) {
    redirect('account.php');
}

$form = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$errors = [];

if (is_post()) {
    check_csrf();
    foreach ($form as $key => $v) {
        $form[$key] = post($key);
    }
    $form['email'] = mb_strtolower($form['email']);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Server-side validation (the browser checks too, but never trust it alone)
    if (mb_strlen($form['full_name']) < 2 || mb_strlen($form['full_name']) > 100) $errors['full_name'] = 'Enter your full name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 150) $errors['email'] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9 +()-]{10,20}$/', $form['phone'])) $errors['phone'] = 'Enter a valid phone number (at least 10 digits).';
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) $errors['password'] = 'Use at least 8 characters with letters and numbers.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
    if (post('terms') !== '1') $errors['terms'] = 'Please accept the Terms and Conditions.';

    if (!isset($errors['email'])) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$form['email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'An account with this email already exists. Try logging in.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare("INSERT INTO users (full_name, email, password_hash, phone, address, role, created_at)
                               VALUES (?, ?, ?, ?, ?, 'customer', NOW())");
        $stmt->execute([
            $form['full_name'],
            $form['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $form['phone'],
            $form['address'] !== '' ? $form['address'] : null,
        ]);
        $id = (int)db()->lastInsertId();
        login_user(['user_id' => $id, 'full_name' => $form['full_name'], 'email' => $form['email'], 'role' => 'customer']);
        flash('success', 'Your account has been created. Welcome to Harvest & Home!');
        redirect($next);
    }
}

function err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<span class="err">' . h($errors[$k]) . '</span>' : '';
}

$pageTitle = 'Create Account';
$active = 'login.php';
require 'includes/header.php';
?>
<div class="ctr wide">
    <img class="lg" src="assets/img/logo.png?v=2" alt="">
    <h1>Create Your Account</h1>
    <p class="lead-text">Save your address and follow every order from the farm to your door.</p>
    <form class="card" method="post" action="register.php" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= h($next) ?>">
        <div class="f2">
            <div>
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" value="<?= h($form['full_name']) ?>" maxlength="100" autocomplete="name" required>
                <?= err($errors, 'full_name') ?>
            </div>
            <div>
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" value="<?= h($form['email']) ?>" maxlength="150" autocomplete="email" data-rule="email" required>
                <?= err($errors, 'email') ?>
            </div>
            <div>
                <label for="phone">Phone Number</label>
                <input id="phone" type="tel" name="phone" value="<?= h($form['phone']) ?>" maxlength="20" autocomplete="tel" data-rule="phone" required>
                <?= err($errors, 'phone') ?>
            </div>
            <div>
                <label for="address">Address <span class="mut">(optional)</span></label>
                <input id="address" name="address" value="<?= h($form['address']) ?>" placeholder="Street, suburb, postal code" autocomplete="street-address">
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="new-password" data-rule="password" required>
                <?= err($errors, 'password') ?>
            </div>
            <div>
                <label for="confirm_password">Confirm Password</label>
                <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" data-rule="match" data-match="password" required>
                <?= err($errors, 'confirm_password') ?>
            </div>
        </div>
        <label class="inline"><input type="checkbox" name="terms" value="1" required> I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a></label>
        <?= err($errors, 'terms') ?>
        <button class="btn block lgx mt-4" type="submit"><?= icon('person-plus') ?>Create Account</button>
        <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php?next=<?= h($next) ?>">Log in here</a></p>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
