<?php
require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel
{
    private ?int $categoryId;
    private string $categoryName;
    private ?string $description;

    public function __construct(?int $categoryId = null, string $categoryName = '', ?string $description = null)
    {
        parent::__construct();
        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
        $this->description = $description;
    }

    // ---- Getters / Setters (encapsulation) ----
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $name): void
    {
        $this->categoryName = trim($name);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    // ---- CRUD ----
    public function create(): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (category_name, description) VALUES (:name, :description)'
        );
        $result = $stmt->execute([
            ':name'        => $this->categoryName,
            ':description' => $this->description,
        ]);
        if ($result) {
            $this->categoryId = (int) $this->db->lastInsertId();
        }
        return $result;
    }

    public function update(): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET category_name = :name, description = :description WHERE category_id = :id'
        );
        return $stmt->execute([
            ':name'        => $this->categoryName,
            ':description' => $this->description,
            ':id'          => $this->categoryId,
        ]);
    }

    public function delete(): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE category_id = :id');
        return $stmt->execute([':id' => $this->categoryId]);
    }

    public static function findAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT * FROM categories ORDER BY category_name ASC');
        $rows = $stmt->fetchAll();

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = new self((int) $row['category_id'], $row['category_name'], $row['description']);
        }
        return $categories;
    }

    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM categories WHERE category_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return new self((int) $row['category_id'], $row['category_name'], $row['description']);
    }

    public function toArray(): array
    {
        return [
            'category_id'   => $this->categoryId,
            'category_name' => $this->categoryName,
            'description'   => $this->description,
        ];
    }
}
