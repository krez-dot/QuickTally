<?php
require_once __DIR__ . '/classes/Sale.php';

$pageTitle = 'Receipt';
$id = (int) ($_GET['id'] ?? 0);
$sale = Sale::findWithItems($id);

require __DIR__ . '/includes/header.php';

if (!$sale) {
    echo '<div class="alert alert-error">Sale not found.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
?>
<h1>Receipt: <?= htmlspecialchars($sale['reference_no']) ?></h1>

<div class="card">
    <p><strong>Date:</strong> <?= htmlspecialchars($sale['sale_date']) ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($sale['customer_name']) ?></p>
    <p><strong>Cashier:</strong> <?= htmlspecialchars($sale['cashier_name']) ?></p>

    <table>
        <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($sale['items'] as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= htmlspecialchars($item['sku']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td>₱<?= number_format((float) $item['unit_price'], 2) ?></td>
                <td>₱<?= number_format((float) $item['subtotal'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="4" style="text-align:right">Total</td>
            <td>₱<?= number_format((float) $sale['total_amount'], 2) ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:right">Amount Paid</td>
            <td>₱<?= number_format((float) $sale['amount_paid'], 2) ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:right">Change</td>
            <td>₱<?= number_format((float) $sale['change_due'], 2) ?></td>
        </tr>
        </tbody>
    </table>
    <p><a class="btn btn-secondary" href="sales_history.php">&larr; Back to Sales History</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
