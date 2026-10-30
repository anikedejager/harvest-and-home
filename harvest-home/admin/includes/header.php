<?php
/* Admin header: checks the admin role and draws the side menu. */
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$adminActive = $adminActive ?? '';
$menu = [
    'index.php'      => ['Dashboard', 'speedometer2'],
    'products.php'   => ['Products', 'box-seam'],
    'categories.php' => ['Categories', 'tags'],
    'inventory.php'  => ['Inventory', 'boxes'],
    'orders.php'     => ['Orders', 'receipt'],
    'customers.php'  => ['Users', 'people'],
    'promotions.php' => ['Promotions', 'megaphone'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#183420">
    <title><?= h($pageTitle ?? 'Admin') ?> | Harvest &amp; Home Admin</title>
    <link rel="icon" href="../assets/img/logo.png?v=2">
    <?= head_assets('../') ?>
</head>
<body>
<div class="adm">
    <aside>
        <div class="brand">
            <img src="../assets/img/logo.png?v=2" alt="Harvest &amp; Home logo">
            <h3>Harvest &amp; Home<br><small>Admin</small></h3>
            <button class="menu-btn" type="button" aria-expanded="false" aria-controls="admin-nav" aria-label="Open menu"><?= icon('list') ?></button>
        </div>
        <nav id="admin-nav">
            <span class="nav-label">Manage</span>
            <?php foreach ($menu as $url => [$label, $ic]): ?>
                <a href="<?= $url ?>" class="<?= $adminActive === $url ? 'on' : '' ?>"><?= icon($ic) ?><?= $label ?></a>
            <?php endforeach; ?>
            <span class="nav-label">Store</span>
            <a href="../index.php" target="_blank" rel="noopener"><?= icon('box-arrow-up-right') ?>View Shop</a>
            <a href="user_form.php?id=<?= (int)current_user()['user_id'] ?>" class="<?= ($adminActive === 'profile') ? 'on' : '' ?>"><?= icon('person-gear') ?>My Profile</a>
            <a href="logout.php"><?= icon('box-arrow-right') ?>Logout</a>
            <div class="adm-user">
                <?= icon('person-circle') ?>
                <div><b><?= h(current_user()['full_name']) ?></b><span title="<?= h(current_user()['email']) ?>"><?= h(current_user()['email']) ?></span></div>
            </div>
        </nav>
    </aside>
    <main id="main">
    <?= show_flash() ?>
