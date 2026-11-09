CREATE DATABASE IF NOT EXISTS lab04_ecommerce
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE lab04_ecommerce;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    description VARCHAR(1000),
    product_image VARCHAR(255),
    available_for_sale TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);