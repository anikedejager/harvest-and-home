<?php
require_once 'includes/functions.php';
require_login();
if (is_admin()) {
    flash('info', 'Administrator accounts cannot place orders. Log in with a customer account.');
    redirect('cart.php');
}

$cart = cart_details();
if (!$cart['items']) {
    redirect('cart.php');
}

$saved = $_SESSION['checkout'] ?? [];
$method = $saved['method'] ?? ($_SESSION['fulfilment_method'] ?? 'delivery');
$form = [
    'street' => $saved['street'] ?? '',
    'suburb' => $saved['suburb'] ?? '',
    'postal' => $saved['postal'] ?? '',
];
$errors = [];

if (is_post()) {
    check_csrf();
    $method = post('fulfilment_method') === 'collection' ? 'collection' : 'delivery';
    $form = ['street' => post('street'), 'suburb' => post('suburb'), 'postal' => post('postal')];

    if ($method === 'delivery') {
        if (mb_strlen($form['street']) < 3) $errors['street'] = 'Enter your street address.';
        if (mb_strlen($form['suburb']) < 2) $errors['suburb'] = 'Enter your suburb or town.';
        if (!preg_match('/^\d{4}$/', $form['postal'])) $errors['postal'] = 'Enter a 4-digit postal code.';
    }

    if (!$errors) {
        $address = $method === 'delivery'
            ? $form['street'] . ', ' . $form['suburb'] . ', ' . $form['postal'] . ', Western Cape'
            : null;
        if ($method === 'delivery' && post('save_address') === '1') {
            $stmt = db()->prepare('UPDATE users SET address = ? WHERE user_id = ?');
            $stmt->execute([$address, current_user()['user_id']]);
        }
        $_SESSION['fulfilment_method'] = $method;
        $_SESSION['checkout'] = $form + ['method' => $method, 'address' => $address];
        redirect('payment.php');
    }
}

// Saved address from the account, shown as a hint
$stmt = db()->prepare('SELECT address FROM users WHERE user_id = ?');
$stmt->execute([current_user()['user_id']]);
$savedAddress = $stmt->fetchColumn();

$fee = $method === 'delivery' ? DELIVERY_FEE : 0;
$pageTitle = 'Checkout';
$active = 'shop.php';
require 'includes/header.php';

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="err">' . h($errors[$key]) . '</span>' : '';
}
?>
<nav class="crumbs" aria-label="Checkout steps">
    <a href="cart.php"><?= icon('bag') ?> Cart</a><?= icon('chevron-right') ?>
    <b><?= icon('truck') ?> Checkout</b><?= icon('chevron-right') ?>
    <span><?= icon('credit-card') ?> Payment</span>
</nav>
<h1>Checkout</h1>
<div class="two mt-3">
    <form class="card" method="post" action="checkout.php" data-validate>
        <?= csrf_field() ?>
        <h2 class="card-title"><?= icon('box-seam') ?>Delivery or Collection</h2>
        <div class="opts">
            <label class="opt"><input type="radio" name="fulfilment_method" value="delivery" <?= $method === 'delivery' ? 'checked' : '' ?>><?= icon('truck') ?><span class="opt-text">Home Delivery<small>Western Cape only, up to 5 working days</small></span><span class="opt-price"><?= money(DELIVERY_FEE) ?></span></label>
            <label class="opt"><input type="radio" name="fulfilment_method" value="collection" <?= $method === 'collection' ? 'checked' : '' ?>><?= icon('shop-window') ?><span class="opt-text">Collection<small>Collect from our store</small></span><span class="opt-price">Free</span></label>
        </div>

        <div id="address-box" <?= $method === 'collection' ? 'hidden' : '' ?>>
            <h2 class="card-title mt-4"><?= icon('geo-alt') ?>Delivery Address</h2>
            <?php if ($savedAddress): ?><p class="hint mb-3"><?= icon('bookmark') ?> Saved address: <?= h($savedAddress) ?></p><?php endif; ?>
            <div class="f2">
                <div>
                    <label for="street">Street address</label>
                    <input id="street" name="street" value="<?= h($form['street']) ?>" maxlength="120" data-req="1" class="<?= isset($errors['street']) ? 'bad' : '' ?>">
                    <?= field_error($errors, 'street') ?>
                </div>
                <div>
                    <label for="suburb">Suburb / Town</label>
                    <input id="suburb" name="suburb" value="<?= h($form['suburb']) ?>" maxlength="80" data-req="1" class="<?= isset($errors['suburb']) ? 'bad' : '' ?>">
                    <?= field_error($errors, 'suburb') ?>
                </div>
                <div>
                    <label for="postal">Postal code</label>
                    <input id="postal" name="postal" value="<?= h($form['postal']) ?>" inputmode="numeric" maxlength="4" data-rule="postal" data-req="1" class="<?= isset($errors['postal']) ? 'bad' : '' ?>">
                    <?= field_error($errors, 'postal') ?>
                </div>
                <div>
                    <label for="province">Province</label>
                    <input id="province" value="Western Cape" disabled>
                </div>
            </div>
            <label class="inline"><input type="checkbox" name="save_address" value="1"> Save this as my address</label>
        </div>

        <h2 class="card-title mt-4"><?= icon('credit-card') ?>Payment Method</h2>
        <div class="opts">
            <label class="opt"><input type="radio" name="payment_method" value="online_payment" checked><?= icon('credit-card-2-front') ?><span class="opt-text">Online card payment<small>Pay securely on the next page</small></span></label>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn lgx" type="submit">Continue to Payment<?= icon('arrow-right') ?></button>
            <a class="btn o lgx" href="cart.php"><?= icon('arrow-left') ?>Back to Cart</a>
        </div>
    </form>

    <div class="card summary">
        <h2 class="card-title"><?= icon('receipt') ?>Your Order</h2>
        <?php foreach ($cart['items'] as $item): ?>
            <div class="sum"><span><?= h($item['product']['product_name']) ?> &times; <?= $item['qty'] ?></span><span><?= money($item['line']) ?></span></div>
        <?php endforeach; ?>
        <div class="sum sub"><span>Subtotal</span><span><?= money($cart['subtotal']) ?></span></div>
        <?php if ($cart['discount'] > 0): ?>
            <div class="sum save"><span>Seasonal discount</span><span>&minus; <?= money($cart['discount']) ?></span></div>
        <?php endif; ?>
        <div class="sum" id="fee-row"><span>Delivery</span><span><?= $fee ? money($fee) : 'Free' ?></span></div>
        <div class="sum tot"><span>Total</span><span id="grand-total" data-goods="<?= $cart['goods'] ?>" data-fee="<?= DELIVERY_FEE ?>"><?= money($cart['goods'] + $fee) ?></span></div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
