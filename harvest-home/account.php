<?php
require_once 'includes/functions.php';
require_login();
if (is_admin()) redirect('admin/index.php');

$uid = current_user()['user_id'];
$stmt = db()->prepare('SELECT COUNT(*) AS n, MAX(order_date) AS last_date FROM orders WHERE user_id = ?');
$stmt->execute([$uid]);
$summary = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 3');
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$stmt = db()->prepare('SELECT address FROM users WHERE user_id = ?');
$stmt->execute([$uid]);
$address = $stmt->fetchColumn();

$pageTitle = 'My Account';
$active = 'account.php';
require 'includes/header.php';
?>
<h1>My Account</h1>
<div class="two side-first">
    <?= account_menu('account.php') ?>
    <div>
        <h2 class="mt-0">Welcome back, <?= h(current_user()['full_name']) ?>!</h2>
        <div class="stats">
            <div class="card stat"><span class="ico"><?= icon('bag-check') ?></span><div><b><?= (int)$summary['n'] ?></b><span>Total Orders</span></div></div>
            <div class="card stat"><span class="ico honey"><?= icon('cash-coin') ?></span><div><b><?= $recent ? money($recent[0]['total_amount']) : 'None' ?></b><span>Latest Order</span></div></div>
            <div class="card stat"><span class="ico brown"><?= icon('geo-alt') ?></span><div><b><?= $address ? 1 : 0 ?></b><span>Saved Address</span></div></div>
        </div>
        <div class="sec-head mt-4">
            <h2>Recent Orders</h2>
            <?php if ($recent): ?><a href="orders.php">All orders <?= icon('arrow-right') ?></a><?php endif; ?>
        </div>
        <?= orders_table($recent) ?>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
