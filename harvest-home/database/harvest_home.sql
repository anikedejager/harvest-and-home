-- Harvest & Home database (MySQL / MariaDB)
-- Built from the Enhanced Entity Relationship Diagram (EERD).
-- Import this file in phpMyAdmin (Import tab) on your hosting account.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS promotion_items;
DROP TABLE IF EXISTS promotions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- users: customers and administrators
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    address        TEXT NULL,
    role           ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- categories: groups products for browsing and filtering
CREATE TABLE categories (
    category_id    INT AUTO_INCREMENT PRIMARY KEY,
    category_name  VARCHAR(100) NOT NULL UNIQUE,
    description    TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- products: the catalogue
CREATE TABLE products (
    product_id      INT AUTO_INCREMENT PRIMARY KEY,
    category_id     INT NOT NULL,
    product_name    VARCHAR(150) NOT NULL,
    description     TEXT NOT NULL,
    price           DECIMAL(10,2) NOT NULL,
    stock_quantity  INT NOT NULL DEFAULT 0,
    product_image   VARCHAR(255) NULL,
    product_status  ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- orders: one row per placed order
CREATE TABLE orders (
    order_id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NOT NULL,
    order_date         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount       DECIMAL(10,2) NOT NULL,
    fulfilment_method  ENUM('delivery','collection') NOT NULL,
    delivery_address   TEXT NULL,
    order_status       ENUM('processing','delivered','collection_pending','collected','cancelled') NOT NULL,
    delivery_fee       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- order_items: the products inside an order (price at time of purchase)
CREATE TABLE order_items (
    order_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT NOT NULL,
    product_id     INT NOT NULL,
    quantity       INT NOT NULL,
    price          DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id)
        REFERENCES orders(order_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id)
        REFERENCES products(product_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- payments: one payment per order (simulated online payment)
CREATE TABLE payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL UNIQUE,
    payment_method  ENUM('online_payment') NOT NULL DEFAULT 'online_payment',
    payment_status  ENUM('pending','successful','failed') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id)
        REFERENCES orders(order_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- promotions: named promotions that can be switched on or off
CREATE TABLE promotions (
    promotion_id      INT AUTO_INCREMENT PRIMARY KEY,
    promotion_name    VARCHAR(100) NOT NULL,
    promotion_status  ENUM('active','inactive') NOT NULL DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- promotion_items: links promotions to products with the promotional price
CREATE TABLE promotion_items (
    promotion_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    product_id         INT NOT NULL,
    promotion_id       INT NOT NULL,
    promo_price        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_promoitems_product FOREIGN KEY (product_id)
        REFERENCES products(product_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_promoitems_promotion FOREIGN KEY (promotion_id)
        REFERENCES promotions(promotion_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Sample data
-- Creates a local administrator and a sample customer for testing.
-- The test login details are provided separately with the project,
-- not in this file. Change the passwords before using the live site.
-- ---------------------------------------------------------------

INSERT INTO users (full_name, email, password_hash, phone, address, role) VALUES
('Harvest & Home Admin', 'admin@harvestandhome.co.za', '$2y$10$N48Yyi6PnjJOOfTaYkxzxusOmbuZH197Dx7gQQC/izK9M1t94Gcc6', '021 123 4567', NULL, 'admin'),
('Sarah Jacobs', 'sarah@example.com', '$2y$10$pquxUOVwNxPnRx674K8hXOYOE8evdTwJIjEIcbQqQ1jEFYXpFcmWi', '082 000 0000', '12 Vine Street, Stellenbosch, 7600, Western Cape', 'customer');

INSERT INTO categories (category_name, description) VALUES
('Fresh Produce', 'Fresh fruit and vegetables from the farm.'),
('Pantry & Preserves', 'Homemade jams, preserves and pantry staples.'),
('Homemade Bakery', 'Breads, rusks and baked goods made by hand.'),
('Harvest Boxes', 'Mixed boxes and hampers of farm favourites.'),
('Seasonal Specials', 'Limited seasonal products while stocks last.');

INSERT INTO products (category_id, product_name, description, price, stock_quantity, product_image, product_status) VALUES
(1, 'Farm Fresh Tomatoes', 'Ripe, juicy tomatoes picked fresh from our farm. Sold per 1 kg bag.', 35.00, 20, NULL, 'active'),
(5, 'Seasonal Veggie Basket', 'A basket of the best vegetables in season this week.', 120.00, 23, NULL, 'active'),
(1, 'Fresh Cucumbers', 'Crisp cucumbers, great for salads. Sold per 2 pack.', 28.00, 26, NULL, 'active'),
(1, 'Fresh Carrots', 'Sweet farm carrots. Sold per 1 kg bag.', 25.00, 29, NULL, 'active'),
(2, 'Apricot Jam', 'Traditional apricot jam made in small batches. 450 g jar.', 56.00, 32, NULL, 'active'),
(2, 'Strawberry Preserve', 'Whole-fruit strawberry preserve. 450 g jar.', 70.00, 35, NULL, 'active'),
(3, 'Homemade Bread', 'Freshly baked farm-style white loaf.', 42.00, 38, NULL, 'active'),
(3, 'Farmstead Rusks', 'Buttermilk rusks, perfect with coffee. 500 g bag.', 60.00, 41, NULL, 'active'),
(4, 'Harvest Hamper', 'A hamper of jams, rusks and seasonal produce. Ideal as a gift.', 180.00, 44, NULL, 'active');

INSERT INTO promotions (promotion_name, promotion_status) VALUES
('Seasonal Harvest', 'active');

INSERT INTO promotion_items (product_id, promotion_id, promo_price) VALUES
(1, 1, 29.00),
(2, 1, 99.00);

-- Sample orders for Sarah so the dashboard and order history have data
INSERT INTO orders (user_id, order_date, total_amount, fulfilment_method, delivery_address, order_status, delivery_fee) VALUES
(2, '2026-09-20 10:15:00', 158.00, 'collection', NULL, 'collected', 0.00),
(2, '2026-10-05 14:30:00', 170.00, 'delivery', '12 Vine Street, Stellenbosch, 7600, Western Cape', 'delivered', 50.00),
(2, '2026-10-10 09:05:00', 95.00, 'collection', NULL, 'collection_pending', 0.00);

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 8, 1, 60.00), (1, 7, 1, 42.00), (1, 5, 1, 56.00),
(2, 2, 1, 120.00),
(3, 4, 1, 25.00), (3, 6, 1, 70.00);

INSERT INTO payments (order_id, payment_method, payment_status) VALUES
(1, 'online_payment', 'successful'),
(2, 'online_payment', 'successful'),
(3, 'online_payment', 'successful');
