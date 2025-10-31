<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$db = get_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: orders.php');
    exit;
}

// Load cars and shipping companies
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
$companyIds = array_map(static function ($r) { return (int)$r['id']; }, $companies);

// Fetch existing order
$stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    header('Location: orders.php');
    exit;
}

// Initialize form variables from DB
$manager_name = (string)($order['manager_name'] ?? '');
$car_id = (int)($order['car_id'] ?? 0);
$gearbox = (string)($order['gearbox'] ?? '');
$shipping_company_id = (int)($order['shipping_company_id'] ?? 0);
$container_code = (string)($order['container_code'] ?? '');
$client_name = (string)($order['client_name'] ?? '');
$client_surname = (string)($order['client_surname'] ?? '');
$client_dob = (string)($order['client_dob'] ?? '');
$client_phone = (string)($order['client_phone'] ?? '');
$client_email = (string)($order['client_email'] ?? '');
$client_address = (string)($order['client_address'] ?? '');
$client_passport = (string)($order['client_passport'] ?? '');
$client_photo = (string)($order['client_photo'] ?? '');

$errors = [];

$text = json_decode('{
  "page_title": "\u062a\u0639\u062f\u064a\u0644 \u0637\u0644\u0628",
  "alert_title": "\u0627\u0644\u0631\u062c\u0627\u0621 \u062a\u0635\u062d\u064a\u062d \u0627\u0644\u0623\u062e\u0637\u0627\u0621 \u0627\u0644\u062a\u0627\u0644\u064a\u0629:",
  "manager_label": "\u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u064a\u0631",
  "car_label": "\u0646\u0648\u0639 \u0627\u0644\u0633\u064a\u0627\u0631\u0629",
  "car_placeholder": "\u0627\u062e\u062a\u0631 \u0627\u0644\u0633\u064a\u0627\u0631\u0629...",
  "gearbox_label": "\u0646\u0648\u0639 \u0639\u0644\u0628\u0629 \u0627\u0644\u0633\u0631\u0639\u0629",
  "gearbox_hint_both": "\u0627\u062e\u062a\u0631 \u0627\u0644\u0646\u0638\u0627\u0645 \u0627\u0644\u0645\u0646\u0627\u0633\u0628 \u062d\u0633\u0628 \u0631\u063a\u0628\u0629 \u0627\u0644\u0632\u0628\u0648\u0646.",
  "gearbox_hint_manual": "\u064a\u062f\u0648\u064a (\u0645\u062a\u0648\u0641\u0631 \u0641\u0642\u0637)",
  "gearbox_hint_auto": "\u0623\u062a\u0648\u0645\u0627\u062a\u064a\u0643 (\u0645\u062a\u0648\u0641\u0631 \u0641\u0642\u0637)",
  "gearbox_hint_none": "\u0644\u0627 \u062a\u0648\u062c\u062f \u0623\u0633\u0639\u0627\u0631 \u0645\u0633\u062c\u0644\u0629 \u0644\u0647\u0630\u0647 \u0627\u0644\u0633\u064a\u0627\u0631\u0629.",
  "shipping_label": "\u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646",
  "shipping_placeholder": "\u2014 \u0627\u062e\u062a\u0631 \u0634\u0631\u0643\u0629 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a) \u2014",
  "container_label": "\u0631\u0642\u0645 \u0627\u0644\u062d\u0627\u0648\u064a\u0629 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "container_placeholder": "CMAU1234567",
  "customer_section": "\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0639\u0645\u064a\u0644",
  "name_label": "\u0627\u0644\u0627\u0633\u0645",
  "name_placeholder": "\u0645\u062b\u0627\u0644: \u0623\u062d\u0645\u062f",
  "surname_label": "\u0627\u0644\u0644\u0642\u0628",
  "dob_label": "\u062a\u0627\u0631\u064a\u062e \u0627\u0644\u0645\u064a\u0644\u0627\u062f",
  "phone_label": "\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062a\u0641",
  "email_label": "\u0627\u0644\u0628\u0631\u064a\u062f \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "address_label": "\u0639\u0646\u0648\u0627\u0646 \u0627\u0644\u0632\u0628\u0648\u0646",
  "passport_label": "\u0631\u0642\u0645 \u062c\u0648\u0627\u0632 \u0627\u0644\u0633\u0641\u0631",
  "passport_placeholder": "A123456789",
  "photo_label": "\u0635\u0648\u0631\u0629 \u0627\u0644\u0639\u0645\u064a\u0644 (\u0627\u062e\u062a\u064a\u0627\u0631\u064a)",
  "photo_hint": "JPG/PNG/WEBP فقط، بحد أقصى معقول.",
  "save_button": "\u062d\u0641\u0638 \u0627\u0644\u062a\u0639\u062f\u064a\u0644\u0627\u062a",
  "cancel_button": "\u0625\u0644\u063a\u0627\u0621",
  "err_name": "\u0627\u0644\u0627\u0633\u0645 \u064a\u062c\u0628 \u0623\u0646 \u064a\u062a\u0643\u0648\u0646 \u0645\u0646 \u062d\u0631\u0648\u0641 \u0641\u0642\u0637.",
  "err_surname": "\u0627\u0644\u0644\u0642\u0628 \u064a\u062c\u0628 \u0623\u0646 \u064a\u062d\u062a\u0648\u064a \u0639\u0644\u0649 \u062d\u0631\u0648\u0641 \u0641\u0642\u0637.",
  "err_phone": "\u0623\u062f\u062e\u0644 \u0631\u0642\u0645\u0627\u064b \u0635\u062d\u064a\u062d\u0627\u064b \u0645\u0646 8 \u0625\u0644\u0649 15 \u062e\u0627\u0646\u0629.",
  "err_passport": "\u0631\u0642\u0645 \u0627\u0644\u062c\u0648\u0627\u0632 \u064a\u062c\u0628 \u0623\u0646 \u064a\u062a\u0643\u0648\u0646 \u0645\u0646 9 \u0623\u0631\u0642\u0627\u0645.",
  "err_car": "\u064a\u0631\u062c\u0649 \u0627\u062e\u062a\u064a\u0627\u0631 \u0627\u0644\u0633\u064a\u0627\u0631\u0629.",
  "err_gearbox": "\u0627\u062e\u062a\u0631 \u0646\u0648\u0639 \u0639\u0644\u0628\u0629 \u0627\u0644\u0633\u0631\u0639\u0629.",
  "err_shipping": "\u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646 \u063a\u064a\u0631 \u0635\u0627\u0644\u062d\u0629.",
  "err_photo": "\u0635\u064a\u063a\u0629 \u0627\u0644\u0635\u0648\u0631\u0629 \u063a\u064a\u0631 \u0645\u062f\u0639\u0648\u0645\u0629."
}', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager_name = trim($_POST['manager_name'] ?? '');
    $car_id = (int)($_POST['car_id'] ?? 0);
    $gearbox = trim((string)($_POST['gearbox'] ?? ''));
    $shipping_company_id = (int)($_POST['shipping_company_id'] ?? 0);
    $container_code = trim((string)($_POST['container_code'] ?? ''));
    $client_name = trim((string)($_POST['client_name'] ?? ''));
    $client_surname = trim((string)($_POST['client_surname'] ?? ''));
    $client_dob = trim((string)($_POST['client_dob'] ?? ''));
    $client_phone = trim((string)($_POST['client_phone'] ?? ''));
    $client_email = trim((string)($_POST['client_email'] ?? ''));
    $client_address = trim((string)($_POST['client_address'] ?? ''));
    $client_passport = trim((string)($_POST['client_passport'] ?? ''));

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

    if ($shipping_company_id > 0 && !in_array($shipping_company_id, $companyIds, true)) {
        $errors['shipping_company_id'] = $text['err_shipping'];
    }

    // Handle optional photo replacement
    $newPhoto = null;
    if (empty($errors) && isset($_FILES['client_photo']) && !empty($_FILES['client_photo']['name'])) {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower((string)pathinfo($_FILES['client_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $newPhoto = time() . '_' . uniqid('', true) . '.' . $ext;
            @move_uploaded_file($_FILES['client_photo']['tmp_name'], $uploadDir . '/' . $newPhoto);
        } else {
            $errors['client_photo'] = $text['err_photo'];
        }
    }

    if (empty($errors)) {
        $shippingValue = $shipping_company_id ?: null;
        $containerValue = $container_code !== '' ? $container_code : null;

        $sql = 'UPDATE orders SET car_id = ?, gearbox = ?, manager_name = ?, client_name = ?, client_surname = ?, client_dob = ?, client_phone = ?, client_email = ?, client_address = ?, client_passport = ?, shipping_company_id = ?, container_code = ?';
        $params = [
            $car_id,
            $gearbox ?: null,
            $manager_name,
            $client_name,
            $client_surname,
            $client_dob,
            $client_phone,
            $client_email !== '' ? $client_email : null,
            $client_address !== '' ? $client_address : null,
            $client_passport,
            $shippingValue,
            $containerValue,
        ];
        if ($newPhoto !== null) {
            $sql .= ', client_photo = ?';
            $params[] = $newPhoto;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Delete old photo if replaced
        if ($newPhoto !== null && !empty($client_photo)) {
            $oldPath = __DIR__ . '/../uploads/' . $client_photo;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
            $client_photo = $newPhoto;
        }

        header('Location: orders.php?updated=' . $id);
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
        <h4 class="card-title mb-3"><?= $text['page_title'] ?> #<?= (int)$id ?></h4>
      </div>
      <div class="card-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><?= $text['alert_title'] ?></strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars((string)$error) ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label"><?= $text['manager_label'] ?></label>
            <input type="text" class="form-control" name="manager_name" value="<?= htmlspecialchars($manager_name) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['car_label'] ?></label>
            <select class="form-select <?= isset($errors['car_id']) ? 'is-invalid' : '' ?>" name="car_id" required>
              <option value="0"><?= $text['car_placeholder'] ?></option>
              <?php foreach ($cars as $car): ?>
                <option value="<?= (int)$car['id'] ?>" <?= ((int)$car['id'] === (int)$car_id) ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)($car['brand'] ?? '')) ?> - <?= htmlspecialchars((string)$car['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['car_id'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['car_id']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['gearbox_label'] ?></label>
            <select class="form-select" id="gearbox" name="gearbox"></select>
            <div id="gearboxHelp" class="form-text text-muted small"></div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['shipping_label'] ?></label>
            <select class="form-select" name="shipping_company_id">
              <option value="0"><?= $text['shipping_placeholder'] ?></option>
              <?php foreach ($companies as $company): ?>
                <option value="<?= (int)$company['id'] ?>" <?= ((int)$shipping_company_id === (int)$company['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($company['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['container_label'] ?></label>
            <input type="text" class="form-control" name="container_code" value="<?= htmlspecialchars($container_code) ?>" placeholder="<?= $text['container_placeholder'] ?>">
          </div>

          <hr>
          <h6 class="mb-3"><?= $text['customer_section'] ?></h6>

          <div class="mb-3">
            <label class="form-label"><?= $text['name_label'] ?></label>
            <input type="text" class="form-control <?= isset($errors['client_name']) ? 'is-invalid' : '' ?>" name="client_name" value="<?= htmlspecialchars($client_name) ?>" placeholder="<?= $text['name_placeholder'] ?>" required>
            <?php if (isset($errors['client_name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['client_name']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['surname_label'] ?></label>
            <input type="text" class="form-control <?= isset($errors['client_surname']) ? 'is-invalid' : '' ?>" name="client_surname" value="<?= htmlspecialchars($client_surname) ?>" required>
            <?php if (isset($errors['client_surname'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['client_surname']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['dob_label'] ?></label>
            <input type="date" class="form-control" name="client_dob" value="<?= htmlspecialchars($client_dob) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['phone_label'] ?></label>
            <input type="tel" class="form-control <?= isset($errors['client_phone']) ? 'is-invalid' : '' ?>" name="client_phone" value="<?= htmlspecialchars($client_phone) ?>" inputmode="numeric" required>
            <?php if (isset($errors['client_phone'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['client_phone']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['email_label'] ?></label>
            <input type="email" class="form-control" name="client_email" value="<?= htmlspecialchars($client_email) ?>" placeholder="example@email.com">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['address_label'] ?></label>
            <input type="text" class="form-control" name="client_address" value="<?= htmlspecialchars($client_address) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['passport_label'] ?></label>
            <input type="text" class="form-control <?= isset($errors['client_passport']) ? 'is-invalid' : '' ?>" name="client_passport" value="<?= htmlspecialchars($client_passport) ?>" inputmode="numeric" maxlength="9" minlength="9" placeholder="<?= $text['passport_placeholder'] ?>" required>
            <?php if (isset($errors['client_passport'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['client_passport']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['photo_label'] ?></label>
            <?php if (!empty($client_photo) && file_exists(__DIR__ . '/../uploads/' . $client_photo)): ?>
              <div class="mb-2">
                <img src="<?= BASE_URL ?>/uploads/<?= urlencode($client_photo) ?>" alt="photo" class="img-thumbnail" style="max-width: 140px; max-height: 140px; object-fit: cover;">
              </div>
            <?php endif; ?>
            <input class="form-control" type="file" name="client_photo" accept="image/*">
            <div class="form-text text-muted small"><?= $text['photo_hint'] ?></div>
          </div>

          <div class="d-flex gap-2 pt-2">
            <button type="submit" class="btn btn-primary px-4"><?= $text['save_button'] ?></button>
            <a href="orders.php" class="btn btn-outline-secondary px-4"><?= $text['cancel_button'] ?></a>
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
    'manual' => 'يدوية',
    'auto' => 'أتوماتيكية',
    'notAvailable' => 'غير متوفرة',
  ], JSON_UNESCAPED_UNICODE) ?>;

  function rebuildGearbox() {
    const id = parseInt(carSelect.value || '0', 10);
    const caps = cars[id] || { manual: false, automatic: false };
    const current = <?= json_encode($gearbox, JSON_UNESCAPED_UNICODE) ?>;
    gearboxSelect.innerHTML = '';

    if (caps.manual && caps.automatic) {
      gearboxSelect.disabled = false;
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="">${labels.placeholder}</option>`);
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="manual" ${current === 'manual' ? 'selected' : ''}>${labels.manual}</option>`);
      gearboxSelect.insertAdjacentHTML('beforeend', `<option value="automatic" ${current === 'automatic' ? 'selected' : ''}>${labels.auto}</option>`);
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

