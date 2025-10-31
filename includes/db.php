<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Returns a shared SQLite PDO connection.
 *
 * Using a static variable prevents initializing multiple connections within the same request,
 * which cuts a bit of overhead on pages like the login screen.
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0777, true) && !is_dir(DATA_DIR)) {
        throw new RuntimeException('Failed to initialize data directory: ' . DATA_DIR);
    }

    $dsn = 'sqlite:' . DB_FILE;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Enable foreign keys and reduce lock contention delays.
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 3000');

    return $pdo;
}
?>
