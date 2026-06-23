-- database.sql
CREATE DATABASE IF NOT EXISTS project3;
USE project3;

-- Customers table (with UNIQUE and NOT NULL constraints)
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  phone VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table (with Foreign Key and CHECK constraints)
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  product VARCHAR(100) NOT NULL,
  quantity INT NOT NULL CHECK (quantity >= 1),
  price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
  status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);