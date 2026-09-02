# QuickTally POS — System Type 7 (Point-of-Sale / Sales Management System)

WMA4 Advanced Web Development — Midterm Phase project. Built with native PHP
(OOP, PDO with prepared statements) and MySQL.

## Features (Midterm requirements)

- **Data-entry/transaction form** — `sales.php` lets a cashier build a cart
  and complete a sale, with server-side validation and friendly error
  handling (empty cart, insufficient stock, underpayment).
- **Normalized MySQL database** — 4 related tables: `categories`,
  `products`, `sales`, `sale_items` (see `sql/database.sql`).
- **Full CRUD** for products (`products.php`, `product_form.php`,
  `product_delete.php`) using PDO prepared statements only.
- **Searchable / filterable / sortable listing** — product list (by name,
  category, price, stock) and sales history (by date range, keyword).
- **Object-Oriented PHP** — `Database` (singleton connection), `Crudable`
  interface, abstract `BaseModel`, and `Category` / `Product` / `Sale`
  classes with encapsulation and inheritance. `InsufficientStockException`
  provides explicit exception handling around checkout.
- **Dashboard** — today's sales total & transaction count, low-stock
  alerts, and top-selling products, computed via PHP/SQL (`index.php`).

## Setup

1. Create the database: import `sql/database.sql` into MySQL
   (e.g. `mysql -u root -p < sql/database.sql`, or via phpMyAdmin).
2. Update credentials in `config/database.php` if needed.
3. Serve the project root with PHP's built-in server:
   ```
   php -S localhost:8000
   ```
4. Visit `http://localhost:8000/index.php`.

## Project structure

```
├── config/database.php        DB connection constants
├── classes/
│   ├── Database.php           PDO singleton connection
│   ├── Crudable.php           CRUD contract (interface)
│   ├── BaseModel.php          Abstract base model
│   ├── Category.php
│   ├── Product.php
│   ├── Sale.php                checkout() transaction logic
│   └── InsufficientStockException.php
├── includes/header.php, footer.php
├── css/style.css
├── sql/database.sql            Schema + seed data
├── index.php                   Dashboard
├── products.php / product_form.php / product_delete.php
├── sales.php                   POS transaction screen
├── sales_history.php / sale_view.php
└── README.md
```

## Notes for the Documentation Report

- Primary record: **products** (also see `sales` for the transaction
  entity).
- Object-Oriented design: `Product` and `Category` both extend
  `BaseModel` (inheritance) and implement `Crudable` (interface);
  properties are private/protected with public getters/setters
  (encapsulation).
- Exception handling: `Sale::checkout()` wraps the insert in a DB
  transaction and throws `InsufficientStockException` /
  `InvalidArgumentException` on invalid input, both caught in
  `sales.php` and shown as friendly messages.
- This is scaffolding for the **Midterm phase only**. Final Term
  features (authentication & RBAC, REST API, Laravel module) are not
  yet implemented — add them per Section V-C of the project
  instructions when Weeks 10–17 topics are covered.
