<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();

$stats = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(order_status = 'processing') AS processing,
        SUM(order_status = 'collection_pending') AS pending,
        COALESCE(SUM(CASE WHEN order_status <> 'cancelled' THEN total_amount END), 0) AS revenue
    FROM orders")->fetch();
$productCount = $pdo->query("SELECT COUNT(*) FROM products WHERE product_status = 'active'")->fetchColumn();
$customerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$lowStock = $pdo->query("SELECT product_id, product_name, stock_quantity FROM products
                         WHERE product_status = 'active' AND stock_quantity <= 5 ORDER BY stock_quantity")->fetchAll();
$recent = $pdo->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON u.user_id = o.user_id
                       ORDER BY o.order_date DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$adminActive = 'index.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Dashboard</h1>
    <p>Welcome, <?= h(current_user()['full_name']) ?>.</p>
</div>
<div class="stats">
    <div class="card stat"><span class="ico"><?= icon('receipt') ?></span><div><b><?= (int)$stats['total'] ?></b><span>Total Orders</span></div></div>
    <div class="card stat"><span class="ico honey"><?= icon('truck') ?></span><div><b><?= (int)$stats['processing'] ?></b><span>Processing – Delivery</span></div></div>
    <div class="card stat"><span class="ico blue"><?= icon('shop-window') ?></span><div><b><?= (int)$stats['pending'] ?></b><span>Collection Pending</span></div></div>
    <div class="card stat"><span class="ico brown"><?= icon('box-seam') ?></span><div><b><?= (int)$productCount ?></b><span>Active Products</span></div></div>
    <div class="card stat"><span class="ico plum"><?= icon('people') ?></span><div><b><?= (int)$customerCount ?></b><span>Customers</span></div></div>
    <div class="card stat"><span class="ico"><?= icon('graph-up-arrow') ?></span><div><b><?= money($stats['revenue']) ?></b><span>Sales (excl. cancelled)</span></div></div>
</div>

<?php if ($lowStock): ?>
    <div class="alert alert-info">
        <div>
            <b>Low stock:</b>
            <?= implode(', ', array_map(fn($p) => h($p['product_name']) . ' (' . (int)$p['stock_quantity'] . ')', $lowStock)) ?>.
            <a href="inventory.php">Update inventory</a>
        </div>
    </div>
<?php endif; ?>

<div class="sec-head">
    <h2>Recent Orders</h2>
    <a href="orders.php">View all orders <?= icon('arrow-right') ?></a>
</div>
<?php require 'includes/orders_table.php'; render_admin_orders($recent); ?>
<div class="d-flex flex-wrap gap-2 mt-4">
    <a class="btn" href="product_form.php"><?= icon('plus-lg') ?>Add Product</a>
    <a class="btn o" href="promotions.php"><?= icon('megaphone') ?>Manage Promotions</a>
    <a class="btn o" href="inventory.php"><?= icon('boxes') ?>Update Inventory</a>
</div>
<?php require 'includes/footer.php'; ?>
