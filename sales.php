<?php
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Sale.php';
require_once __DIR__ . '/classes/InsufficientStockException.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'New Sale';
$products = Product::findAll();
$errors = [];
$receipt = null;

// Keep a working cart in the session so quantities survive re-renders
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_item') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = (int) ($_POST['quantity'] ?? 0);
    if ($productId > 0 && $qty > 0) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
        $_SESSION['flash_success'] = 'Item added to cart.';
    } else {
        $_SESSION['flash_error'] = 'Select a product and a valid quantity before adding.';
    }
    header('Location: sales.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'remove_item') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    unset($_SESSION['cart'][$productId]);
    header('Location: sales.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'clear_cart') {
    $_SESSION['cart'] = [];
    header('Location: sales.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'checkout') {
    $cashierName  = trim($_POST['cashier_name'] ?? '');
    $customerName = trim($_POST['customer_name'] ?? '');
    $amountPaid   = $_POST['amount_paid'] ?? '';

    if ($cashierName === '') {
        $errors[] = 'Cashier name is required.';
    }
    if (empty($_SESSION['cart'])) {
        $errors[] = 'The cart is empty. Add at least one product before checking out.';
    }
    if (!is_numeric($amountPaid) || (float) $amountPaid < 0) {
        $errors[] = 'Amount paid must be a valid non-negative number.';
    }

    if (empty($errors)) {
        try {
            $sale = new Sale($cashierName, $customerName);
            foreach ($_SESSION['cart'] as $productId => $qty) {
                $sale->addCartLine((int) $productId, (int) $qty);
            }
            $receipt = $sale->checkout((float) $amountPaid);
            $_SESSION['cart'] = []; // clear cart after a successful transaction
            $_SESSION['flash_success'] = 'Sale completed! Reference: ' . $receipt['reference_no'];
        } catch (InsufficientStockException $e) {
            // Specific, user-friendly handling of the critical "not enough stock" case
            $errors[] = $e->getMessage();
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'An unexpected error occurred while processing the sale. Please try again.';
        }
    }
}

// Build a lookup + compute current cart contents/total for display
$productsById = [];
foreach ($products as $p) {
    $productsById[$p->getProductId()] = $p;
}
$cartTotal = 0.0;

require __DIR__ . '/includes/header.php';
?>
<h1>New Sale</h1>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card">
    <h2>1. Add Products to Cart</h2>
    <form class="inline" method="post" action="sales.php">
        <input type="hidden" name="action" value="add_item">
        <div class="field">
            <label for="product_id">Product</label>
            <select id="product_id" name="product_id" required>
                <option value="">-- Select product --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p->getProductId() ?>">
                        <?= htmlspecialchars($p->getProductName()) ?> (₱<?= number_format($p->getUnitPrice(), 2) ?>, <?= $p->getStockQuantity() ?> in stock)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
        </div>
        <button type="submit" class="btn">Add to Cart</button>
    </form>
</div>

<div class="card">
    <h2>2. Cart</h2>
    <table class="cart-table">
        <thead><tr><th>Product</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($_SESSION['cart'])): ?>
            <tr><td colspan="5">Cart is empty. Add products above.</td></tr>
        <?php else: foreach ($_SESSION['cart'] as $pid => $qty):
            $p = $productsById[$pid] ?? null;
            if (!$p) continue;
            $subtotal = $p->getUnitPrice() * $qty;
            $cartTotal += $subtotal;
        ?>
            <tr>
                <td><?= htmlspecialchars($p->getProductName()) ?></td>
                <td>₱<?= number_format($p->getUnitPrice(), 2) ?></td>
                <td><?= (int) $qty ?></td>
                <td>₱<?= number_format($subtotal, 2) ?></td>
                <td>
                    <form method="post" action="sales.php" style="display:inline">
                        <input type="hidden" name="action" value="remove_item">
                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($_SESSION['cart'])): ?>
        <tfoot>
            <tr class="total-row"><td colspan="3" style="text-align:right">Total</td><td colspan="2">₱<?= number_format($cartTotal, 2) ?></td></tr>
        </tfoot>
        <?php endif; ?>
    </table>
    <?php if (!empty($_SESSION['cart'])): ?>
        <form method="post" action="sales.php" style="margin-top:10px">
            <input type="hidden" name="action" value="clear_cart">
            <button type="submit" class="btn btn-secondary btn-sm">Clear Cart</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>3. Checkout</h2>
    <form method="post" action="sales.php">
        <input type="hidden" name="action" value="checkout">
        <div class="field" style="margin-bottom:12px;">
            <label for="cashier_name">Cashier Name</label>
            <input type="text" id="cashier_name" name="cashier_name" required>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="customer_name">Customer Name (optional)</label>
            <input type="text" id="customer_name" name="customer_name" placeholder="Walk-in Customer">
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label for="amount_paid">Amount Paid (₱)</label>
            <input type="number" step="0.01" min="0" id="amount_paid" name="amount_paid" required>
        </div>
        <button type="submit" class="btn">Complete Sale</button>
    </form>
</div>

<?php if ($receipt): ?>
<div class="card">
    <h2>✅ Sale Completed — <?= htmlspecialchars($receipt['reference_no']) ?></h2>
    <p><strong>Total:</strong> ₱<?= number_format($receipt['total_amount'], 2) ?> &nbsp; | &nbsp;
       <strong>Change Due:</strong> ₱<?= number_format($receipt['change_due'], 2) ?></p>
    <a class="btn" href="sale_view.php?id=<?= $receipt['sale_id'] ?>">View Full Receipt</a>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
