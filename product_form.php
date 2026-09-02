<?php
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Category.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$isEdit = $id !== null && $id > 0;
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';

$categories = Category::findAll();
$errors = [];

// Default form values
$form = [
    'sku' => '', 'product_name' => '', 'description' => '',
    'category_id' => $categories[0]->getCategoryId() ?? 0,
    'unit_price' => '', 'stock_quantity' => '', 'reorder_level' => 10,
];

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $existing = Product::findById($id);
    if (!$existing) {
        $_SESSION['flash_error'] = 'Product not found.';
        header('Location: products.php');
        exit;
    }
    $form = [
        'sku' => $existing->getSku(),
        'product_name' => $existing->getProductName(),
        'description' => $existing->getDescription() ?? '',
        'category_id' => $existing->getCategoryId(),
        'unit_price' => $existing->getUnitPrice(),
        'stock_quantity' => $existing->getStockQuantity(),
        'reorder_level' => $existing->getReorderLevel(),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['sku']            = trim($_POST['sku'] ?? '');
    $form['product_name']   = trim($_POST['product_name'] ?? '');
    $form['description']    = trim($_POST['description'] ?? '');
    $form['category_id']    = (int) ($_POST['category_id'] ?? 0);
    $form['unit_price']     = $_POST['unit_price'] ?? '';
    $form['stock_quantity'] = $_POST['stock_quantity'] ?? '';
    $form['reorder_level']  = $_POST['reorder_level'] ?? '';

    // ---- Server-side validation ----
    if ($form['sku'] === '') {
        $errors[] = 'SKU is required.';
    }
    if ($form['product_name'] === '') {
        $errors[] = 'Product name is required.';
    }
    if ($form['category_id'] <= 0) {
        $errors[] = 'Please select a category.';
    }
    if (!is_numeric($form['unit_price']) || (float) $form['unit_price'] < 0) {
        $errors[] = 'Unit price must be a non-negative number.';
    }
    if (!is_numeric($form['stock_quantity']) || (int) $form['stock_quantity'] < 0) {
        $errors[] = 'Stock quantity must be a non-negative whole number.';
    }
    if (!is_numeric($form['reorder_level']) || (int) $form['reorder_level'] < 0) {
        $errors[] = 'Reorder level must be a non-negative whole number.';
    }

    if (empty($errors)) {
        try {
            $product = new Product(
                $isEdit ? $id : null,
                (int) $form['category_id'],
                $form['sku'],
                $form['product_name'],
                $form['description'] ?: null,
                (float) $form['unit_price'],
                (int) $form['stock_quantity'],
                (int) $form['reorder_level']
            );

            $ok = $isEdit ? $product->update() : $product->create();

            if ($ok) {
                $_SESSION['flash_success'] = $isEdit ? 'Product updated successfully.' : 'Product added successfully.';
                header('Location: products.php');
                exit;
            }
            $errors[] = 'Could not save the product. Please try again.';
        } catch (PDOException $e) {
            // Likely a duplicate SKU (UNIQUE constraint) — user-friendly error handling
            if ($e->getCode() === '23000') {
                $errors[] = 'That SKU is already used by another product. Please use a unique SKU.';
            } else {
                $errors[] = 'A database error occurred while saving the product.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<h1><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h1>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card">
    <form method="post" action="product_form.php">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <div class="field" style="margin-bottom:12px;">
            <label for="sku">SKU</label>
            <input type="text" id="sku" name="sku" value="<?= htmlspecialchars($form['sku']) ?>" required>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($form['product_name']) ?>" required>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($form['description']) ?></textarea>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c->getCategoryId() ?>" <?= (int) $form['category_id'] === $c->getCategoryId() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c->getCategoryName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="unit_price">Unit Price (₱)</label>
            <input type="number" step="0.01" min="0" id="unit_price" name="unit_price" value="<?= htmlspecialchars((string) $form['unit_price']) ?>" required>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" step="1" min="0" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars((string) $form['stock_quantity']) ?>" required>
        </div>
        <div class="field" style="margin-bottom:16px;">
            <label for="reorder_level">Reorder Level</label>
            <input type="number" step="1" min="0" id="reorder_level" name="reorder_level" value="<?= htmlspecialchars((string) $form['reorder_level']) ?>" required>
        </div>

        <button type="submit" class="btn"><?= $isEdit ? 'Update Product' : 'Save Product' ?></button>
        <a href="products.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
