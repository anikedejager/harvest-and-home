<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['promotion_id'] ?? 0);
$promo = ['promotion_name' => '', 'promotion_status' => 'inactive'];
$prices = []; // product_id => promo_price

if ($id) {
    $stmt = db()->prepare('SELECT * FROM promotions WHERE promotion_id = ?');
    $stmt->execute([$id]);
    $promo = $stmt->fetch();
    if (!$promo) {
        flash('error', 'Promotion not found.');
        redirect('promotions.php');
    }
    $stmt = db()->prepare('SELECT product_id, promo_price FROM promotion_items WHERE promotion_id = ?');
    $stmt->execute([$id]);
    $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$products = db()->query("SELECT product_id, product_name, price FROM products ORDER BY product_name")->fetchAll();
$errors = [];

if (is_post()) {
    check_csrf();
    $promo['promotion_name'] = post('promotion_name');
    $promo['promotion_status'] = post('promotion_status') === 'active' ? 'active' : 'inactive';
    $prices = [];
    if (mb_strlen($promo['promotion_name']) < 2 || mb_strlen($promo['promotion_name']) > 100) {
        $errors[] = 'Enter a promotion name (2 to 100 characters).';
    }
    foreach ($products as $p) {
        $raw = trim((string)($_POST['promo'][$p['product_id']] ?? ''));
        if ($raw === '') continue;
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $raw) || (float)$raw <= 0) {
            $errors[] = $p['product_name'] . ': enter a valid price.';
        } elseif ((float)$raw >= (float)$p['price']) {
            $errors[] = $p['product_name'] . ': the promotional price must be lower than the normal price of ' . money($p['price']) . '.';
        }
        $prices[$p['product_id']] = $raw;
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        if ($id) {
            $pdo->prepare('UPDATE promotions SET promotion_name = ?, promotion_status = ? WHERE promotion_id = ?')
                ->execute([$promo['promotion_name'], $promo['promotion_status'], $id]);
            $pdo->prepare('DELETE FROM promotion_items WHERE promotion_id = ?')->execute([$id]);
        } else {
            $pdo->prepare('INSERT INTO promotions (promotion_name, promotion_status) VALUES (?, ?)')
                ->execute([$promo['promotion_name'], $promo['promotion_status']]);
            $id = (int)$pdo->lastInsertId();
        }
        $add = $pdo->prepare('INSERT INTO promotion_items (product_id, promotion_id, promo_price) VALUES (?, ?, ?)');
        foreach ($prices as $productId => $price) {
            $add->execute([$productId, $id, $price]);
        }
        $pdo->commit();
        flash('success', 'The promotion was saved.');
        redirect('promotions.php');
    }
}

$pageTitle = $id ? 'Edit Promotion' : 'New Promotion';
$adminActive = 'promotions.php';
require 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="promotions.php">Promotions</a><?= icon('chevron-right') ?>
    <span><?= $pageTitle ?></span>
</nav>
<h1 class="mb-3"><?= $pageTitle ?></h1>
<form class="card" method="post" action="promotion_form.php" data-validate style="max-width:760px">
    <?= csrf_field() ?>
    <?php if ($id): ?><input type="hidden" name="promotion_id" value="<?= $id ?>"><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="f2">
        <div>
            <label for="promotion_name">Promotion name</label>
            <input id="promotion_name" name="promotion_name" value="<?= h($promo['promotion_name']) ?>" maxlength="100" required>
        </div>
        <div>
            <label for="promotion_status">Status</label>
            <select id="promotion_status" name="promotion_status">
                <option value="inactive" <?= $promo['promotion_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="active" <?= $promo['promotion_status'] === 'active' ? 'selected' : '' ?>>Active</option>
            </select>
        </div>
    </div>
    <h2 class="card-title mt-2 mb-1"><?= icon('tag') ?>Promo price per product (R)</h2>
    <p class="hint mb-2">Leave blank if the product is not in this promotion.</p>
    <?php foreach ($products as $p): $pid = (int)$p['product_id']; ?>
        <div class="promo-row">
            <label for="pp<?= $pid ?>"><?= h($p['product_name']) ?> <span class="mut">(normal <?= money($p['price']) ?>)</span></label>
            <input id="pp<?= $pid ?>" name="promo[<?= $pid ?>]" value="<?= h($prices[$pid] ?? '') ?>" inputmode="decimal" data-rule="money" placeholder="0.00">
        </div>
    <?php endforeach; ?>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn" type="submit"><?= icon('check2') ?>Save Promotion</button>
        <a class="btn o" href="promotions.php">Cancel</a>
    </div>
</form>
<?php require 'includes/footer.php'; ?>
