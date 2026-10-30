<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    check_csrf();
    $id = (int)post('promotion_id');
    if (post('action') === 'toggle') {
        db()->prepare("UPDATE promotions SET promotion_status = IF(promotion_status = 'active', 'inactive', 'active') WHERE promotion_id = ?")->execute([$id]);
        flash('success', 'Promotion status changed.');
    } elseif (post('action') === 'delete') {
        db()->prepare('DELETE FROM promotions WHERE promotion_id = ?')->execute([$id]);
        flash('success', 'Promotion deleted.');
    }
    redirect('promotions.php');
}

$promotions = db()->query('SELECT * FROM promotions ORDER BY promotion_id DESC')->fetchAll();
$items = db()->query('SELECT pi.*, p.product_name, p.price FROM promotion_items pi
                      JOIN products p ON p.product_id = pi.product_id ORDER BY p.product_name')->fetchAll();
$byPromo = [];
foreach ($items as $i) $byPromo[$i['promotion_id']][] = $i;

$pageTitle = 'Promotions';
$adminActive = 'promotions.php';
require 'includes/header.php';
?>
<div class="toolbar">
    <h1>Promotions</h1>
    <a class="btn" href="promotion_form.php"><?= icon('plus-lg') ?>New Promotion</a>
</div>
<div class="notice"><?= icon('info-circle') ?><span>While a promotion is active, its products show the promotional price in the shop. If a product is in more than one active promotion, the lowest price is used.</span></div>
<div class="tw">
    <table>
        <thead><tr><th>Promotion</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($promotions as $m): $pid = (int)$m['promotion_id']; $on = $m['promotion_status'] === 'active'; ?>
            <tr>
                <td><b><?= h($m['promotion_name']) ?></b></td>
                <td><?= isset($byPromo[$pid])
                        ? implode('<br>', array_map(fn($i) => h($i['product_name']) . ' (' . money($i['price']) . ' &rarr; ' . money($i['promo_price']) . ')', $byPromo[$pid]))
                        : '<span class="mut">No products yet</span>' ?></td>
                <td><span class="pill <?= $on ? 'p2' : 'p5' ?>"><?= $on ? 'Active' : 'Inactive' ?></span></td>
                <td class="actions">
                    <a class="act" href="promotion_form.php?id=<?= $pid ?>"><?= icon('pencil-square') ?>Edit</a>
                    <form method="post" action="promotions.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="promotion_id" value="<?= $pid ?>">
                        <button class="linkbtn" type="submit"><?= icon($on ? 'toggle-on' : 'toggle-off') ?><?= $on ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                    <form method="post" action="promotions.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="promotion_id" value="<?= $pid ?>">
                        <button class="linkbtn r" type="submit" data-confirm="Delete the <?= h($m['promotion_name']) ?> promotion?"><?= icon('trash3') ?>Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$promotions): ?><tr><td colspan="4" class="mut">No promotions yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>
