-- ============================================================
-- QuickTally POS - Database Schema
-- System Type 7: Point-of-Sale (POS) / Sales Management System
-- WMA4 Advanced Web Development - Midterm Phase
-- ============================================================

DROP DATABASE IF EXISTS quicktally_pos;
CREATE DATABASE quicktally_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quicktally_pos;

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
CREATE TABLE categories (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100) NOT NULL UNIQUE,
    description     VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: products  (primary record of the system)
-- ------------------------------------------------------------
CREATE TABLE products (
    product_id      INT AUTO_INCREMENT PRIMARY KEY,
    category_id     INT NOT NULL,
    sku             VARCHAR(30) NOT NULL UNIQUE,
    product_name    VARCHAR(150) NOT NULL,
    description     VARCHAR(255) DEFAULT NULL,
    unit_price      DECIMAL(10,2) NOT NULL,
    stock_quantity  INT NOT NULL DEFAULT 0,
    reorder_level   INT NOT NULL DEFAULT 10,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: sales  (a single sales transaction / receipt)
-- ------------------------------------------------------------
CREATE TABLE sales (
    sale_id         INT AUTO_INCREMENT PRIMARY KEY,
    reference_no    VARCHAR(20) NOT NULL UNIQUE,
    cashier_name    VARCHAR(100) NOT NULL,
    customer_name   VARCHAR(100) DEFAULT 'Walk-in Customer',
    total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    change_due      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sale_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: sale_items  (line items of a sale - links sales & products)
-- ------------------------------------------------------------
CREATE TABLE sale_items (
    sale_item_id    INT AUTO_INCREMENT PRIMARY KEY,
    sale_id         INT NOT NULL,
    product_id      INT NOT NULL,
    quantity        INT NOT NULL,
    unit_price      DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_saleitems_sale
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_saleitems_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sample seed data
-- ------------------------------------------------------------
INSERT INTO categories (category_name, description) VALUES
('Beverages', 'Bottled and canned drinks'),
('Snacks', 'Chips, biscuits, and packaged snacks'),
('Grocery', 'Everyday grocery items'),
('Personal Care', 'Hygiene and personal care products');

INSERT INTO products (category_id, sku, product_name, description, unit_price, stock_quantity, reorder_level) VALUES
(1, 'BEV-001', 'Bottled Water 500ml', 'Purified drinking water', 15.00, 120, 30),
(1, 'BEV-002', 'Soft Drink 1.5L', 'Assorted soft drink', 65.00, 60, 20),
(2, 'SNK-001', 'Potato Chips 60g', 'Salted potato chips', 25.00, 80, 20),
(2, 'SNK-002', 'Chocolate Bar 45g', 'Milk chocolate bar', 20.00, 100, 25),
(3, 'GRO-001', 'Instant Noodles', 'Pack of instant noodles', 15.00, 150, 40),
(3, 'GRO-002', 'Canned Sardines 155g', 'Canned sardines in tomato sauce', 22.00, 90, 20),
(4, 'PC-001', 'Bar Soap 90g', 'Antibacterial bar soap', 18.00, 70, 15),
(4, 'PC-002', 'Shampoo Sachet 12ml', 'Single-use shampoo sachet', 8.00, 200, 50);
