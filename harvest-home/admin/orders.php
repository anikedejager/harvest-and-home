<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require 'includes/orders_table.php';

$statuses = ['processing', 'delivered', 'collection_pending', 'collected', 'cancelled'];
$filter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$customer = (int)($_GET['customer'] ?? 0);

$sql = 'SELECT o.*, u.full_name FROM orders o JOIN users u ON u.user_id = o.user_id WHERE 1 = 1';
$params = [];
if ($filter) { $sql .= ' AND o.order_status = ?'; $params[] = $filter; }
if ($customer) { $sql .= ' AND o.user_id = ?'; $params[] = $customer; }
$sql .= ' ORDER BY o.order_date DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders';
$adminActive = 'orders.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Orders</h1>
    <p>Filter by status and open an order to update it.</p>
</div>
<div class="chips">
    <a class="chip <?= !$filter ? 'on' : '' ?>" href="orders.php<?= $customer ? '?customer=' . $customer : '' ?>"><?= icon('grid') ?>All</a>
    <?php foreach ($statuses as $s): ?>
        <a class="chip <?= $filter === $s ? 'on' : '' ?>" href="orders.php?<?= http_build_query(array_filter(['status' => $s, 'customer' => $customer])) ?>"><?= h(status_label($s)) ?></a>
    <?php endforeach; ?>
</div>
<?php if ($customer && $orders): ?><div class="notice"><?= icon('person') ?><span>Showing orders for <?= h($orders[0]['full_name']) ?>. <a href="orders.php">Show all customers</a></span></div><?php endif; ?>
<?php render_admin_orders($orders); ?>
<p class="result-count mt-3"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></p>
<?php require 'includes/footer.php'; ?>
