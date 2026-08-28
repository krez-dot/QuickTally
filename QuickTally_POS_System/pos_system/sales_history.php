<?php
require_once __DIR__ . '/classes/Sale.php';

$pageTitle = 'Sales History';

$keyword  = trim($_GET['keyword'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');
$sortDir  = $_GET['sort_dir'] ?? 'DESC';

$sales = Sale::search($dateFrom, $dateTo, $keyword, $sortDir, 200);

require __DIR__ . '/includes/header.php';
?>
<h1>Sales History</h1>

<form class="inline" method="get">
    <div class="field">
        <label for="keyword">Search (Ref No. / Customer / Cashier)</label>
        <input type="text" id="keyword" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="e.g. SO-2026...">
    </div>
    <div class="field">
        <label for="date_from">From</label>
        <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
    </div>
    <div class="field">
        <label for="date_to">To</label>
        <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
    </div>
    <div class="field">
        <label for="sort_dir">Sort by Date</label>
        <select id="sort_dir" name="sort_dir">
            <option value="DESC" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>Newest first</option>
            <option value="ASC" <?= $sortDir === 'ASC' ? 'selected' : '' ?>>Oldest first</option>
        </select>
    </div>
    <button class="btn" type="submit">Filter</button>
    <a class="btn btn-secondary" href="sales_history.php">Reset</a>
</form>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Reference No.</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Cashier</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Change</th>
                <th>View</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($sales)): ?>
            <tr><td colspan="8">No sales transactions found.</td></tr>
        <?php else: foreach ($sales as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['reference_no']) ?></td>
                <td><?= htmlspecialchars($s['sale_date']) ?></td>
                <td><?= htmlspecialchars($s['customer_name']) ?></td>
                <td><?= htmlspecialchars($s['cashier_name']) ?></td>
                <td>₱<?= number_format((float) $s['total_amount'], 2) ?></td>
                <td>₱<?= number_format((float) $s['amount_paid'], 2) ?></td>
                <td>₱<?= number_format((float) $s['change_due'], 2) ?></td>
                <td><a class="btn btn-sm" href="sale_view.php?id=<?= (int) $s['sale_id'] ?>">Receipt</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
