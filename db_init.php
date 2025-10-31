<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Create data directory if missing
if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);

$db = get_db();

// Create tables
$db->exec("CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT
);");

$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    company_name TEXT,
    company_logo TEXT,
    company_phone TEXT DEFAULT '',
    company_email TEXT DEFAULT '',
    company_address TEXT DEFAULT '',
    company_nif TEXT DEFAULT '',
    company_rc TEXT DEFAULT '',
    company_nis TEXT DEFAULT ''
);");

$columns = [
    'company_phone TEXT DEFAULT \'\'',
    'company_email TEXT DEFAULT \'\'',
    'company_address TEXT DEFAULT \'\'',
    'company_nif TEXT DEFAULT \'\'',
    'company_rc TEXT DEFAULT \'\'',
    'company_nis TEXT DEFAULT \'\''
];
foreach ($columns as $definition) {
    try {
        $db->exec("ALTER TABLE settings ADD COLUMN $definition");
    } catch (Throwable $e) {
        // Column already exists.
    }
}

$db->exec("INSERT OR IGNORE INTO settings(
    id, company_name, company_logo, company_phone, company_email, company_address, company_nif, company_rc, company_nis
) VALUES (1, 'Zino Auto', NULL, '', '', '', '', '', '');");

// Cars table: extended schema
$db->exec("CREATE TABLE IF NOT EXISTS cars (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    brand TEXT NOT NULL,
    body_type TEXT NOT NULL,
    color TEXT NOT NULL,
    engine TEXT NOT NULL,
    price_manual REAL NOT NULL,
    price_automatic REAL NOT NULL,
    customs_price REAL NOT NULL,
    image TEXT
);");

$db->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    car_id INTEGER,
    shipping_company_id INTEGER,
    container_code TEXT,
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
);");

// Shipping companies table
$db->exec("CREATE TABLE IF NOT EXISTS shipping_companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    contact_phone TEXT,
    contact_email TEXT,
    website TEXT,
    notes TEXT,
    active INTEGER DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
);");

$db->exec("CREATE INDEX IF NOT EXISTS idx_shipping_companies_active ON shipping_companies(active);");

// Insert default admin if not exists
$defaultUsername = 'admin';
$defaultPasswordPlain = '123456';
$defaultPasswordHash = password_hash($defaultPasswordPlain, PASSWORD_DEFAULT);

$stmt = $db->prepare('SELECT password FROM admin WHERE username = ?');
$stmt->execute([$defaultUsername]);
$adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminRow) {
    $ins = $db->prepare('INSERT INTO admin (username, password) VALUES (?, ?)');
    $ins->execute([$defaultUsername, $defaultPasswordHash]);
    echo "Default admin created: username=admin password={$defaultPasswordPlain}\n";
} else {
    $stored = (string)$adminRow['password'];
    $needsUpdate = $stored === '' || strlen($stored) < 20 || password_needs_rehash($stored, PASSWORD_DEFAULT);
    if ($needsUpdate) {
        $upd = $db->prepare('UPDATE admin SET password = ? WHERE username = ?');
        $upd->execute([$defaultPasswordHash, $defaultUsername]);
        echo "Default admin password reset to {$defaultPasswordPlain}\n";
    } else {
        echo "Admin already exists.\n";
    }
}

echo "DB initialized at " . DB_FILE . "\n";

echo "Make sure to delete or secure db_init.php after setup.\n";

?>
