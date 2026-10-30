<?php
require_once 'includes/functions.php';
require_login();

// Customers may only see their own orders
$stmt = db()->prepare('SELECT o.*, p.payment_status FROM orders o LEFT JOIN payments p ON p.order_id = o.order_id
                       WHERE o.order_id = ? AND o.user_id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0), current_user()['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    flash('error', 'Order not found.');
    redirect('orders.php');
}

$stmt = db()->prepare('SELECT oi.*, pr.product_name FROM order_items oi JOIN products pr ON pr.product_id = oi.product_id WHERE oi.order_id = ?');
$stmt->execute([$order['order_id']]);
$items = $stmt->fetchAll();
$goods = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items));

$pageTitle = 'Order #' . order_no($order['order_id']);
$active = 'account.php';
require 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="account.php">My Account</a><?= icon('chevron-right') ?>
    <a href="orders.php">Order History</a><?= icon('chevron-right') ?>
    <span>#<?= order_no($order['order_id']) ?></span>
</nav>
<h1>Order #<?= order_no($order['order_id']) ?></h1>
<div class="meta">
    <span><?= icon('calendar3') ?><?= date('j M Y, H:i', strtotime($order['order_date'])) ?></span>
    <span><?= icon($order['fulfilment_method'] === 'delivery' ? 'truck' : 'shop-window') ?><?= $order['fulfilment_method'] === 'delivery' ? 'Home delivery' : 'Collection from our store' ?></span>
    <?= status_pill($order['order_status']) ?>
</div>
<?php if ($order['order_status'] === 'collection_pending'): ?>
    <div class="notice"><?= icon('shop-window') ?><span><b>Pickup required:</b> please collect from our store during business hours.</span></div>
<?php endif; ?>
<?php if ($order['delivery_address']): ?>
    <div class="notice"><?= icon('geo-alt') ?><span>Delivery address: <?= h($order['delivery_address']) ?></span></div>
<?php endif; ?>
<div class="two">
    <div class="tw">
        <table>
            <thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $i): ?>
                <tr><td><b><?= h($i['product_name']) ?></b></td><td><?= (int)$i['quantity'] ?></td><td><?= money($i['price']) ?></td><td><?= money($i['quantity'] * $i['price']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card summary">
        <h2 class="card-title"><?= icon('receipt') ?>Summary</h2>
        <div class="sum"><span>Items</span><span><?= money($goods) ?></span></div>
        <?php if ($order['fulfilment_method'] === 'delivery'): ?>
            <div class="sum"><span>Delivery</span><span><?= money($order['delivery_fee']) ?></span></div>
        <?php endif; ?>
        <div class="sum tot"><span>Total</span><span><?= money($order['total_amount']) ?></span></div>
        <p class="hint mt-3"><?= icon('credit-card') ?> Payment: <?= h(ucfirst($order['payment_status'] ?? 'pending')) ?> (online payment)</p>
    </div>
</div>
<a class="btn o mt-4" href="orders.php"><?= icon('arrow-left') ?>Back to Order History</a>
<?php require 'includes/footer.php'; ?>
