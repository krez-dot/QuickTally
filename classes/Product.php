<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel
{
    private ?int $productId;
    private int $categoryId;
    private string $sku;
    private string $productName;
    private ?string $description;
    private float $unitPrice;
    private int $stockQuantity;
    private int $reorderLevel;

    // Populated only when the product is loaded via a JOIN with categories
    private ?string $categoryName = null;

    public function __construct(
        ?int $productId = null,
        int $categoryId = 0,
        string $sku = '',
        string $productName = '',
        ?string $description = null,
        float $unitPrice = 0.0,
        int $stockQuantity = 0,
        int $reorderLevel = 10
    ) {
        parent::__construct();
        $this->productId     = $productId;
        $this->categoryId    = $categoryId;
        $this->sku           = $sku;
        $this->productName   = $productName;
        $this->description   = $description;
        $this->unitPrice     = $unitPrice;
        $this->stockQuantity = $stockQuantity;
        $this->reorderLevel  = $reorderLevel;
    }

    // ---- Getters / Setters ----
    public function getProductId(): ?int { return $this->productId; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function setCategoryId(int $categoryId): void { $this->categoryId = $categoryId; }
    public function getSku(): string { return $this->sku; }
    public function setSku(string $sku): void { $this->sku = trim($sku); }
    public function getProductName(): string { return $this->productName; }
    public function setProductName(string $name): void { $this->productName = trim($name); }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function setUnitPrice(float $price): void { $this->unitPrice = $price; }
    public function getStockQuantity(): int { return $this->stockQuantity; }
    public function setStockQuantity(int $qty): void { $this->stockQuantity = $qty; }
    public function getReorderLevel(): int { return $this->reorderLevel; }
    public function setReorderLevel(int $level): void { $this->reorderLevel = $level; }
    public function getCategoryName(): ?string { return $this->categoryName; }

    public function isLowStock(): bool
    {
        return $this->stockQuantity <= $this->reorderLevel;
    }

    // ---- CRUD ----
    public function create(): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (category_id, sku, product_name, description, unit_price, stock_quantity, reorder_level)
             VALUES (:category_id, :sku, :name, :description, :price, :stock, :reorder)'
        );
        $result = $stmt->execute([
            ':category_id' => $this->categoryId,
            ':sku'         => $this->sku,
            ':name'        => $this->productName,
            ':description' => $this->description,
            ':price'       => $this->unitPrice,
            ':stock'       => $this->stockQuantity,
            ':reorder'     => $this->reorderLevel,
        ]);
        if ($result) {
            $this->productId = (int) $this->db->lastInsertId();
        }
        return $result;
    }

    public function update(): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products
                SET category_id = :category_id, sku = :sku, product_name = :name,
                    description = :description, unit_price = :price,
                    stock_quantity = :stock, reorder_level = :reorder
              WHERE product_id = :id'
        );
        return $stmt->execute([
            ':category_id' => $this->categoryId,
            ':sku'         => $this->sku,
            ':name'        => $this->productName,
            ':description' => $this->description,
            ':price'       => $this->unitPrice,
            ':stock'       => $this->stockQuantity,
            ':reorder'     => $this->reorderLevel,
            ':id'          => $this->productId,
        ]);
    }

    public function delete(): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE product_id = :id');
        return $stmt->execute([':id' => $this->productId]);
    }

    /**
     * Adjust stock by a signed delta (negative = deduct, positive = restock).
     * Used by Sale::checkout() when a transaction is completed.
     */
    public function adjustStock(int $delta): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products SET stock_quantity = stock_quantity + :delta WHERE product_id = :id'
        );
        $result = $stmt->execute([':delta' => $delta, ':id' => $this->productId]);
        if ($result) {
            $this->stockQuantity += $delta;
        }
        return $result;
    }

    /**
     * Search / filter / sort listing used by products.php
     */
    public static function search(string $keyword = '', ?int $categoryId = null, string $sortBy = 'product_name', string $sortDir = 'ASC', int $limit = 50): array
    {
        $allowedSort = ['product_name', 'unit_price', 'stock_quantity', 'sku'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'product_name';
        }
        $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = 'SELECT p.*, c.category_name
                  FROM products p
                  JOIN categories c ON c.category_id = p.category_id
                 WHERE (p.product_name LIKE :keyword1 OR p.sku LIKE :keyword2)';
        // Note: PDO with real prepared statements (ATTR_EMULATE_PREPARES = false)
        // does not allow the same named placeholder to be reused more than once
        // in a query, so each LIKE clause gets its own placeholder bound to the
        // same value.
        $params = [':keyword1' => "%{$keyword}%", ':keyword2' => "%{$keyword}%"];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        $sql .= " ORDER BY {$sortBy} {$sortDir} LIMIT :limit";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $products[] = self::hydrate($row);
        }
        return $products;
    }

    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT p.*, c.category_name FROM products p
              JOIN categories c ON c.category_id = p.category_id
             WHERE p.product_id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findAll(): array
    {
        return self::search('', null, 'product_name', 'ASC', 1000);
    }

    public static function lowStock(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query(
            'SELECT p.*, c.category_name FROM products p
              JOIN categories c ON c.category_id = p.category_id
             WHERE p.stock_quantity <= p.reorder_level
             ORDER BY p.stock_quantity ASC'
        );
        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $products[] = self::hydrate($row);
        }
        return $products;
    }

    private static function hydrate(array $row): self
    {
        $product = new self(
            (int) $row['product_id'],
            (int) $row['category_id'],
            $row['sku'],
            $row['product_name'],
            $row['description'],
            (float) $row['unit_price'],
            (int) $row['stock_quantity'],
            (int) $row['reorder_level']
        );
        if (isset($row['category_name'])) {
            $product->categoryName = $row['category_name'];
        }
        return $product;
    }

    public function toArray(): array
    {
        return [
            'product_id'     => $this->productId,
            'category_id'    => $this->categoryId,
            'category_name'  => $this->categoryName,
            'sku'            => $this->sku,
            'product_name'   => $this->productName,
            'description'    => $this->description,
            'unit_price'     => $this->unitPrice,
            'stock_quantity' => $this->stockQuantity,
            'reorder_level'  => $this->reorderLevel,
        ];
    }
}
