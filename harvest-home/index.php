<?php
require_once 'includes/functions.php';

$specials = db()->query(product_sql() . " WHERE p.product_status = 'active' AND pr.promo_price IS NOT NULL AND pr.promo_price < p.price ORDER BY p.product_name")->fetchAll();
$categories = get_categories();

$pageTitle = 'Home';
$active = 'index.php';
require 'includes/header.php';
?>
<section class="hero">
    <div>
        <span class="eyebrow"><?= icon('geo-alt-fill') ?>Western Cape farmstall</span>
        <h1>From Our Farm to Your Home</h1>
        <p>Fresh local produce, homemade favourites and carefully prepared goods from the Western Cape.</p>
        <div class="cta">
            <a class="btn light lgx" href="shop.php"><?= icon('basket2') ?>Shop Now</a>
            <?php if ($specials): ?><a class="btn ghost lgx" href="shop.php?special=1"><?= icon('stars') ?>View Specials</a><?php endif; ?>
        </div>
    </div>
    <div class="hero-art"><img src="assets/img/logo.png?v=2" alt=""></div>
</section>

<div class="perks">
    <div class="perk"><span class="ico"><?= icon('truck') ?></span><div><b>Home delivery</b><span>Anywhere in the Western Cape, <?= money(DELIVERY_FEE) ?> flat fee</span></div></div>
    <div class="perk"><span class="ico honey"><?= icon('shop-window') ?></span><div><b>Free collection</b><span>Collect from our Stellenbosch store</span></div></div>
    <div class="perk"><span class="ico brown"><?= icon('flower1') ?></span><div><b>Proudly local</b><span>Grown nearby and made by hand</span></div></div>
</div>

<div class="sec-head">
    <h2>Shop by Category</h2>
    <a href="shop.php">All products <?= icon('arrow-right') ?></a>
</div>
<div class="grid cats">
    <?php foreach ($categories as $c): ?>
        <a class="card cat" href="shop.php?category=<?= (int)$c['category_id'] ?>">
            <span class="ico"><?= icon(category_icon($c['category_name'])) ?></span>
            <b><?= h($c['category_name']) ?></b>
            <p><?= h($c['description']) ?></p>
            <span class="more">Explore <?= icon('arrow-right') ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($specials): ?>
<section class="card promo-band">
    <div>
        <span class="tag"><?= icon('stars') ?>Seasonal Harvest</span>
        <h2>Fresh for the season.</h2>
        <p class="mut">Enjoy special prices on selected seasonal produce while stocks last.</p>
    </div>
    <a class="btn" href="shop.php?special=1"><?= icon('tag-fill') ?>Shop Seasonal Offers</a>
</section>

<div class="sec-head">
    <h2>Seasonal Specials</h2>
    <a href="shop.php?special=1">See all <?= icon('arrow-right') ?></a>
</div>
<div class="grid">
    <?php foreach ($specials as $p) echo product_card($p); ?>
</div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
