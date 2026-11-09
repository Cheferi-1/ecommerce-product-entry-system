# LAB 04 - E-Commerce Product Entry

## Description

This project is a product entry system developed using
HTML, CSS, PHP and MySQL.

It allows users to add products and upload product images.

## Technologies

- HTML
- CSS
- PHP
- MySQL
- PDO

## Database Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin.
3. Select the SQL tab.
4. Execute database.sql.
5. The database lab04_ecommerce will be created.

## Project Setup

1. Copy the folder into C:\xampp\htdocs.
2. Start Apache and MySQL.
3. Open your browser.
4. Visit:

http://localhost/LAB04_Ecommerce_Products/

## Validation Rules

- Product name must contain 3 to 100 characters.
- SKU is required and must be unique.
- Category is required.
- Price must be greater than zero.
- Quantity must be a non-negative integer.
- Description cannot exceed 1000 characters.
- Product image is optional.
- Only JPEG and PNG images are accepted.
- Maximum image size is 2MB.

## Database

Database: lab04_ecommerce

Table: products

## Security

- PDO is used for database connections.
- Prepared statements are used for insertion.
- Product image types are checked on the server.
- Uploaded images receive generated filenames.
- User input is validated.
- Output is escaped using htmlspecialchars().