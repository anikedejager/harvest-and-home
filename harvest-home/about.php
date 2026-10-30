<?php
require_once 'includes/functions.php';
$pageTitle = 'About Us';
$active = 'about.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>About Harvest &amp; Home</h1>
    <p>Our story, our farm, our passion.</p>
</div>
<section class="hero">
    <div>
        <span class="eyebrow"><?= icon('house-heart') ?>Our story</span>
        <h2 class="text-white mt-0">From our farm to your home</h2>
        <p>Harvest &amp; Home is a local farmstall-inspired store in the Western Cape. Shop online, or visit us in store and shop in person. We bring fresh produce and homemade favourites together, with a focus on quality, convenience and supporting local.</p>
        <div class="cta"><a class="btn light" href="shop.php"><?= icon('basket2') ?>Visit the Shop</a></div>
    </div>
    <div class="hero-art"><img src="assets/img/logo.png?v=2" alt=""></div>
</section>
<div class="grid mt-4">
    <div class="card feature"><span class="ico"><?= icon('flower1') ?></span><b>Local Produce</b><p>Sourced from trusted local growers.</p></div>
    <div class="card feature"><span class="ico honey"><?= icon('cake2') ?></span><b>Homemade Favourites</b><p>Traditional recipes made with care.</p></div>
    <div class="card feature"><span class="ico blue"><?= icon('recycle') ?></span><b>Sustainable Practices</b><p>Thoughtful sourcing and reduced waste.</p></div>
    <div class="card feature"><span class="ico brown"><?= icon('shop-window') ?></span><b>Walk-in Welcome</b><p>Visit our store as well as shopping online.</p></div>
</div>
<?php require 'includes/footer.php'; ?>
