<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$editId = (int)($_GET['edit'] ?? 0);
$errors = [];

if (is_post()) {
    check_csrf();
    $action = post('action');
    $id = (int)post('category_id');

    if ($action === 'delete') {
        $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            flash('error', 'This category still has products. Move or delete them first.');
        } else {
            db()->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$id]);
            flash('success', 'The category was deleted.');
        }
        redirect('categories.php');
    }

    $name = post('category_name');
    $desc = post('description');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Enter a category name (2 to 100 characters).';
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE category_name = ? AND category_id <> ?');
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'A category with this name already exists.';
    }
    if (!$errors) {
        if ($action === 'update' && $id) {
            db()->prepare('UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?')->execute([$name, $desc ?: null, $id]);
            flash('success', 'The category was updated.');
        } else {
            db()->prepare('INSERT INTO categories (category_name, description) VALUES (?, ?)')->execute([$name, $desc ?: null]);
            flash('success', 'The category was added.');
        }
        redirect('categories.php');
    }
    $editId = $action === 'update' ? $id : 0;
}

$categories = db()->query('SELECT c.*, COUNT(p.product_id) AS products FROM categories c
                           LEFT JOIN products p ON p.category_id = c.category_id
                           GROUP BY c.category_id ORDER BY c.category_id')->fetchAll();
$editing = null;
foreach ($categories as $c) if ((int)$c['category_id'] === $editId) $editing = $c;

$pageTitle = 'Categories';
$adminActive = 'categories.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Categories</h1>
    <p>Group products so customers can filter the shop.</p>
</div>
<div class="two">
    <div class="tw">
        <table>
            <thead><tr><th>Category</th><th>Description</th><th>Products</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><span class="prod-cell"><span class="mini"><?= icon(category_icon($c['category_name'])) ?></span><?= h($c['category_name']) ?></span></td>
                    <td class="mut"><?= h($c['description']) ?></td>
                    <td><?= (int)$c['products'] ?></td>
                    <td class="actions">
                        <a class="act" href="categories.php?edit=<?= (int)$c['category_id'] ?>"><?= icon('pencil-square') ?>Edit</a>
                        <form method="post" action="categories.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                            <button class="linkbtn r" type="submit" data-confirm="Delete the <?= h($c['category_name']) ?> category?"><?= icon('trash3') ?>Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form class="card summary" method="post" action="categories.php" data-validate>
        <?= csrf_field() ?>
        <h2 class="card-title"><?= icon($editing ? 'pencil-square' : 'plus-circle') ?><?= $editing ? 'Edit Category' : 'Add Category' ?></h2>
        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
        <input type="hidden" name="category_id" value="<?= $editing ? (int)$editing['category_id'] : 0 ?>">
        <label for="category_name">Category name</label>
        <input id="category_name" name="category_name" value="<?= h($_POST['category_name'] ?? $editing['category_name'] ?? '') ?>" maxlength="100" required>
        <label for="description">Description <span class="mut">(optional)</span></label>
        <textarea id="description" name="description" rows="3"><?= h($_POST['description'] ?? $editing['description'] ?? '') ?></textarea>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn" type="submit"><?= icon($editing ? 'check2' : 'plus-lg') ?><?= $editing ? 'Save' : 'Add' ?></button>
            <?php if ($editing): ?><a class="btn o" href="categories.php">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
