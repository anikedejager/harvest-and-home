<?php
require_once 'includes/functions.php';

// Search and filter values from the query string
$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$special = !empty($_GET['special']);
$sort = $_GET['sort'] ?? 'name';

$sql = product_sql() . " WHERE p.product_status = 'active'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}
if ($special) {
    $sql .= ' AND pr.promo_price IS NOT NULL AND pr.promo_price < p.price';
}
$orders = [
    'name'       => 'p.product_name ASC',
    'price_low'  => 'COALESCE(LEAST(pr.promo_price, p.price), p.price) ASC',
    'price_high' => 'COALESCE(LEAST(pr.promo_price, p.price), p.price) DESC',
];
$sql .= ' ORDER BY ' . ($orders[$sort] ?? $orders['name']);

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = get_categories();

// Builds a link that keeps the current search but changes one filter
function shop_link(array $change): string
{
    $query = array_merge(['q' => $_GET['q'] ?? '', 'sort' => $_GET['sort'] ?? ''], $change);
    return 'shop.php?' . http_build_query(array_filter($query, fn($v) => $v !== '' && $v !== 0 && $v !== null));
}

$pageTitle = 'Shop';
$active = 'shop.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Shop</h1>
    <p>Discover fresh produce, homemade favourites and more.</p>
</div>
<div class="notice"><?= icon('truck') ?><span>Home delivery in the Western Cape for <?= money(DELIVERY_FEE) ?>, or collect from our store for free. You choose in your cart.</span></div>

<form class="search" method="get" action="shop.php" role="search">
    <label for="q" class="skip">Search products</label>
    <div class="field"><?= icon('search') ?><input id="q" type="search" name="q" value="<?= h($q) ?>" placeholder="Search products..."></div>
    <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
    <select name="sort" aria-label="Sort products">
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A to Z</option>
        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
    </select>
    <button class="btn" type="submit"><?= icon('search') ?>Search</button>
</form>

<div class="chips" aria-label="Filter by category">
    <a class="chip <?= !$categoryId && !$special ? 'on' : '' ?>" href="<?= h(shop_link([])) ?>"><?= icon('grid') ?>All</a>
    <?php foreach ($categories as $c): ?>
        <a class="chip <?= $categoryId === (int)$c['category_id'] ? 'on' : '' ?>" href="<?= h(shop_link(['category' => (int)$c['category_id']])) ?>"><?= icon(category_icon($c['category_name'])) ?><?= h($c['category_name']) ?></a>
    <?php endforeach; ?>
    <a class="chip <?= $special ? 'on' : '' ?>" href="<?= h(shop_link(['special' => 1])) ?>"><?= icon('tag') ?>On Special</a>
</div>

<p class="result-count"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> found<?= $q !== '' ? ' for "' . h($q) . '"' : '' ?></p>

<?php if ($products): ?>
    <div class="grid">
        <?php foreach ($products as $p) echo product_card($p); ?>
    </div>
<?php else: ?>
    <div class="card empty">
        <?= icon('search') ?>
        <p>No products match your search.</p>
        <a class="btn o" href="shop.php"><?= icon('x-circle') ?>Clear filters</a>
    </div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
