<?php
require_once 'includes/functions.php';
require_login();
if (is_admin()) redirect('admin/index.php');

$uid = current_user()['user_id'];
$editing = isset($_GET['edit']);
$error = '';

if (is_post()) {
    check_csrf();
    if (post('action') === 'remove') {
        db()->prepare('UPDATE users SET address = NULL WHERE user_id = ?')->execute([$uid]);
        flash('success', 'Your saved address was removed.');
        redirect('address.php');
    }
    $address = post('address');
    if (mb_strlen($address) < 8 || mb_strlen($address) > 255) {
        $error = 'Enter your full address (street, suburb and postal code).';
        $editing = true;
    } else {
        db()->prepare('UPDATE users SET address = ? WHERE user_id = ?')->execute([$address, $uid]);
        flash('success', 'Your address has been saved.');
        redirect('address.php');
    }
}

$stmt = db()->prepare('SELECT address FROM users WHERE user_id = ?');
$stmt->execute([$uid]);
$address = $stmt->fetchColumn();

$pageTitle = 'Address Book';
$active = 'account.php';
require 'includes/header.php';
?>
<h1>My Account</h1>
<div class="two side-first">
    <?= account_menu('address.php') ?>
    <div>
        <h2 class="mt-0">Address Book</h2>
        <?php if ($address && !$editing): ?>
            <div class="card" style="max-width:440px">
                <div class="info-item">
                    <span class="ico"><?= icon('house-heart') ?></span>
                    <div><b>Home</b><p><?= nl2br(h($address)) ?></p></div>
                </div>
                <div class="actions mt-3">
                    <a class="act" href="address.php?edit=1"><?= icon('pencil-square') ?>Edit</a>
                    <form method="post" action="address.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <button class="linkbtn r" type="submit" data-confirm="Remove this address?"><?= icon('trash3') ?>Remove</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <form class="card" method="post" action="address.php" data-validate style="max-width:540px">
                <?= csrf_field() ?>
                <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
                <label for="address"><?= $address ? 'Edit your address' : 'Add an address' ?></label>
                <textarea id="address" name="address" rows="3" maxlength="255" placeholder="Street, suburb, postal code, Western Cape" required><?= h($_POST['address'] ?? $address ?? '') ?></textarea>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn" type="submit"><?= icon('check2') ?>Save Address</button>
                    <?php if ($address): ?><a class="btn o" href="address.php">Cancel</a><?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
        <p class="hint mt-3"><?= icon('info-circle') ?> This address is shown at checkout for home delivery.</p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
