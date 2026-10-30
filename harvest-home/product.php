<?php
require_once 'includes/functions.php';

$p = get_product((int)($_GET['id'] ?? 0));
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require 'includes/header.php';
    echo '<div class="card empty">' . icon('search') . '<h1>Product not found</h1><p>This product is not available.</p>'
       . '<a class="btn" href="shop.php">' . icon('arrow-left') . 'Back to the shop</a></div>';
    require 'includes/footer.php';
    exit;
}
$stock = (int)$p['stock_quantity'];

$pageTitle = $p['product_name'];
$active = 'shop.php';
require 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="index.php">Home</a><?= icon('chevron-right') ?>
    <a href="shop.php">Shop</a><?= icon('chevron-right') ?>
    <a href="shop.php?category=<?= (int)$p['category_id'] ?>"><?= h($p['category_name']) ?></a><?= icon('chevron-right') ?>
    <span><?= h($p['product_name']) ?></span>
</nav>
<div class="two even">
    <div class="ph big"><?= product_image($p) ?></div>
    <div class="card">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="tag green"><?= icon(category_icon($p['category_name'])) ?><?= h($p['category_name']) ?></span>
            <?php if (on_special($p)): ?><span class="tag"><?= icon('stars') ?>Seasonal Special</span><?php endif; ?>
        </div>
        <h1><?= h($p['product_name']) ?></h1>
        <p class="price big"><?= on_special($p) ? '<span class="was">' . money($p['price']) . '</span>' : '' ?><?= money(sell_price($p)) ?></p>
        <p class="mut"><?= nl2br(h($p['description'])) ?></p>
        <p class="mt-3"><?= $stock > 0
            ? '<span class="stock-ok">' . icon('check-circle-fill') . 'In stock (' . $stock . ' available)</span>'
            : '<span class="stock-low">' . icon('x-circle') . 'Out of stock</span>' ?></p>
        <?php if ($stock > 0): ?>
            <form class="buy" method="post" action="cart.php" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                <div class="qty-field">
                    <label for="qty">Quantity</label>
                    <input id="qty" type="number" name="qty" value="1" min="1" max="<?= $stock ?>" data-rule="whole" required>
                </div>
                <button class="btn lgx" type="submit"><?= icon('bag-plus') ?>Add to Cart</button>
                <a class="btn o lgx" href="shop.php"><?= icon('arrow-left') ?>Back to Shop</a>
            </form>
        <?php else: ?>
            <div class="buy"><a class="btn o" href="shop.php"><?= icon('arrow-left') ?>Back to Shop</a></div>
        <?php endif; ?>
        <ul class="checks">
            <li><?= icon('truck') ?>Western Cape delivery for <?= money(DELIVERY_FEE) ?>, within 5 working days</li>
            <li><?= icon('shop-window') ?>Free collection from our Stellenbosch store</li>
            <li><?= icon('shield-check') ?>Secure checkout, card details are never stored</li>
        </ul>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
