<?php
require_once 'includes/functions.php';
require_login();
if (is_admin()) redirect('admin/index.php');

$uid = current_user()['user_id'];
$stmt = db()->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
$errors = [];

if (is_post()) {
    check_csrf();
    $name = post('full_name');
    $email = mb_strtolower(post('email'));
    $phone = post('phone');
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';

    if (mb_strlen($name) < 2) $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9 +()-]{10,20}$/', $phone)) $errors[] = 'Enter a valid phone number.';

    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?');
    $stmt->execute([$email, $uid]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'That email address is already used by another account.';

    // Optional password change
    $changePassword = $new !== '';
    if ($changePassword) {
        if (!password_verify($current, $user['password_hash'])) $errors[] = 'Your current password is incorrect.';
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) $errors[] = 'New password: use at least 8 characters with letters and numbers.';
    }

    if (!$errors) {
        $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?');
        $stmt->execute([$name, $email, $phone, $uid]);
        if ($changePassword) {
            $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        }
        $_SESSION['user']['full_name'] = $name;
        $_SESSION['user']['email'] = $email;
        flash('success', 'Your details have been saved.');
        redirect('details.php');
    }
    $user = array_merge($user, ['full_name' => $name, 'email' => $email, 'phone' => $phone]);
}

$pageTitle = 'Personal Details';
$active = 'account.php';
require 'includes/header.php';
?>
<h1>My Account</h1>
<div class="two side-first">
    <?= account_menu('details.php') ?>
    <form class="card" method="post" action="details.php" data-validate>
        <?= csrf_field() ?>
        <h2 class="card-title"><?= icon('person') ?>Personal Details</h2>
        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
        <div class="f2">
            <div><label for="full_name">Full Name</label><input id="full_name" name="full_name" value="<?= h($user['full_name']) ?>" required></div>
            <div><label for="email">Email</label><input id="email" type="email" name="email" value="<?= h($user['email']) ?>" data-rule="email" required></div>
            <div><label for="phone">Phone</label><input id="phone" type="tel" name="phone" value="<?= h($user['phone']) ?>" data-rule="phone" required></div>
        </div>
        <h2 class="card-title mt-3 mb-1"><?= icon('key') ?>Change Password</h2>
        <p class="hint mb-3">Leave blank to keep your current password.</p>
        <div class="f2">
            <div><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
            <div><label for="new_password">New password</label><input id="new_password" type="password" name="new_password" autocomplete="new-password" data-rule="password"></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn" type="submit"><?= icon('check2') ?>Save Changes</button>
            <a class="btn o" href="account.php">Cancel</a>
        </div>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
