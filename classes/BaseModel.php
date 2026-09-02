<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Crudable.php';

/**
 * BaseModel
 * Abstract parent shared by all domain entities (Category, Product, Sale).
 * Demonstrates encapsulation (protected properties, PDO hidden from
 * subclasses behind $this->db) and inheritance (concrete models extend
 * this class and implement Crudable).
 */
abstract class BaseModel implements Crudable
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Every concrete model must be able to describe itself as an array,
     * e.g. for rendering in a table row or JSON response.
     */
    abstract public function toArray(): array;
}
