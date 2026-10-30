<?php
require_once 'includes/functions.php';

// ---------- Cart actions (all POST, all CSRF-protected) ----------
if (is_post()) {
    check_csrf();
    $action = post('action');
    $id = (int)post('product_id', '0');

    if ($action === 'add') {
        $p = get_product($id);
        $qty = max(1, (int)post('qty', '1'));
        if (!$p) {
            flash('error', 'That product is not available.');
        } else {
            $inCart = $_SESSION['cart'][$id] ?? 0;
            $newQty = min($inCart + $qty, (int)$p['stock_quantity']);
            if ($newQty < 1) {
                flash('error', $p['product_name'] . ' is out of stock.');
            } else {
                $_SESSION['cart'][$id] = $newQty;
                if ($newQty < $inCart + $qty) {
                    flash('info', 'Only ' . (int)$p['stock_quantity'] . ' of ' . $p['product_name'] . ' are available, so your cart has been limited to that.');
                } else {
                    flash('success', $p['product_name'] . ' was added to your cart.');
                }
            }
        }
    } elseif ($action === 'update' && isset($_SESSION['cart'][$id])) {
        $qty = (int)post('qty', '1');
        if ($qty < 1) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id] = $qty; // capped to stock in cart_details()
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
        flash('success', 'The item was removed from your cart.');
    } elseif ($action === 'checkout') {
        $method = post('fulfilment_method') === 'collection' ? 'collection' : 'delivery';
        $_SESSION['fulfilment_method'] = $method;
        redirect('checkout.php');
    }
    redirect('cart.php');
}

$cart = cart_details();
$method = $_SESSION['fulfilment_method'] ?? 'delivery';
$fee = $method === 'delivery' ? DELIVERY_FEE : 0;

$pageTitle = 'Your Cart';
$active = 'shop.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Your Shopping Cart</h1>
    <?php if ($cart['items']): ?><p><?= cart_count() ?> item<?= cart_count() === 1 ? '' : 's' ?> ready for checkout</p><?php endif; ?>
</div>

<?php if (!$cart['items']): ?>
    <div class="card empty">
        <?= icon('bag') ?>
        <h2 class="mt-0">Your cart is empty</h2>
        <p>Browse the shop and add a few fresh favourites.</p>
        <a class="btn" href="shop.php"><?= icon('basket2') ?>Start Shopping</a>
    </div>
<?php else: ?>
<div class="two">
    <div class="tw stack">
        <table>
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th><span class="skip">Remove</span></th></tr></thead>
            <tbody>
            <?php foreach ($cart['items'] as $item): $p = $item['product']; $id = (int)$p['product_id']; ?>
                <tr>
                    <td><a class="prod-cell" href="product.php?id=<?= $id ?>"><span class="mini"><?= icon(category_icon($p['category_name'])) ?></span><?= h($p['product_name']) ?></a></td>
                    <td data-label="Price"><?= on_special($p) ? '<span class="was">' . money($p['price']) . '</span><br>' : '' ?><?= money($item['price']) ?></td>
                    <td>
                        <form class="qty-form qty" method="post" action="cart.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <label class="skip" for="qty<?= $id ?>">Quantity for <?= h($p['product_name']) ?></label>
                            <input id="qty<?= $id ?>" type="number" name="qty" value="<?= $item['qty'] ?>" min="1" max="<?= (int)$p['stock_quantity'] ?>">
                            <button class="btn sm js-update" type="submit"><?= icon('arrow-repeat') ?>Update</button>
                        </form>
                    </td>
                    <td data-label="Total"><b><?= money($item['line']) ?></b></td>
                    <td>
                        <form method="post" action="cart.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <button class="linkbtn r icon-only" type="submit" title="Remove" aria-label="Remove <?= h($p['product_name']) ?>"><?= icon('trash3') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form class="card summary" method="post" action="cart.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="checkout">
        <h2 class="card-title"><?= icon('receipt') ?>Order Summary</h2>
        <fieldset class="opts">
            <legend>How would you like your order?</legend>
            <label class="opt"><input type="radio" name="fulfilment_method" value="delivery" <?= $method === 'delivery' ? 'checked' : '' ?>><?= icon('truck') ?><span class="opt-text">Home Delivery<small>Western Cape only</small></span><span class="opt-price"><?= money(DELIVERY_FEE) ?></span></label>
            <label class="opt"><input type="radio" name="fulfilment_method" value="collection" <?= $method === 'collection' ? 'checked' : '' ?>><?= icon('shop-window') ?><span class="opt-text">Collect from our store<small>Stellenbosch</small></span><span class="opt-price">Free</span></label>
        </fieldset>
        <div class="sum"><span>Subtotal</span><span><?= money($cart['subtotal']) ?></span></div>
        <?php if ($cart['discount'] > 0): ?>
            <div class="sum save"><span>Seasonal discount</span><span>&minus; <?= money($cart['discount']) ?></span></div>
        <?php endif; ?>
        <div class="sum" id="fee-row"><span>Delivery</span><span><?= $fee ? money($fee) : 'Free' ?></span></div>
        <div class="sum tot"><span>Total</span><span id="grand-total" data-goods="<?= $cart['goods'] ?>" data-fee="<?= DELIVERY_FEE ?>"><?= money($cart['goods'] + $fee) ?></span></div>
        <button class="btn block lgx mt-3" type="submit"><?= icon('lock-fill') ?>Proceed to Checkout</button>
        <a class="btn o block" href="shop.php"><?= icon('arrow-left') ?>Continue Shopping</a>
    </form>
</div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
