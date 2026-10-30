<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    check_csrf();
    $id = (int)post('product_id');
    $stock = post('stock_quantity');
    if (!preg_match('/^\d+$/', $stock) || (int)$stock > 100000) {
        flash('error', 'Stock must be a whole number between 0 and 100 000.');
    } else {
        db()->prepare('UPDATE products SET stock_quantity = ? WHERE product_id = ?')->execute([(int)$stock, $id]);
        flash('success', 'Stock updated.');
    }
    redirect('inventory.php');
}

$products = db()->query(product_sql() . ' ORDER BY p.stock_quantity, p.product_name')->fetchAll();

$pageTitle = 'Inventory';
$adminActive = 'inventory.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Inventory</h1>
    <p>Products with the lowest stock are listed first. Items with 5 or fewer are marked low.</p>
</div>
<div class="tw">
    <table>
        <thead><tr><th>Product</th><th>Category</th><th>Status</th><th>In stock</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): $id = (int)$p['product_id']; ?>
            <tr>
                <td><b><?= h($p['product_name']) ?></b><?= (int)$p['stock_quantity'] <= 5 ? ' <span class="stock-low">' . icon('exclamation-circle') . 'Low</span>' : '' ?></td>
                <td><?= h($p['category_name']) ?></td>
                <td><span class="pill <?= $p['product_status'] === 'active' ? 'p2' : 'p5' ?>"><?= ucfirst($p['product_status']) ?></span></td>
                <td colspan="2">
                    <form class="qty" method="post" action="inventory.php" data-validate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        <label class="skip" for="st<?= $id ?>">Stock for <?= h($p['product_name']) ?></label>
                        <input id="st<?= $id ?>" name="stock_quantity" value="<?= (int)$p['stock_quantity'] ?>" inputmode="numeric" data-rule="whole" required>
                        <button class="btn sm" type="submit"><?= icon('check2') ?>Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>
