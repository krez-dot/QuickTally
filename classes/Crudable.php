<?php

/**
 * Crudable
 * Contract implemented by every model that supports the standard
 * Create / Update / Delete lifecycle against the database.
 */
interface Crudable
{
    public function create(): bool;
    public function update(): bool;
    public function delete(): bool;
}
