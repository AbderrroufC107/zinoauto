<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$admin = current_admin();
$db = get_db();

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
        $db->exec("ALTER TABLE settings ADD COLUMN $definition");
    } catch (Throwable $e) {
        // Column already exists.
    }
}

$db->exec("INSERT OR IGNORE INTO settings (
    id, company_name, company_logo, company_phone, company_email, company_address, company_nif, company_rc, company_nis
) VALUES (1, 'Zino Auto', NULL, '', '', '', '', '', '')");

$text = json_decode('{
  "page_title": "\u0644\u0648\u062d\u0629 \u0627\u0644\u062a\u062d\u0643\u0645",
  "alert_success": "\u062a\u0645 \u0627\u0644\u062a\u062d\u062f\u064a\u062b \u0628\u0646\u062c\u0627\u062d.",
  "alert_error": "\u062d\u062f\u062b \u062e\u0637\u0623. \u062d\u0627\u0648\u0644 \u0645\u062c\u062f\u062f\u0627\u064b.",
  "welcome": "\u0645\u0631\u062d\u0628\u0627\u064b\u060c ",
  "settings_button": "\u0627\u0644\u0625\u0639\u062f\u0627\u062f\u0627\u062a",
  "stat_orders": "\u0625\u062c\u0645\u0627\u0644\u064a \u0627\u0644\u0637\u0644\u0628\u0627\u062a",
  "stat_cars": "\u0627\u0644\u0633\u064a\u0627\u0631\u0627\u062a \u0627\u0644\u0645\u0633\u062c\u0644\u0629",
  "stat_paid": "\u0627\u0644\u0637\u0644\u0628\u0627\u062a \u0627\u0644\u0645\u062f\u0641\u0648\u0639\u0629",
  "stat_shipped": "\u0627\u0644\u0637\u0644\u0628\u0627\u062a \u0627\u0644\u0645\u0634\u062d\u0648\u0646\u0629",
  "quick_create": "\u0625\u0646\u0634\u0627\u0621 \u0637\u0644\u0628 \u062c\u062f\u064a\u062f",
  "quick_orders": "\u0639\u0631\u0636 \u0627\u0644\u0637\u0644\u0628\u0627\u062a",
  "quick_cars": "\u0625\u062f\u0627\u0631\u0629 \u0627\u0644\u0633\u064a\u0627\u0631\u0627\u062a",
  "quick_shipping": "\u0634\u0631\u0643\u0627\u062a \u0627\u0644\u0634\u062d\u0646",
  "quick_logout": "\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062e\u0631\u0648\u062c",
  "company_section": "\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0634\u0631\u0643\u0629",
  "company_name": "\u0627\u0633\u0645 \u0627\u0644\u0634\u0631\u0643\u0629",
  "company_logo": "\u0627\u0644\u0634\u0639\u0627\u0631 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "company_logo_alt": "\u0634\u0639\u0627\u0631 \u0627\u0644\u0634\u0631\u0643\u0629",
  "company_save": "\u062d\u0641\u0638 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a",
  "err_admin_validation": "\u0627\u062f\u062e\u0644 \u0627\u0633\u0645 \u0645\u0633\u062a\u062e\u062f\u0645 \u0648\u0643\u0644\u0645\u0629 \u0645\u0631\u0648\u0631 (\u0639\u0644\u0649 \u0627\u0644\u0623\u0642\u0644 \u0633\u062a\u0629 \u0623\u062d\u0631\u0641).",
  "err_admin_exists": "\u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645 \u0645\u0648\u062c\u0648\u062f \u0645\u0633\u0628\u0642\u0627\u064b.",
  "msg_admin_added": "\u062a\u0645\u062a \u0625\u0636\u0627\u0641\u0629 \u0627\u0644\u0645\u0634\u0631\u0641 \u0628\u0646\u062c\u0627\u062d.",
  "err_delete_self": "\u0644\u0627 \u064a\u0645\u0643\u0646 \u062d\u0630\u0641 \u062d\u0633\u0627\u0628\u0643 \u0627\u0644\u0634\u062e\u0635\u064a.",
  "msg_admin_deleted": "\u062a\u0645 \u062d\u0630\u0641 \u0627\u0644\u0645\u0634\u0631\u0641 \u0628\u0646\u062c\u0627\u062d.",
  "err_delete_last": "\u0644\u0627 \u064a\u0645\u0643\u0646 \u062d\u0630\u0641 \u0622\u062e\u0631 \u0645\u0634\u0631\u0641 \u0645\u062a\u0628\u0642\u064d."
}', true);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_company'])) {
    $name = trim($_POST['company_name'] ?? '');
    $name = $name !== '' ? $name : $text['page_title'];

    $row = $db->query('SELECT company_logo FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    $oldLogo = $row['company_logo'] ?? null;
    $logoName = $oldLogo;

    if (!empty($_FILES['company_logo']['name'])) {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $logoName = time() . '_' . uniqid('', true) . '.' . $ext;
        $uploadPath = $uploadDir . '/' . $logoName;

        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadPath)) {
            if ($oldLogo && file_exists($uploadDir . '/' . $oldLogo)) {
                unlink($uploadDir . '/' . $oldLogo);
            }
        } else {
            $err = $text['alert_error'];
        }
    }

    if ($err === '') {
        $stmt = $db->prepare('UPDATE settings SET company_name = ?, company_logo = ? WHERE id = 1');
        $stmt->execute([$name, $logoName]);
        $msg = $text['alert_success'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($password) < 6) {
        $err = $text['err_admin_validation'];
    } else {
        $stmt = $db->prepare('SELECT id FROM admin WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $err = $text['err_admin_exists'];
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('INSERT INTO admin (username, password) VALUES (?, ?)')->execute([$username, $hash]);
            $msg = $text['msg_admin_added'];
        }
    }
}

if (isset($_GET['delete_admin'])) {
    $id = (int)$_GET['delete_admin'];
    if ($id === ($_SESSION['admin_id'] ?? 0)) {
        $err = $text['err_delete_self'];
    } else {
        $count = (int)$db->query('SELECT COUNT(*) FROM admin')->fetchColumn();
        if ($count > 1) {
            $db->prepare('DELETE FROM admin WHERE id = ?')->execute([$id]);
            $msg = $text['msg_admin_deleted'];
        } else {
            $err = $text['err_delete_last'];
        }
    }
}

$settings = $db->query('SELECT company_name, company_logo FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$admins = $db->query('SELECT id, username FROM admin ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$cars_count = (int)$db->query('SELECT COUNT(*) FROM cars')->fetchColumn();
$orders_count = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$paid_count = (int)$db->query('SELECT COUNT(*) FROM orders WHERE paid = 1')->fetchColumn();
$shipped_count = (int)$db->query('SELECT COUNT(*) FROM orders WHERE shipped = 1')->fetchColumn();

$page_title = $text['page_title'];
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-12">
    <?php if ($msg): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($err) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h2 class="mb-1"><?= $text['page_title'] ?></h2>
          <p class="text-muted mb-0"><?= $text['welcome'] ?><strong><?= htmlspecialchars($admin['username']) ?></strong></p>
        </div>
        <a href="settings.php" class="btn btn-outline-primary px-4"><?= $text['settings_button'] ?></a>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="row g-3">
      <div class="col-md-3 col-6">
        <div class="card text-center border-0 shadow-sm h-100">
          <div class="card-body py-3">
            <div class="fs-5 text-primary mb-1">📦</div>
            <h5 class="mb-0"><?= number_format($orders_count) ?></h5>
            <small class="text-muted"><?= $text['stat_orders'] ?></small>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="card text-center border-0 shadow-sm h-100">
          <div class="card-body py-3">
            <div class="fs-5 text-purple mb-1">🚗</div>
            <h5 class="mb-0"><?= number_format($cars_count) ?></h5>
            <small class="text-muted"><?= $text['stat_cars'] ?></small>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="card text-center border-0 shadow-sm h-100">
          <div class="card-body py-3">
            <div class="fs-5 text-success mb-1">💰</div>
            <h5 class="mb-0"><?= number_format($paid_count) ?></h5>
            <small class="text-muted"><?= $text['stat_paid'] ?></small>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="card text-center border-0 shadow-sm h-100">
          <div class="card-body py-3">
            <div class="fs-5 text-warning mb-1">🚚</div>
            <h5 class="mb-0"><?= number_format($shipped_count) ?></h5>
            <small class="text-muted"><?= $text['stat_shipped'] ?></small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="row g-3">
      <div class="col-md-3 col-6">
        <a href="create_order.php" class="card text-center text-decoration-none border-0 shadow-sm h-100 py-3">
          <div class="fs-4 text-success mb-2">＋</div>
          <div class="fw-bold"><?= $text['quick_create'] ?></div>
        </a>
      </div>
      <div class="col-md-3 col-6">
        <a href="orders.php" class="card text-center text-decoration-none border-0 shadow-sm h-100 py-3">
          <div class="fs-4 text-warning mb-2">📄</div>
          <div class="fw-bold"><?= $text['quick_orders'] ?></div>
        </a>
      </div>
      <div class="col-md-3 col-6">
        <a href="cars.php" class="card text-center text-decoration-none border-0 shadow-sm h-100 py-3">
          <div class="fs-4 text-purple mb-2">🚙</div>
          <div class="fw-bold"><?= $text['quick_cars'] ?></div>
        </a>
      </div>
      <div class="col-md-3 col-6">
        <a href="shipping.php" class="card text-center text-decoration-none border-0 shadow-sm h-100 py-3">
          <div class="fs-4 text-info mb-2">🚢</div>
          <div class="fw-bold"><?= $text['quick_shipping'] ?></div>
        </a>
      </div>
      <div class="col-md-3 col-6">
        <a href="login.php?logout=1" class="card text-center text-decoration-none border-0 shadow-sm h-100 py-3 text-danger">
          <div class="fs-4 mb-2">⎋</div>
          <div class="fw-bold"><?= $text['quick_logout'] ?></div>
        </a>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 pb-0">
        <h5 class="mb-0"><?= $text['company_section'] ?></h5>
      </div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="save_company" value="1">
          <div class="mb-3">
            <label class="form-label"><?= $text['company_name'] ?></label>
            <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? 'Zino Auto') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?= $text['company_logo'] ?></label>
            <input class="form-control" type="file" name="company_logo" accept="image/*">
            <?php if (!empty($settings['company_logo']) && file_exists(__DIR__ . '/../uploads/' . $settings['company_logo'])): ?>
              <div class="mt-2">
                <img src="<?= BASE_URL ?>/uploads/<?= urlencode($settings['company_logo']) ?>" alt="<?= $text['company_logo_alt'] ?>" style="height:60px; border-radius:6px; object-fit:cover;">
              </div>
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-primary w-100"><?= $text['company_save'] ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
