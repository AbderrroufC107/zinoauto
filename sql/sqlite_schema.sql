-- SQLite schema for Zino Auto
-- IMPORTANT: This file is for SQLite only. Do NOT import this file into MySQL/phpMyAdmin.
-- To create the SQLite DB, either run the project's `db_init.php` or use sqlite3:
--   sqlite3 C:\xampp\htdocs\zino_auto\data\app.db < C:\xampp\htdocs\zino_auto\sql\sqlite_schema.sql
-- The following PRAGMA is SQLite-specific and will cause errors in MySQL:
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS admin (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL
);

-- Cars catalog: extended with brand, color, type, engine, and pricing
CREATE TABLE IF NOT EXISTS cars (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  -- Model name (e.g., Corolla, A4, Sportage)
  name TEXT NOT NULL,
  -- Brand/Make (e.g., Toyota, Audi, Kia)
  brand TEXT NOT NULL,
  -- Body type (e.g., Sedan, SUV, Hatchback)
  body_type TEXT NOT NULL,
  -- Color (single dominant color per entry)
  color TEXT NOT NULL,
  -- Engine spec (e.g., 2.0L Petrol, 1.6L Diesel, EV)
  engine TEXT NOT NULL,
  -- Price for manual gearbox option
  price_manual REAL NOT NULL,
  -- Price for automatic gearbox option
  price_automatic REAL NOT NULL,
  -- Customs/duties price (single value per car)
  customs_price REAL NOT NULL,
  -- Optional image path/URL
  image TEXT
);

-- Helpful indexes for search/filtering
CREATE INDEX IF NOT EXISTS idx_cars_brand ON cars(brand);
CREATE INDEX IF NOT EXISTS idx_cars_name ON cars(name);
CREATE INDEX IF NOT EXISTS idx_cars_body_type ON cars(body_type);

CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  car_id INTEGER,
  shipping_company_id INTEGER,
  container_code TEXT,
  -- Gearbox selected for this order: 'manual' or 'automatic'
  gearbox TEXT,
  manager_name TEXT,
  client_name TEXT,
  client_surname TEXT,
  client_dob TEXT,
  client_phone TEXT,
  client_email TEXT,
  client_address TEXT,
  client_passport TEXT,
  client_photo TEXT,
  created_at TEXT,
  paid INTEGER DEFAULT 0,
  paid_at TEXT,
  shipped INTEGER DEFAULT 0,
  shipped_at TEXT,
  received INTEGER DEFAULT 0,
  received_at TEXT,
  FOREIGN KEY(car_id) REFERENCES cars(id),
  FOREIGN KEY(shipping_company_id) REFERENCES shipping_companies(id)
);

-- Shipping companies we work with
CREATE TABLE IF NOT EXISTS shipping_companies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  contact_phone TEXT,
  contact_email TEXT,
  website TEXT,
  notes TEXT,
  active INTEGER DEFAULT 1,
  created_at TEXT,
  updated_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_shipping_companies_active ON shipping_companies(active);

-- NOTE: For security it's recommended to set the admin password using the provided
-- `set_admin_password.php` script after importing. The password field must contain
-- a PHP password_hash() value.

-- Example: Create an admin user with an empty password-hash placeholder, then
-- use set_admin_password.php to set a real password safely.
INSERT OR IGNORE INTO admin (username, password) VALUES ('admin', '');
