<?php
require_once 'includes/functions.php';
require_login();

$stmt = db()->prepare('SELECT * FROM orders WHERE order_id = ? AND user_id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0), current_user()['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    redirect('orders.php');
}

$pageTitle = 'Order Confirmed';
require 'includes/header.php';
?>
<div class="ctr">
    <div class="success-icon"><?= icon('check-lg') ?></div>
    <h1>Thank You for Your Order!</h1>
    <p class="lead-text">Your order has been placed successfully.</p>
    <div class="card">
        <div class="sum"><span>Order number</span><b>#<?= order_no($order['order_id']) ?></b></div>
        <div class="sum"><span>Total</span><b><?= money($order['total_amount']) ?></b></div>
        <div class="sum"><span>Status</span><?= status_pill($order['order_status']) ?></div>
        <div class="notice mt-3 mb-0">
            <?= icon($order['fulfilment_method'] === 'delivery' ? 'truck' : 'shop-window') ?>
            <span><?= $order['fulfilment_method'] === 'delivery'
                ? 'Your order will be delivered within 5 working days.'
                : 'We will let you know when your order is ready. Please collect it from our store during business hours.' ?></span>
        </div>
        <a class="btn block lgx mt-3" href="order.php?id=<?= (int)$order['order_id'] ?>"><?= icon('receipt') ?>View Order</a>
        <a class="btn o block" href="shop.php"><?= icon('basket2') ?>Continue Shopping</a>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
