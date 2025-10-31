<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$db = get_db();

try {
    $columns = $db->query('PRAGMA table_info(orders)')->fetchAll(PDO::FETCH_ASSOC);
    $have = [];
    foreach ($columns as $col) {
        $have[strtolower((string)$col['name'])] = true;
    }
    if (empty($have['shipping_company_id'])) {
        $db->exec('ALTER TABLE orders ADD COLUMN shipping_company_id INTEGER');
    }
    if (empty($have['container_code'])) {
        $db->exec("ALTER TABLE orders ADD COLUMN container_code TEXT");
    }
    if (empty($have['gearbox'])) {
        $db->exec("ALTER TABLE orders ADD COLUMN gearbox TEXT");
    }
    if (empty($have['client_address'])) {
        $db->exec("ALTER TABLE orders ADD COLUMN client_address TEXT");
    }
} catch (Throwable $e) {
    error_log('orders migration failed: ' . $e->getMessage());
}

$cars = $db->query('SELECT * FROM cars ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$carsById = [];
foreach ($cars as $car) {
    $carsById[(int)$car['id']] = $car;
}
$gearboxMap = [];
foreach ($cars as $car) {
    $gearboxMap[(int)$car['id']] = [
        'manual' => (float)($car['price_manual'] ?? 0) > 0,
        'automatic' => (float)($car['price_automatic'] ?? 0) > 0,
    ];
}

$companies = $db->query('SELECT id, name FROM shipping_companies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$manager_name = '';
$car_id = 0;
$gearbox = '';
$shipping_company_id = 0;
$container_code = '';
$client_name = '';
$client_surname = '';
$client_dob = '';
$client_phone = '';
$client_email = '';
$client_address = '';
$client_passport = '';
$client_photo = null;
$errors = [];

$text = json_decode('{
  "page_title": "\u0625\u0646\u0634\u0627\u0621 \u0637\u0644\u0628 \u062c\u062f\u064a\u062f",
  "alert_title": "\u0627\u0644\u0631\u062c\u0627\u0621 \u062a\u0635\u062d\u064a\u062d \u0627\u0644\u0623\u062e\u0637\u0627\u0621 \u0627\u0644\u062a\u0627\u0644\u064a\u0629:",
  "manager_label": "\u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u064a\u0631",
  "car_label": "\u0646\u0648\u0639 \u0627\u0644\u0633\u064a\u0627\u0631\u0629",
  "car_placeholder": "\u0627\u062e\u062a\u0631 \u0627\u0644\u0633\u064a\u0627\u0631\u0629...",
  "gearbox_label": "\u0646\u0648\u0639 \u0639\u0644\u0628\u0629 \u0627\u0644\u0633\u0631\u0639\u0629",
  "gearbox_hint_both": "\u0627\u062e\u062a\u0631 \u0627\u0644\u0646\u0638\u0627\u0645 \u0627\u0644\u0645\u0646\u0627\u0633\u0628 \u062d\u0633\u0628 \u0631\u063a\u0628\u0629 \u0627\u0644\u0632\u0628\u0648\u0646.",
  "gearbox_hint_manual": "\u062a\u0645 \u0627\u062e\u062a\u064a\u0627\u0631 \u064a\u062f\u0648\u064a (\u0645\u062a\u0648\u0641\u0631 \u0641\u0642\u0637).",
  "gearbox_hint_auto": "\u062a\u0645 \u0627\u062e\u062a\u064a\u0627\u0631 \u0623\u0648\u062a\u0648\u0645\u0627\u062a\u064a\u0643 (\u0645\u062a\u0648\u0641\u0631 \u0641\u0642\u0637).",
  "gearbox_hint_none": "\u0644\u0627 \u062a\u0648\u062c\u062f \u0623\u0633\u0639\u0627\u0631 \u0645\u0633\u062c\u0644\u0629 \u0644\u0647\u0630\u0647 \u0627\u0644\u0633\u064a\u0627\u0631\u0629.",
  "shipping_label": "\u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646",
  "shipping_placeholder": "\u2014 \u0627\u062e\u062a\u0631 \u0634\u0631\u0643\u0629 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a) \u2014",
  "container_label": "\u0631\u0642\u0645 \u0627\u0644\u062d\u0627\u0648\u064a\u0629 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "container_placeholder": "CMAU1234567",
  "customer_section": "\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0639\u0645\u064a\u0644",
  "name_label": "\u0627\u0644\u0627\u0633\u0645",
  "name_placeholder": "\u0645\u062b\u0627\u0644: \u0623\u062d\u0645\u062f",
  "name_hint": "\u064a\u064f\u0633\u0645\u062d \u0628\u0627\u0644\u062d\u0631\u0648\u0641 \u0627\u0644\u0639\u0631\u0628\u064a\u0629 \u0623\u0648 \u0627\u0644\u0625\u0646\u062c\u0644\u064a\u0632\u064a\u0629 \u0641\u0642\u0637.",
  "surname_label": "\u0627\u0644\u0644\u0642\u0628",
  "surname_placeholder": "\u0645\u062b\u0627\u0644: \u0627\u0644\u0639\u0644\u064a",
  "surname_hint": "\u064a\u064f\u0633\u0645\u062d \u0628\u0627\u0644\u062d\u0631\u0648\u0641 \u0641\u0642\u0637 (\u0628\u062f\u0648\u0646 \u0623\u0631\u0642\u0627\u0645).",
  "dob_label": "\u062a\u0627\u0631\u064a\u062e \u0627\u0644\u0645\u064a\u0644\u0627\u062f",
  "phone_label": "\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062a\u0641",
  "phone_placeholder": "0912345678",
  "phone_hint": "\u0623\u062f\u062e\u0644 \u0631\u0642\u0645\u0627\u064b \u0645\u0646 8 \u0625\u0644\u0649 15 \u062e\u0627\u0646\u0629 \u0628\u062f\u0648\u0646 \u0631\u0645\u0648\u0632 \u0623\u0648 \u0645\u0633\u0627\u0641\u0627\u062a.",
  "email_label": "\u0627\u0644\u0628\u0631\u064a\u062f \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "address_label": "\u0639\u0646\u0648\u0627\u0646 \u0627\u0644\u0632\u0628\u0648\u0646",
  "address_placeholder": "\u0627\u0644\u0645\u062f\u064a\u0646\u0629\u060c \u0627\u0644\u0634\u0627\u0631\u0639\u060c \u0631\u0642\u0645 \u0627\u0644\u0628\u0627\u0628",
  "passport_label": "\u0631\u0642\u0645 \u062c\u0648\u0627\u0632 \u0627\u0644\u0633\u0641\u0631",
  "passport_placeholder": "123456789",
  "passport_hint": "\u064a\u062c\u0628 \u0623\u0646 \u064a\u0643\u0648\u0646 \u0627\u0644\u0631\u0642\u0645 \u0645\u0643\u0648\u0651\u0646\u0627\u064b \u0645\u0646 9 \u0623\u0631\u0642\u0627\u0645 \u0628\u0627\u0644\u0636\u0628\u0637.",
  "photo_label": "\u0635\u0648\u0631\u0629 \u0627\u0644\u0639\u0645\u064a\u0644 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "photo_hint": "\u064a\u064f\u0641\u0636\u0651\u0644 \u0635\u064a\u063a JPG \u0623\u0648 PNG. \u0627\u0644\u062d\u062f \u0627\u0644\u0623\u0642\u0635\u0649 \u0644\u0644\u062d\u062c\u0645 5 \u0645\u064a\u063a\u0627\u0628\u0627\u062a.",
  "save_button": "\u062d\u0641\u0638 \u0627\u0644\u0637\u0644\u0628",
  "cancel_button": "\u0625\u0644\u063a\u0627\u0621",
  "err_name": "\u0627\u0644\u0627\u0633\u0645 \u064a\u062c\u0628 \u0623\u0646 \u064a\u062a\u0643\u0648\u0646 \u0645\u0646 \u062d\u0631\u0648\u0641 \u0639\u0631\u0628\u064a\u0629 \u0623\u0648 \u0625\u0646\u062c\u0644\u064a\u0632\u064a\u0629 \u0641\u0642\u0637.",
  "err_surname": "\u0627\u0644\u0644\u0642\u0628 \u064a\u062c\u0628 \u0623\u0646 \u064a\u062d\u062a\u0648\u064a \u0639\u0644\u0649 \u062d\u0631\u0648\u0641 \u0641\u0642\u0637.",
  "err_phone": "\u0623\u062f\u062e\u0644 \u0631\u0642\u0645\u0627\u064b \u0635\u062d\u064a\u062d\u0627\u064b \u0645\u0646 8 \u0625\u0644\u0649 15 \u062e\u0627\u0646\u0629 \u0628\u062f\u0648\u0646 \u0631\u0645\u0648\u0632 \u0623\u0648 \u0645\u0633\u0627\u0641\u0627\u062a.",
  "err_passport": "\u0631\u0642\u0645 \u062c\u0648\u0627\u0632 \u0627\u0644\u0633\u0641\u0631 \u064a\u062c\u0628 \u0623\u0646 \u064a\u062a\u0643\u0648\u0646 \u0645\u0646 9 \u0623\u0631\u0642\u0627\u0645 \u0628\u0627\u0644\u0636\u0628\u0637.",
  "err_car": "\u064a\u0631\u062c\u0649 \u0627\u062e\u062a\u064a\u0627\u0631 \u0627\u0644\u0633\u064a\u0627\u0631\u0629.",
  "err_gearbox": "\u0627\u062e\u062a\u0631 \u0646\u0648\u0639 \u0639\u0644\u0628\u0629 \u0627\u0644\u0633\u0631\u0639\u0629 (\u064a\u062f\u0648\u064a \u0623\u0648 \u0623\u0648\u062a\u0648\u0645\u0627\u062a\u064a\u0643).",
  "err_shipping": "\u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646 \u0627\u0644\u0645\u062e\u062a\u0627\u0631\u0629 \u063a\u064a\u0631 \u0635\u0627\u0644\u062d\u0629.",
  "err_photo": "\u0635\u064a\u063a\u0629 \u0627\u0644\u0635\u0648\u0631\u0629 \u063a\u064a\u0631 \u0645\u062f\u0639\u0648\u0645\u0629. \u0627\u0644\u0645\u0633\u0645\u0648\u062d JPG \u0623\u0648 PNG \u0623\u0648 WEBP."
}', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager_name = trim($_POST['manager_name'] ?? '');
    $car_id = (int)($_POST['car_id'] ?? 0);
    $gearbox = $_POST['gearbox'] ?? '';
    $shipping_company_id = (int)($_POST['shipping_company_id'] ?? 0);
    $container_code = trim($_POST['container_code'] ?? '');
    if ($container_code !== '') {
        $container_code = substr($container_code, 0, 255);
    }
    $client_name = trim($_POST['client_name'] ?? '');
    $client_surname = trim($_POST['client_surname'] ?? '');
    $client_dob = $_POST['client_dob'] ?? '';
    $client_phone = trim($_POST['client_phone'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $client_address = trim($_POST['client_address'] ?? '');
    $client_passport = trim($_POST['client_passport'] ?? '');

    if ($client_name === '' || !preg_match('/^[\p{L}\s\'\-]{2,}$/u', $client_name)) {
        $errors['client_name'] = $text['err_name'];
    }
    if ($client_surname === '' || !preg_match('/^[\p{L}\s\'\-]{2,}$/u', $client_surname)) {
        $errors['client_surname'] = $text['err_surname'];
    }
    if ($client_phone === '' || !preg_match('/^\d{8,15}$/', $client_phone)) {
        $errors['client_phone'] = $text['err_phone'];
    }
    if ($client_passport === '' || !preg_match('/^\d{9}$/', $client_passport)) {
        $errors['client_passport'] = $text['err_passport'];
    }
    if ($car_id <= 0 || !isset($carsById[$car_id])) {
        $errors['car_id'] = $text['err_car'];
    }

    if (empty($errors)) {
        $car = $carsById[$car_id];
        $hasManual = (float)($car['price_manual'] ?? 0) > 0;
        $hasAutomatic = (float)($car['price_automatic'] ?? 0) > 0;

        if ($hasManual && $hasAutomatic) {
            if ($gearbox !== 'manual' && $gearbox !== 'automatic') {
                $errors['gearbox'] = $text['err_gearbox'];
            }
        } elseif ($hasManual) {
            $gearbox = 'manual';
        } elseif ($hasAutomatic) {
            $gearbox = 'automatic';
        } else {
            $gearbox = null;
        }
    }

    if ($shipping_company_id > 0 && !in_array($shipping_company_id, array_column($companies, 'id'))) {
        $errors['shipping_company_id'] = $text['err_shipping'];
    }

    if (empty($errors) && !empty($_FILES['client_photo']['name'])) {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['client_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $client_photo = time() . '_' . uniqid('', true) . '.' . $ext;
            move_uploaded_file($_FILES['client_photo']['tmp_name'], $uploadDir . '/' . $client_photo);
        } else {
            $errors['client_photo'] = $text['err_photo'];
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare('INSERT INTO orders (
            car_id, shipping_company_id, container_code, gearbox, manager_name, client_name, client_surname, client_dob,
            client_phone, client_email, client_address, client_passport, client_photo, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->execute([
            $car_id,
            $shipping_company_id ?: null,
            $container_code !== '' ? $container_code : null,
            $gearbox ?: null,
            $manager_name,
            $client_name,
            $client_surname,
            $client_dob,
            $client_phone,
            $client_email ?: null,
            $client_address ?: null,
            $client_passport,
            $client_photo,
            date('c')
        ]);

        header('Location: orders.php?created=' . (int)$db->lastInsertId());
        exit;
    }
}

$page_title = $text['page_title'];
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 pb-0">
        <h4 class="card-title mb-3"><?= $text['page_title'] ?></h4>
      </div>
      <div class="card-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><?= $text['alert_title'] ?></strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
          <div class="mb-3">
            <label class="form-label"><?= $text['manager_label'] ?></label>
            <input type="text" class="form-control" name="manager_name" value="<?= htmlspecialchars($manager_name) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['car_label'] ?></label>
            <select class="form-select <?= isset($errors['car_id']) ? 'is-invalid' : '' ?>" name="car_id" required>
              <option value=""><?= $text['car_placeholder'] ?></option>
              <?php foreach ($cars as $car): ?>
                <option value="<?= (int)$car['id'] ?>" <?= $car_id == $car['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars(trim(($car['brand'] ?? '') . ' ' . $car['name'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['car_id'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['car_id']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3" id="gearbox-block">
            <label class="form-label"><?= $text['gearbox_label'] ?></label>
            <select id="gearbox" class="form-select <?= isset($errors['gearbox']) ? 'is-invalid' : '' ?>" name="gearbox"></select>
            <?php if (isset($errors['gearbox'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['gearbox']) ?></div>
            <?php endif; ?>
            <div class="form-text text-muted small" id="gearboxHelp"></div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['shipping_label'] ?></label>
            <select class="form-select <?= isset($errors['shipping_company_id']) ? 'is-invalid' : '' ?>" name="shipping_company_id">
              <option value=""><?= $text['shipping_placeholder'] ?></option>
              <?php foreach ($companies as $company): ?>
                <option value="<?= (int)$company['id'] ?>" <?= $shipping_company_id == $company['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($company['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['shipping_company_id'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['shipping_company_id']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['container_label'] ?></label>
            <input type="text" class="form-control" name="container_code" value="<?= htmlspecialchars($container_code) ?>" placeholder="<?= $text['container_placeholder'] ?>">
          </div>

          <hr class="my-4">

          <h5 class="mb-3"><?= $text['customer_section'] ?></h5>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label"><?= $text['name_label'] ?></label>
              <input type="text" class="form-control <?= isset($errors['client_name']) ? 'is-invalid' : '' ?>" name="client_name" value="<?= htmlspecialchars($client_name) ?>" placeholder="<?= $text['name_placeholder'] ?>" required>
              <div class="form-text text-muted small"><?= $text['name_hint'] ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= $text['surname_label'] ?></label>
              <input type="text" class="form-control <?= isset($errors['client_surname']) ? 'is-invalid' : '' ?>" name="client_surname" value="<?= htmlspecialchars($client_surname) ?>" placeholder="<?= $text['surname_placeholder'] ?>" required>
              <div class="form-text text-muted small"><?= $text['surname_hint'] ?></div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['dob_label'] ?></label>
            <input type="date" class="form-control" name="client_dob" value="<?= htmlspecialchars($client_dob) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['phone_label'] ?></label>
            <input type="tel" class="form-control <?= isset($errors['client_phone']) ? 'is-invalid' : '' ?>" name="client_phone" value="<?= htmlspecialchars($client_phone) ?>" placeholder="<?= $text['phone_placeholder'] ?>" inputmode="numeric" required>
            <?php if (isset($errors['client_phone'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['client_phone']) ?></div>
            <?php endif; ?>
            <div class="form-text text-muted small"><?= $text['phone_hint'] ?></div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['email_label'] ?></label>
            <input type="email" class="form-control" name="client_email" value="<?= htmlspecialchars($client_email) ?>" placeholder="example@email.com">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['address_label'] ?></label>
            <input type="text" class="form-control" name="client_address" value="<?= htmlspecialchars($client_address) ?>" placeholder="<?= $text['address_placeholder'] ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['passport_label'] ?></label>
            <input type="text" class="form-control <?= isset($errors['client_passport']) ? 'is-invalid' : '' ?>" name="client_passport" value="<?= htmlspecialchars($client_passport) ?>" inputmode="numeric" maxlength="9" minlength="9" placeholder="<?= $text['passport_placeholder'] ?>" required>
            <?php if (isset($errors['client_passport'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['client_passport']) ?></div>
            <?php endif; ?>
            <div class="form-text text-muted small"><?= $text['passport_hint'] ?></div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['photo_label'] ?></label>
            <input class="form-control" type="file" name="client_photo" accept="image/*">
            <div class="form-text text-muted small"><?= $text['photo_hint'] ?></div>
          </div>

          <div class="d-flex gap-2 pt-2">
            <button type="submit" class="btn btn-primary px-4"><?= $text['save_button'] ?></button>
            <a href="dashboard.php" class="btn btn-outline-secondary px-4"><?= $text['cancel_button'] ?></a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const cars = <?= json_encode($gearboxMap, JSON_UNESCAPED_UNICODE) ?>;
  const carSelect = document.querySelector('select[name="car_id"]');
  const gearboxSelect = document.getElementById('gearbox');
  const help = document.getElementById('gearboxHelp');

  const hints = <?= json_encode([
    'both' => $text['gearbox_hint_both'],
    'manual' => $text['gearbox_hint_manual'],
    'auto' => $text['gearbox_hint_auto'],
    'none' => $text['gearbox_hint_none'],
  ], JSON_UNESCAPED_UNICODE) ?>;

  const labels = <?= json_encode([
    'placeholder' => $text['car_placeholder'],
    'manual' => 'يدوي',
    'auto' => 'أوتوماتيك',
    'notAvailable' => 'غير متاح',
  ], JSON_UNESCAPED_UNICODE) ?>;

  function rebuildGearbox() {
    const id = parseInt(carSelect.value || '0', 10);
    const caps = cars[id] || { manual: false, automatic: false };
    gearboxSelect.innerHTML = '';

    if (caps.manual && caps.automatic) {
      gearboxSelect.disabled = false;
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="">${labels.placeholder}</option>`);
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="manual">${labels.manual}</option>`);
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="automatic">${labels.auto}</option>`);
      help.textContent = hints.both;
    } else if (caps.manual) {
      gearboxSelect.disabled = true;
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="manual" selected>${labels.manual}</option>`);
      help.textContent = hints.manual;
    } else if (caps.automatic) {
      gearboxSelect.disabled = true;
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="automatic" selected>${labels.auto}</option>`);
      help.textContent = hints.auto;
    } else {
      gearboxSelect.disabled = true;
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="">${labels.notAvailable}</option>`);
      help.textContent = hints.none;
    }
  }

  if (carSelect && gearboxSelect) {
    carSelect.addEventListener('change', rebuildGearbox);
    rebuildGearbox();
  }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
