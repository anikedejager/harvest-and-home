<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);
$load = db()->prepare('SELECT o.*, u.full_name, u.email, u.phone, p.payment_status, p.payment_method
                       FROM orders o JOIN users u ON u.user_id = o.user_id
                       LEFT JOIN payments p ON p.order_id = o.order_id WHERE o.order_id = ?');
$load->execute([$id]);
$order = $load->fetch();
if (!$order) {
    flash('error', 'Order not found.');
    redirect('orders.php');
}
$allowed = allowed_statuses($order['fulfilment_method']);

// ---------- Update order status ----------
if (is_post()) {
    check_csrf();
    $new = post('order_status');
    if ($order['order_status'] === 'cancelled') {
        flash('error', 'Cancelled orders cannot be changed.');
    } elseif (!in_array($new, $allowed, true)) {
        flash('error', 'That status is not valid for a ' . $order['fulfilment_method'] . ' order.');
    } elseif ($new !== $order['order_status']) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?')->execute([$new, $id]);
        if ($new === 'cancelled') {
            // Put the items back into stock
            $pdo->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.product_id
                           SET p.stock_quantity = p.stock_quantity + oi.quantity WHERE oi.order_id = ?')->execute([$id]);
        }
        $pdo->commit();
        flash('success', 'Order status updated to ' . status_label($new) . ($new === 'cancelled' ? '. Stock has been returned.' : '.'));
    }
    redirect('order.php?id=' . $id);
}

$stmt = db()->prepare('SELECT oi.*, pr.product_name FROM order_items oi JOIN products pr ON pr.product_id = oi.product_id WHERE oi.order_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
$goods = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items));

$pageTitle = 'Order #' . order_no($id);
$adminActive = 'orders.php';
require 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="orders.php">Orders</a><?= icon('chevron-right') ?>
    <span>#<?= order_no($id) ?></span>
</nav>
<h1>Order #<?= order_no($id) ?></h1>
<div class="meta">
    <span><?= icon('calendar3') ?><?= date('j M Y, H:i', strtotime($order['order_date'])) ?></span>
    <span><?= icon('person') ?><?= h($order['full_name']) ?></span>
    <span><?= icon($order['fulfilment_method'] === 'delivery' ? 'truck' : 'shop-window') ?><?= $order['fulfilment_method'] === 'delivery' ? 'Home delivery' : 'Collection' ?></span>
    <?= status_pill($order['order_status']) ?>
</div>
<div class="two">
    <div>
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
        <div class="card mt-3">
            <h2 class="card-title"><?= icon('person-vcard') ?>Customer</h2>
            <div class="info-list">
                <div class="info-item"><span class="ico"><?= icon('person') ?></span><div><b><?= h($order['full_name']) ?></b><p><a href="mailto:<?= h($order['email']) ?>"><?= h($order['email']) ?></a><br><?= h($order['phone']) ?></p></div></div>
                <?php if ($order['delivery_address']): ?>
                    <div class="info-item"><span class="ico honey"><?= icon('geo-alt') ?></span><div><b>Deliver to</b><p><?= h($order['delivery_address']) ?></p></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card summary">
        <h2 class="card-title"><?= icon('receipt') ?>Summary</h2>
        <div class="sum"><span>Items</span><span><?= money($goods) ?></span></div>
        <div class="sum"><span>Delivery fee</span><span><?= money($order['delivery_fee']) ?></span></div>
        <div class="sum tot"><span>Total</span><span><?= money($order['total_amount']) ?></span></div>
        <p class="hint mt-2 mb-3"><?= icon('credit-card') ?> Payment: <?= h(ucfirst($order['payment_status'] ?? 'none')) ?></p>
        <?php if ($order['order_status'] === 'cancelled'): ?>
            <div class="notice mb-0"><?= icon('lock') ?><span>This order was cancelled and can no longer be changed.</span></div>
        <?php else: ?>
            <form method="post" action="order.php">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <label for="order_status">Update status</label>
                <select id="order_status" name="order_status">
                    <?php foreach ($allowed as $s): ?>
                        <option value="<?= $s ?>" <?= $s === $order['order_status'] ? 'selected' : '' ?>><?= h(status_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn block mt-0" type="submit" data-confirm="Change the status of this order?"><?= icon('arrow-repeat') ?>Update Status</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<a class="btn o mt-4" href="orders.php"><?= icon('arrow-left') ?>Back to Orders</a>
<?php require 'includes/footer.php'; ?>
