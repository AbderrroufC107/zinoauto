-- MySQL schema for Zino Auto
-- IMPORTANT: This file is for MySQL/phpMyAdmin only. Do NOT import into SQLite.
CREATE DATABASE IF NOT EXISTS zino_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE zino_auto;

CREATE TABLE IF NOT EXISTS admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY,
  company_name VARCHAR(255),
  company_logo VARCHAR(255),
  company_phone VARCHAR(50) DEFAULT '',
  company_email VARCHAR(255) DEFAULT '',
  company_address TEXT,
  company_nif VARCHAR(50) DEFAULT '',
  company_rc VARCHAR(50) DEFAULT '',
  company_nis VARCHAR(50) DEFAULT ''
);

CREATE TABLE IF NOT EXISTS cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  brand VARCHAR(255),
  body_type VARCHAR(100),
  color VARCHAR(100),
  engine VARCHAR(100),
  price_manual DECIMAL(12,2),
  price_automatic DECIMAL(12,2),
  customs_price DECIMAL(12,2),
  image VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS shipping_companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  contact_phone VARCHAR(100),
  contact_email VARCHAR(255),
  website VARCHAR(255),
  notes TEXT,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME,
  updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  car_id INT,
  shipping_company_id INT,
  container_code VARCHAR(255),
  gearbox VARCHAR(50),
  manager_name VARCHAR(255),
  client_name VARCHAR(255),
  client_surname VARCHAR(255),
  client_dob DATE,
  client_phone VARCHAR(50),
  client_email VARCHAR(255),
  client_address TEXT,
  client_passport VARCHAR(100),
  client_photo VARCHAR(255),
  created_at DATETIME,
  paid TINYINT(1) DEFAULT 0,
  paid_at DATETIME NULL,
  shipped TINYINT(1) DEFAULT 0,
  shipped_at DATETIME NULL,
  received TINYINT(1) DEFAULT 0,
  received_at DATETIME NULL,
  FOREIGN KEY (car_id) REFERENCES cars(id),
  FOREIGN KEY (shipping_company_id) REFERENCES shipping_companies(id)
);

CREATE INDEX IF NOT EXISTS idx_shipping_companies_active ON shipping_companies(active);

-- Insert default admin placeholder (use set_admin_password.php or update via SQL)
INSERT INTO admin (username, password) VALUES ('admin', '')
ON DUPLICATE KEY UPDATE username=VALUES(username);
