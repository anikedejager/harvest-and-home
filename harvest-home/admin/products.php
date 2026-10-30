<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// Delete (or deactivate when the product already appears in orders)
if (is_post() && post('action') === 'delete') {
    check_csrf();
    $id = (int)post('product_id');
    $stmt = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        db()->prepare("UPDATE products SET product_status = 'inactive' WHERE product_id = ?")->execute([$id]);
        flash('info', 'This product appears in past orders, so it was set to inactive instead of being deleted. It no longer shows in the shop.');
    } else {
        $img = db()->prepare('SELECT product_image FROM products WHERE product_id = ?');
        $img->execute([$id]);
        $path = $img->fetchColumn();
        db()->prepare('DELETE FROM products WHERE product_id = ?')->execute([$id]);
        if ($path && str_starts_with($path, 'assets/uploads/') && is_file(__DIR__ . '/../' . $path)) {
            unlink(__DIR__ . '/../' . $path);
        }
        flash('success', 'The product was deleted.');
    }
    redirect('products.php');
}

$q = trim($_GET['q'] ?? '');
$sql = product_sql();
$params = [];
if ($q !== '') {
    $sql .= ' WHERE p.product_name LIKE ? OR c.category_name LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY p.product_status, p.product_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products';
$adminActive = 'products.php';
require 'includes/header.php';
?>
<div class="toolbar">
    <div>
        <h1>Products</h1>
        <p class="mut mb-0"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p>
    </div>
    <a class="btn" href="product_form.php"><?= icon('plus-lg') ?>Add Product</a>
</div>
<form class="search" method="get" action="products.php" role="search">
    <div class="field"><?= icon('search') ?><input type="search" name="q" value="<?= h($q) ?>" placeholder="Search products or categories" aria-label="Search products"></div>
    <button class="btn" type="submit"><?= icon('search') ?>Search</button>
</form>
<div class="tw">
    <table>
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <span class="prod-cell">
                        <?php if ($p['product_image']): ?><img class="thumb" src="../<?= h($p['product_image']) ?>" alt=""><?php else: ?><span class="mini"><?= icon(category_icon($p['category_name'])) ?></span><?php endif; ?>
                        <?= h($p['product_name']) ?>
                    </span>
                </td>
                <td><?= h($p['category_name']) ?></td>
                <td><?= on_special($p) ? '<span class="was">' . money($p['price']) . '</span>' . money(sell_price($p)) : money($p['price']) ?></td>
                <td><?= (int)$p['stock_quantity'] <= 5 ? '<span class="stock-low">' . icon('exclamation-circle') . (int)$p['stock_quantity'] . '</span>' : (int)$p['stock_quantity'] ?></td>
                <td><span class="pill <?= $p['product_status'] === 'active' ? 'p2' : 'p5' ?>"><?= ucfirst($p['product_status']) ?></span></td>
                <td class="actions">
                    <a class="act" href="product_form.php?id=<?= (int)$p['product_id'] ?>"><?= icon('pencil-square') ?>Edit</a>
                    <form method="post" action="products.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                        <button class="linkbtn r" type="submit" data-confirm="Delete <?= h($p['product_name']) ?>?"><?= icon('trash3') ?>Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="6" class="mut">No products found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>
