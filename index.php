<?php
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Sale.php';

$pageTitle = 'Dashboard';

$todayTotal   = Sale::todayTotal();
$todayCount   = Sale::todayCount();
$lowStock     = Product::lowStock();
$topProducts  = Sale::topProducts(5);
$allProducts  = Product::findAll();
$totalStockUnits = array_sum(array_map(fn($p) => $p->getStockQuantity(), $allProducts));

require __DIR__ . '/includes/header.php';
?>
<h1>Dashboard</h1>
<p>Quick summary of today's sales and current inventory status.</p>

<div class="stat-grid">
    <div class="stat-card">
        <div class="label">Today's Sales</div>
        <div class="value">₱<?= number_format($todayTotal, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Transactions Today</div>
        <div class="value"><?= (int) $todayCount ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Total Stock on Hand</div>
        <div class="value"><?= (int) $totalStockUnits ?> units</div>
    </div>
    <div class="stat-card <?= count($lowStock) > 0 ? 'warn' : '' ?>">
        <div class="label">Low-Stock Products</div>
        <div class="value"><?= count($lowStock) ?></div>
    </div>
</div>

<div class="card">
    <h2>⚠️ Low-Stock Alerts</h2>
    <?php if (empty($lowStock)): ?>
        <p>All products are above their reorder level.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Stock</th><th>Reorder Level</th></tr></thead>
            <tbody>
            <?php foreach ($lowStock as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p->getSku()) ?></td>
                    <td><?= htmlspecialchars($p->getProductName()) ?></td>
                    <td><?= htmlspecialchars($p->getCategoryName() ?? '') ?></td>
                    <td><span class="badge badge-low"><?= $p->getStockQuantity() ?></span></td>
                    <td><?= $p->getReorderLevel() ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>🏆 Top-Selling Products</h2>
    <?php if (empty($topProducts)): ?>
        <p>No sales recorded yet.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Product</th><th>Units Sold</th><th>Total Sales</th></tr></thead>
            <tbody>
            <?php foreach ($topProducts as $tp): ?>
                <tr>
                    <td><?= htmlspecialchars($tp['product_name']) ?></td>
                    <td><?= (int) $tp['total_qty'] ?></td>
                    <td>₱<?= number_format((float) $tp['total_sales'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
