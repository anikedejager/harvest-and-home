<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$product = $id ? get_product($id, false) : null;
if ($id && !$product) {
    flash('error', 'Product not found.');
    redirect('products.php');
}
$categories = get_categories();
$form = $product ?: ['product_name' => '', 'category_id' => '', 'description' => '', 'price' => '', 'stock_quantity' => '0', 'product_status' => 'active', 'product_image' => null];
$errors = [];

if (is_post()) {
    check_csrf();
    $form['product_name'] = post('product_name');
    $form['category_id'] = (int)post('category_id');
    $form['description'] = post('description');
    $form['price'] = post('price');
    $form['stock_quantity'] = post('stock_quantity');
    $form['product_status'] = post('product_status') === 'inactive' ? 'inactive' : 'active';

    if (mb_strlen($form['product_name']) < 2 || mb_strlen($form['product_name']) > 150) $errors[] = 'Enter a product name (2 to 150 characters).';
    if (!in_array($form['category_id'], array_map('intval', array_column($categories, 'category_id')), true)) $errors[] = 'Choose a category.';
    if (mb_strlen($form['description']) < 5) $errors[] = 'Enter a short description.';
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $form['price']) || (float)$form['price'] <= 0) $errors[] = 'Enter a price greater than 0, for example 45.00.';
    if (!preg_match('/^\d+$/', $form['stock_quantity'])) $errors[] = 'Stock must be a whole number (0 or more).';

    // Optional image upload: only real JPG, PNG or WEBP images under 2 MB
    $newImage = null;
    if (!empty($_FILES['product_image']['name'])) {
        $file = $_FILES['product_image'];
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $file['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The image could not be uploaded. Try a smaller file.';
        } elseif (!isset($types[$mime]) || !getimagesize($file['tmp_name'])) {
            $errors[] = 'The image must be a JPG, PNG or WEBP file.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'The image must be smaller than 2 MB.';
        } else {
            $newImage = 'assets/uploads/' . bin2hex(random_bytes(8)) . '.' . $types[$mime];
        }
    }

    if (!$errors) {
        if ($newImage && !move_uploaded_file($_FILES['product_image']['tmp_name'], __DIR__ . '/../' . $newImage)) {
            $errors[] = 'The image could not be saved on the server.';
            $newImage = null;
        }
    }

    if (!$errors) {
        $image = $newImage ?? ($product['product_image'] ?? null);
        if ($product) {
            $stmt = db()->prepare('UPDATE products SET category_id = ?, product_name = ?, description = ?, price = ?, stock_quantity = ?, product_image = ?, product_status = ?
                                   WHERE product_id = ?');
            $stmt->execute([$form['category_id'], $form['product_name'], $form['description'], $form['price'], (int)$form['stock_quantity'], $image, $form['product_status'], $id]);
            if ($newImage && $product['product_image'] && str_starts_with($product['product_image'], 'assets/uploads/')) {
                @unlink(__DIR__ . '/../' . $product['product_image']);
            }
            flash('success', 'The product was updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO products (category_id, product_name, description, price, stock_quantity, product_image, product_status)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$form['category_id'], $form['product_name'], $form['description'], $form['price'], (int)$form['stock_quantity'], $image, $form['product_status']]);
            flash('success', 'The product was added.');
        }
        redirect('products.php');
    }
}

$pageTitle = $product ? 'Edit Product' : 'Add Product';
$adminActive = 'products.php';
require 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="products.php">Products</a><?= icon('chevron-right') ?>
    <span><?= $pageTitle ?></span>
</nav>
<h1 class="mb-3"><?= $pageTitle ?></h1>
<form class="card" method="post" action="product_form.php" enctype="multipart/form-data" data-validate style="max-width:760px">
    <?= csrf_field() ?>
    <?php if ($product): ?><input type="hidden" name="product_id" value="<?= $id ?>"><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="f2">
        <div>
            <label for="product_name">Product name</label>
            <input id="product_name" name="product_name" value="<?= h($form['product_name']) ?>" maxlength="150" required>
        </div>
        <div>
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Choose a category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>" <?= (int)$form['category_id'] === (int)$c['category_id'] ? 'selected' : '' ?>><?= h($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="price">Normal price (R)</label>
            <input id="price" name="price" value="<?= h($form['price']) ?>" inputmode="decimal" data-rule="money" required>
        </div>
        <div>
            <label for="stock_quantity">Stock quantity</label>
            <input id="stock_quantity" name="stock_quantity" value="<?= h($form['stock_quantity']) ?>" inputmode="numeric" data-rule="whole" required>
        </div>
        <div>
            <label for="product_status">Status</label>
            <select id="product_status" name="product_status">
                <option value="active" <?= $form['product_status'] === 'active' ? 'selected' : '' ?>>Active (shown in shop)</option>
                <option value="inactive" <?= $form['product_status'] === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option>
            </select>
        </div>
        <div>
            <label for="product_image">Image <span class="mut">(JPG, PNG or WEBP, max 2 MB)</span></label>
            <input id="product_image" type="file" name="product_image" accept="image/jpeg,image/png,image/webp">
            <img id="image-preview" class="thumb" style="width:90px;height:90px" src="<?= $form['product_image'] ? '../' . h($form['product_image']) : '' ?>" alt="Image preview" <?= $form['product_image'] ? '' : 'hidden' ?>>
        </div>
    </div>
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" required><?= h($form['description']) ?></textarea>
    <p class="hint"><?= icon('info-circle') ?> Promotional prices are set on the <a href="promotions.php">Promotions</a> page.</p>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn" type="submit"><?= icon('check2') ?>Save Product</button>
        <a class="btn o" href="products.php">Cancel</a>
    </div>
</form>
<?php require 'includes/footer.php'; ?>
