<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$page_title = $page_title ?? 'Zino Auto';

$labels = json_decode('{
  "brand_placeholder": "Zino Auto",
  "nav_home": "\u0627\u0644\u0631\u0626\u064a\u0633\u064a\u0629",
  "nav_dashboard": "\u0644\u0648\u062d\u0629 \u0627\u0644\u062a\u062d\u0643\u0645",
  "nav_login": "\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062f\u062e\u0648\u0644",
  "nav_logout": "\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062e\u0631\u0648\u062c",
  "nav_toggle": "\u062a\u0628\u062f\u064a\u0644 \u0627\u0644\u0642\u0627\u0626\u0645\u0629",
  "brand_alt": "\u0634\u0639\u0627\u0631"
}', true);

$brandName = $labels['brand_placeholder'];
$brandLogo = null;
$shouldLoadSettings = !defined('HEADER_SKIP_SETTINGS');

if ($shouldLoadSettings && file_exists(DB_FILE)) {
    static $cachedSettings = null;

    if ($cachedSettings === null) {
        try {
            $dbh = get_db();
            $dbh->exec("CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                company_name TEXT,
                company_logo TEXT,
                company_phone TEXT DEFAULT '',
                company_email TEXT DEFAULT '',
                company_address TEXT DEFAULT '',
                company_nif TEXT DEFAULT '',
                company_rc TEXT DEFAULT '',
                company_nis TEXT DEFAULT ''
            )");

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
                    $dbh->exec("ALTER TABLE settings ADD COLUMN $definition");
                } catch (Throwable $e) {
                    // Column already exists.
                }
            }

            $dbh->exec("INSERT OR IGNORE INTO settings (
                id, company_name, company_logo, company_phone, company_email, company_address, company_nif, company_rc, company_nis
            ) VALUES (1, 'Zino Auto', NULL, '', '', '', '', '', '')");

            $stmt = $dbh->prepare('SELECT company_name, company_logo FROM settings WHERE id = 1');
            $stmt->execute();
            $cachedSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Failed to load settings: ' . $e->getMessage());
            $cachedSettings = [];
        }
    }

    if (!empty($cachedSettings)) {
        $brandName = trim((string)($cachedSettings['company_name'] ?? '')) ?: $labels['brand_placeholder'];
        $brandLogo = $cachedSettings['company_logo'] ?? null;
    }
}
?>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($brandName) ?></title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin.css') ?>">

  <style>
    body {
      font-family: 'Tajawal', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
      padding-top: 72px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container-fluid px-3 px-md-4">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>/">
      <?php if ($brandLogo && file_exists(__DIR__ . '/../uploads/' . $brandLogo)): ?>
        <img src="<?= BASE_URL ?>/uploads/<?= urlencode($brandLogo) ?>?v=<?= time() ?>"
             alt="<?= $labels['brand_alt'] ?> <?= htmlspecialchars($brandName) ?>"
             width="28"
             height="28"
             style="object-fit: cover; border-radius: 4px;"
             loading="lazy">
      <?php endif; ?>
      <span><?= htmlspecialchars($brandName) ?></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu" aria-controls="navmenu" aria-expanded="false" aria-label="<?= $labels['nav_toggle'] ?>">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/"><?= $labels['nav_home'] ?></a>
        </li>
      </ul>

      <ul class="navbar-nav">
        <?php if (!empty($_SESSION['admin_id'])): ?>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'dashboard') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php"><?= $labels['nav_dashboard'] ?></a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white-50" href="<?= BASE_URL ?>/admin/login.php?logout=1"><?= $labels['nav_logout'] ?></a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'login.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/login.php"><?= $labels['nav_login'] ?></a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
