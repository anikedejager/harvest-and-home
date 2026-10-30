<?php
/*
 * Add or edit a user, including their role (RBAC).
 * A password is set only when a user is added. Admins cannot change another
 * user's password; customers change their own under Personal Details, and an
 * admin changes their own here (current password required).
 * Safety rules: an admin cannot remove their own admin role, the last admin
 * account cannot be changed to a customer, and only the main admin (the
 * original administrator) may change other administrator accounts.
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);
$user = null;
if ($id) {
    $stmt = db()->prepare('SELECT user_id, full_name, email, phone, address, role FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash('error', 'User not found.');
        redirect('customers.php');
    }
}
$form = $user ?: ['full_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'role' => 'customer'];
$isSelf = $user && (int)$user['user_id'] === (int)current_user()['user_id'];
$isMain = $user && (int)$user['user_id'] === main_admin_id();

// Admin accounts are protected: only the main admin may change another administrator
if ($user && $user['role'] === 'admin' && !$isSelf && !is_main_admin()) {
    flash('error', 'Only the main administrator can change other administrator accounts.');
    redirect('customers.php');
}
$errors = [];

if (is_post()) {
    check_csrf();
    $form['full_name'] = post('full_name');
    $form['email'] = mb_strtolower(post('email'));
    $form['phone'] = post('phone');
    $form['address'] = post('address');
    $form['role'] = post('role') === 'admin' ? 'admin' : 'customer';
    $password = $_POST['password'] ?? '';
    $current = $_POST['current_password'] ?? '';
    $newPassword = $isSelf ? ($_POST['new_password'] ?? '') : '';

    if (mb_strlen($form['full_name']) < 2 || mb_strlen($form['full_name']) > 100) $errors[] = 'Enter a full name (2 to 100 characters).';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 150) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9 +()-]{10,20}$/', $form['phone'])) $errors[] = 'Enter a valid phone number (at least 10 digits).';
    if (mb_strlen($form['address']) > 255) $errors[] = 'The address is too long.';

    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?');
    $stmt->execute([$form['email'], $id]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'Another account already uses this email address.';

    // Only a new user gets a password from the admin
    if (!$user && (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password))) {
        $errors[] = 'Password: use at least 8 characters with letters and numbers.';
    }

    // Admin changing their own password: must confirm the current one
    if ($newPassword !== '') {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $stmt->execute([$id]);
        if (!password_verify($current, (string)$stmt->fetchColumn())) {
            $errors[] = 'Your current password is incorrect.';
        }
        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'New password: use at least 8 characters with letters and numbers.';
        }
    }

    // Role safety rules
    if ($user && $user['role'] === 'admin' && $form['role'] === 'customer') {
        $admins = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($isSelf) {
            $errors[] = 'You cannot remove your own admin role. Ask another administrator to do it.';
        } elseif ($admins <= 1) {
            $errors[] = 'This is the only administrator account, so it must stay an admin.';
        }
    }

    if (!$errors) {
        $address = $form['address'] !== '' ? $form['address'] : null;
        if ($user) {
            db()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, role = ? WHERE user_id = ?')
                ->execute([$form['full_name'], $form['email'], $form['phone'], $address, $form['role'], $id]);
            if ($newPassword !== '') {
                db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                    ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
            }
            if ($isSelf) {
                $_SESSION['user']['full_name'] = $form['full_name'];
                $_SESSION['user']['email'] = $form['email'];
            }
            flash('success', $form['full_name'] . ' was updated.');
        } else {
            db()->prepare("INSERT INTO users (full_name, email, password_hash, phone, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())")
                ->execute([$form['full_name'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['phone'], $address, $form['role']]);
            flash('success', $form['full_name'] . ' was added as ' . ($form['role'] === 'admin' ? 'an administrator.' : 'a customer.'));
        }
        redirect('customers.php');
    }
}

$pageTitle = $isSelf ? 'My Profile' : ($user ? 'Edit User' : 'Add User');
$adminActive = $isSelf ? 'profile' : 'customers.php';
require 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="customers.php">Users</a><?= icon('chevron-right') ?>
    <span><?= $pageTitle ?></span>
</nav>
<h1 class="mb-3"><?= $pageTitle ?></h1>
<form class="card" method="post" action="user_form.php" data-validate style="max-width:760px">
    <?= csrf_field() ?>
    <?php if ($user): ?><input type="hidden" name="user_id" value="<?= $id ?>"><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <h2 class="card-title"><?= icon('person') ?>Account details</h2>
    <div class="f2">
        <div>
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" value="<?= h($form['full_name']) ?>" maxlength="100" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?= h($form['email']) ?>" maxlength="150" data-rule="email" required>
        </div>
        <div>
            <label for="phone">Phone</label>
            <input id="phone" type="tel" name="phone" value="<?= h($form['phone']) ?>" maxlength="20" data-rule="phone" required>
        </div>
        <div>
            <label for="address">Address <span class="mut">(optional)</span></label>
            <input id="address" name="address" value="<?= h($form['address'] ?? '') ?>" maxlength="255">
        </div>
    </div>

    <h2 class="card-title mt-2"><?= icon('shield-lock') ?>Role and access</h2>
    <div class="opts">
        <label class="opt"><input type="radio" name="role" value="customer" <?= $form['role'] === 'customer' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>><?= icon('person') ?><span class="opt-text">Customer<small>Can shop, check out and see their own orders</small></span></label>
        <label class="opt"><input type="radio" name="role" value="admin" <?= $form['role'] === 'admin' ? 'checked' : '' ?>><?= icon('shield-lock') ?><span class="opt-text">Administrator<small>Can open the admin website and manage the store</small></span></label>
    </div>
    <?php if ($isSelf): ?>
        <input type="hidden" name="role" value="admin">
        <?php if ($isMain): ?><p class="hint mb-2"><?= icon('star-fill') ?> You are the main administrator. Your account is protected and only you can change it.</p><?php endif; ?>
        <p class="hint mb-3"><?= icon('info-circle') ?> This is your own account, so your admin role cannot be removed here.</p>
    <?php endif; ?>

    <?php if (!$user): ?>
        <h2 class="card-title mt-2"><?= icon('key') ?>Starting password</h2>
        <p class="hint mb-2">Give this to the new user. They can change it under My Account &gt; Personal Details.</p>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="new-password" data-rule="password" required style="max-width:360px">
    <?php elseif ($isSelf): ?>
        <h2 class="card-title mt-2"><?= icon('key') ?>Change your password</h2>
        <p class="hint mb-2">Leave both fields blank to keep your current password.</p>
        <div class="f2">
            <div><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
            <div><label for="new_password">New password</label><input id="new_password" type="password" name="new_password" autocomplete="new-password" data-rule="password"></div>
        </div>
    <?php else: ?>
        <p class="hint mt-2"><?= icon('lock') ?> Passwords are private. Only the user can change their password, under My Account &gt; Personal Details.</p>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn" type="submit"><?= icon('check2') ?><?= $user ? 'Save Changes' : 'Add User' ?></button>
        <a class="btn o" href="customers.php">Cancel</a>
    </div>
</form>
<?php require 'includes/footer.php'; ?>
