<?php

/**
 * InsufficientStockException
 * Custom exception thrown when a sale attempts to sell more units
 * of a product than are currently in stock. Used to demonstrate
 * explicit exception handling around the critical checkout operation.
 */
class InsufficientStockException extends Exception
{
    private string $productName;
    private int $requested;
    private int $available;

    public function __construct(string $productName, int $requested, int $available)
    {
        $this->productName = $productName;
        $this->requested = $requested;
        $this->available = $available;

        $message = "Insufficient stock for \"{$productName}\": requested {$requested}, only {$available} available.";
        parent::__construct($message);
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }
}
