<?php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/InsufficientStockException.php';

class Sale extends BaseModel
{
    private ?int $saleId;
    private string $referenceNo;
    private string $cashierName;
    private string $customerName;
    private float $totalAmount = 0.0;
    private float $amountPaid = 0.0;
    private float $changeDue = 0.0;
    private ?string $saleDate = null;

    /** @var array<int, array{product_id:int, quantity:int}> raw cart lines before checkout */
    private array $cartLines = [];

    public function __construct(string $cashierName = '', string $customerName = 'Walk-in Customer')
    {
        parent::__construct();
        $this->saleId = null;
        $this->cashierName = $cashierName;
        $this->customerName = $customerName ?: 'Walk-in Customer';
        $this->referenceNo = $this->generateReferenceNo();
    }

    private function generateReferenceNo(): string
    {
        return 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    public function addCartLine(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }
        $this->cartLines[] = ['product_id' => $productId, 'quantity' => $quantity];
    }

    public function getReferenceNo(): string { return $this->referenceNo; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getChangeDue(): float { return $this->changeDue; }
    public function getSaleId(): ?int { return $this->saleId; }

    /**
     * Validate stock, compute totals, persist the sale + line items,
     * and deduct stock — all inside a single DB transaction.
     *
     * @throws InsufficientStockException if any line exceeds available stock
     * @throws InvalidArgumentException   if the cart is empty or payment is short
     */
    public function checkout(float $amountPaid): array
    {
        if (empty($this->cartLines)) {
            throw new InvalidArgumentException('Cannot checkout an empty cart.');
        }

        $this->db->beginTransaction();
        try {
            $lineItems = [];
            $total = 0.0;

            // 1. Validate stock and compute totals for every line
            foreach ($this->cartLines as $line) {
                $product = Product::findById($line['product_id']);
                if ($product === null) {
                    throw new InvalidArgumentException("Product #{$line['product_id']} does not exist.");
                }
                if ($product->getStockQuantity() < $line['quantity']) {
                    throw new InsufficientStockException(
                        $product->getProductName(),
                        $line['quantity'],
                        $product->getStockQuantity()
                    );
                }
                $subtotal = $product->getUnitPrice() * $line['quantity'];
                $total += $subtotal;
                $lineItems[] = [
                    'product'  => $product,
                    'quantity' => $line['quantity'],
                    'price'    => $product->getUnitPrice(),
                    'subtotal' => $subtotal,
                ];
            }

            if ($amountPaid < $total) {
                throw new InvalidArgumentException(
                    'Amount paid (₱' . number_format($amountPaid, 2) . ') is less than the total due (₱' . number_format($total, 2) . ').'
                );
            }

            $this->totalAmount = $total;
            $this->amountPaid = $amountPaid;
            $this->changeDue = round($amountPaid - $total, 2);

            // 2. Insert the sale header
            $stmt = $this->db->prepare(
                'INSERT INTO sales (reference_no, cashier_name, customer_name, total_amount, amount_paid, change_due)
                 VALUES (:ref, :cashier, :customer, :total, :paid, :change)'
            );
            $stmt->execute([
                ':ref'      => $this->referenceNo,
                ':cashier'  => $this->cashierName,
                ':customer' => $this->customerName,
                ':total'    => $this->totalAmount,
                ':paid'     => $this->amountPaid,
                ':change'   => $this->changeDue,
            ]);
            $this->saleId = (int) $this->db->lastInsertId();

            // 3. Insert each line item and deduct stock
            $itemStmt = $this->db->prepare(
                'INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
                 VALUES (:sale_id, :product_id, :qty, :price, :subtotal)'
            );
            foreach ($lineItems as $item) {
                $itemStmt->execute([
                    ':sale_id'    => $this->saleId,
                    ':product_id' => $item['product']->getProductId(),
                    ':qty'        => $item['quantity'],
                    ':price'      => $item['price'],
                    ':subtotal'   => $item['subtotal'],
                ]);
                $item['product']->adjustStock(-$item['quantity']);
            }

            $this->db->commit();

            return [
                'sale_id'      => $this->saleId,
                'reference_no' => $this->referenceNo,
                'total_amount' => $this->totalAmount,
                'change_due'   => $this->changeDue,
                'items'        => $lineItems,
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e; // re-thrown so the calling page can display a friendly message
        }
    }

    // ---- CRUD (sales are normally created via checkout(), but Crudable is still honored) ----
    public function create(): bool
    {
        throw new BadMethodCallException('Use checkout() to create a sale with its line items.');
    }

    public function update(): bool
    {
        throw new BadMethodCallException('Sales transactions are immutable once completed.');
    }

    public function delete(): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sales WHERE sale_id = :id');
        return $stmt->execute([':id' => $this->saleId]);
    }

    /**
     * Search sales history with optional date range, sorted by date.
     */
    public static function search(string $dateFrom = '', string $dateTo = '', string $keyword = '', string $sortDir = 'DESC', int $limit = 100): array
    {
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $sql = 'SELECT * FROM sales WHERE 1=1';
        $params = [];

        if ($dateFrom !== '') {
            $sql .= ' AND sale_date >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $sql .= ' AND sale_date <= :date_to';
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }
        if ($keyword !== '') {
            // Each LIKE clause needs its own placeholder — PDO with real prepared
            // statements (ATTR_EMULATE_PREPARES = false) does not allow reusing
            // the same named placeholder more than once in a query.
            $sql .= ' AND (reference_no LIKE :keyword1 OR customer_name LIKE :keyword2 OR cashier_name LIKE :keyword3)';
            $params[':keyword1'] = "%{$keyword}%";
            $params[':keyword2'] = "%{$keyword}%";
            $params[':keyword3'] = "%{$keyword}%";
        }
        $sql .= " ORDER BY sale_date {$sortDir} LIMIT :limit";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findWithItems(int $saleId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM sales WHERE sale_id = :id');
        $stmt->execute([':id' => $saleId]);
        $sale = $stmt->fetch();
        if (!$sale) {
            return null;
        }

        $itemStmt = $db->prepare(
            'SELECT si.*, p.product_name, p.sku FROM sale_items si
              JOIN products p ON p.product_id = si.product_id
             WHERE si.sale_id = :id'
        );
        $itemStmt->execute([':id' => $saleId]);
        $sale['items'] = $itemStmt->fetchAll();
        return $sale;
    }

    /** Dashboard helper: today's total sales amount */
    public static function todayTotal(): float
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE DATE(sale_date) = CURDATE()');
        return (float) $stmt->fetch()['total'];
    }

    /** Dashboard helper: count of transactions today */
    public static function todayCount(): int
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT COUNT(*) AS cnt FROM sales WHERE DATE(sale_date) = CURDATE()');
        return (int) $stmt->fetch()['cnt'];
    }

    /** Dashboard helper: best-selling products (by quantity) */
    public static function topProducts(int $limit = 5): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT p.product_name, SUM(si.quantity) AS total_qty, SUM(si.subtotal) AS total_sales
               FROM sale_items si
               JOIN products p ON p.product_id = si.product_id
              GROUP BY si.product_id
              ORDER BY total_qty DESC
              LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function toArray(): array
    {
        return [
            'sale_id'      => $this->saleId,
            'reference_no' => $this->referenceNo,
            'cashier_name' => $this->cashierName,
            'customer_name'=> $this->customerName,
            'total_amount' => $this->totalAmount,
            'amount_paid'  => $this->amountPaid,
            'change_due'   => $this->changeDue,
        ];
    }
}
