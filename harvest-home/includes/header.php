<?php
/* Customer-site header. Pages set $pageTitle and $active before including this. */
$pageTitle = $pageTitle ?? 'Harvest & Home';
$active = $active ?? '';
$count = cart_count();
$nav = [
    'index.php'   => ['Home', 'house'],
    'shop.php'    => ['Shop', 'shop'],
    'about.php'   => ['About Us', 'info-circle'],
    'contact.php' => ['Contact', 'envelope'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | Harvest &amp; Home</title>
    <meta name="description" content="Harvest & Home: fresh farm produce and homemade favourites from the Western Cape, delivered or ready for collection.">
    <meta name="theme-color" content="#2F5D3A">
    <link rel="icon" href="assets/img/logo.png?v=2">
    <?= head_assets() ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="top">
    <a class="logo" href="index.php">
        <img src="assets/img/logo.png?v=2" alt="Harvest &amp; Home logo">
        <span>Harvest &amp; Home<small>From Our Farm to Your Home</small></span>
    </a>
    <button class="menu-btn" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Open menu"><?= icon('list') ?></button>
    <nav id="main-nav">
        <?php foreach ($nav as $url => [$label, $ic]): ?>
            <a href="<?= $url ?>" class="<?= $active === $url ? 'on' : '' ?>"><?= icon($ic) ?><?= $label ?></a>
        <?php endforeach; ?>
        <?php if (is_logged_in() && !is_admin()): ?>
            <a href="account.php" class="<?= $active === 'account.php' ? 'on' : '' ?>"><?= icon('person-circle') ?>My Account</a>
            <a href="logout.php"><?= icon('box-arrow-right') ?>Logout</a>
        <?php elseif (is_admin()): ?>
            <a href="admin/index.php"><?= icon('speedometer2') ?>Admin</a>
            <a href="logout.php"><?= icon('box-arrow-right') ?>Logout</a>
        <?php else: ?>
            <a href="login.php" class="<?= $active === 'login.php' ? 'on' : '' ?>"><?= icon('box-arrow-in-right') ?>Login / Register</a>
        <?php endif; ?>
    </nav>
    <div class="icons">
        <a class="ic" href="shop.php" title="Search products" aria-label="Search products"><?= icon('search') ?></a>
        <a class="ic" href="cart.php" title="Shopping cart" aria-label="Shopping cart, <?= $count ?> items"><?= icon('bag') ?><?php if ($count): ?><span class="bd"><?= $count ?></span><?php endif; ?></a>
    </div>
</header>
<main id="main" class="wrap">
<?= show_flash() ?>
