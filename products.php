<?php
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Category.php';

$pageTitle = 'Products';

$keyword    = trim($_GET['keyword'] ?? '');
$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
$sortBy     = $_GET['sort_by'] ?? 'product_name';
$sortDir    = $_GET['sort_dir'] ?? 'ASC';

$products   = Product::search($keyword, $categoryId, $sortBy, $sortDir, 200);
$categories = Category::findAll();

require __DIR__ . '/includes/header.php';
?>
<h1>Products</h1>
<p><a class="btn" href="product_form.php">+ Add New Product</a></p>

<form class="inline" method="get">
    <div class="field">
        <label for="keyword">Search (Name / SKU)</label>
        <input type="text" id="keyword" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="e.g. water">
    </div>
    <div class="field">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c->getCategoryId() ?>" <?= $categoryId === $c->getCategoryId() ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c->getCategoryName()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="sort_by">Sort By</label>
        <select id="sort_by" name="sort_by">
            <option value="product_name" <?= $sortBy === 'product_name' ? 'selected' : '' ?>>Name</option>
            <option value="unit_price" <?= $sortBy === 'unit_price' ? 'selected' : '' ?>>Price</option>
            <option value="stock_quantity" <?= $sortBy === 'stock_quantity' ? 'selected' : '' ?>>Stock</option>
            <option value="sku" <?= $sortBy === 'sku' ? 'selected' : '' ?>>SKU</option>
        </select>
    </div>
    <div class="field">
        <label for="sort_dir">Order</label>
        <select id="sort_dir" name="sort_dir">
            <option value="ASC" <?= $sortDir === 'ASC' ? 'selected' : '' ?>>Ascending</option>
            <option value="DESC" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>Descending</option>
        </select>
    </div>
    <button class="btn" type="submit">Apply</button>
    <a class="btn btn-secondary" href="products.php">Reset</a>
</form>

<div class="card">
    <table>
        <thead>
            <tr><th>SKU</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
            <tr><td colspan="6">No products found.</td></tr>
        <?php else: foreach ($products as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p->getSku()) ?></td>
                <td><?= htmlspecialchars($p->getProductName()) ?></td>
                <td><?= htmlspecialchars($p->getCategoryName() ?? '') ?></td>
                <td>₱<?= number_format($p->getUnitPrice(), 2) ?></td>
                <td>
                    <?= $p->getStockQuantity() ?>
                    <?php if ($p->isLowStock()): ?>
                        <span class="badge badge-low">Low</span>
                    <?php else: ?>
                        <span class="badge badge-ok">OK</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a class="btn btn-sm" href="product_form.php?id=<?= $p->getProductId() ?>">Edit</a>
                    <a class="btn btn-sm btn-danger" href="product_delete.php?id=<?= $p->getProductId() ?>"
                       onclick="return confirm('Delete this product? This cannot be undone.');">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
