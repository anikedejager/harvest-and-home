<?php
require_once 'includes/functions.php';
require_login();
if (is_admin()) redirect('admin/orders.php');

$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC');
$stmt->execute([current_user()['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Order History';
$active = 'account.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Order History</h1>
    <p>View and track all your orders.</p>
</div>
<div class="two side-first">
    <?= account_menu('orders.php') ?>
    <div>
        <?= orders_table($orders) ?>
        <p class="result-count mt-3">Showing <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
