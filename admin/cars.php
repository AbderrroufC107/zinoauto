<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$db = get_db();
$msg = '';
$err = '';

$text = json_decode('{
  "page_title": "إدارة السيارات",
  "form_title_add": "إضافة سيارة جديدة",
  "form_title_edit": "تعديل سيارة",
  "success_saved": "تمت إضافة السيارة بنجاح.",
  "success_updated": "تم تحديث بيانات السيارة بنجاح.",
  "error_required": "يرجى تعبئة جميع الحقول المطلوبة.",
  "error_price": "يرجى إدخال أسعار صحيحة لكل الخيارات.",
  "error_image_type": "نوع الملف غير مدعوم. الصيغ المسموحة: JPG، PNG، WEBP.",
  "error_upload": "تعذر رفع الصورة. حاول مجدداً.",
  "field_name": "اسم السيارة",
  "field_brand": "العلامة التجارية",
  "field_body_type": "نوع الهيكل (مثل: Sedan, SUV)",
  "field_color": "اللون",
  "field_engine": "المحرك (مثل: 2.0L بنزين)",
  "field_price_manual": "سعر العلبة اليدوية (DZD)",
  "field_price_automatic": "سعر العلبة الأوتوماتيكية (DZD)",
  "field_price_customs": "سعر الجمركة (DZD)",
  "field_image": "صورة السيارة",
  "field_existing_image": "الصورة الحالية",
  "button_save": "حفظ",
  "button_update": "تحديث",
  "button_cancel": "إلغاء",
  "list_title": "السيارات المسجلة",
  "list_empty": "لا توجد سيارات مسجلة حالياً.",
  "badge_body_type": "الهيكل",
  "badge_color": "اللون",
  "list_engine_label": "المحرك",
  "list_price_manual": "يدوي",
  "list_price_automatic": "أوتوماتيك",
  "list_price_customs": "جمركة",
  "currency": "DZD",
  "list_edit": "تعديل",
  "list_delete": "حذف",
  "list_delete_confirm": "هل تريد بالتأكيد حذف هذه السيارة؟",
  "list_back_dashboard": "العودة إلى لوحة التحكم"
}', true);

// Auto-migrate: ensure new columns exist
try {
    $cols = $db->query("PRAGMA table_info(cars)")->fetchAll(PDO::FETCH_ASSOC);
    $have = [];
    foreach ($cols as $col) {
        $have[strtolower((string)$col['name'])] = true;
    }
    foreach (['brand', 'body_type', 'color', 'engine'] as $c) {
        if (empty($have[$c])) {
            $db->exec("ALTER TABLE cars ADD COLUMN $c TEXT NOT NULL DEFAULT ''");
        }
    }
    foreach (['price_manual', 'price_automatic', 'customs_price'] as $c) {
        if (empty($have[$c])) {
            $db->exec("ALTER TABLE cars ADD COLUMN $c REAL NOT NULL DEFAULT 0");
        }
    }
    if (empty($have['image'])) {
        $db->exec("ALTER TABLE cars ADD COLUMN image TEXT");
    }
    // Create table for extra images (if not exists)
    $db->exec("CREATE TABLE IF NOT EXISTS car_images (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      car_id INTEGER NOT NULL,
      image TEXT NOT NULL,
      created_at TEXT,
      FOREIGN KEY(car_id) REFERENCES cars(id) ON DELETE CASCADE
    )");
} catch (Exception $e) {
    error_log('cars migration failed: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $body_type = trim($_POST['body_type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $engine = trim($_POST['engine'] ?? '');
    $price_manual = (isset($_POST['price_manual']) && $_POST['price_manual'] !== '') ? (float)$_POST['price_manual'] : null;
    $price_automatic = (isset($_POST['price_automatic']) && $_POST['price_automatic'] !== '') ? (float)$_POST['price_automatic'] : null;
    $customs_price = (isset($_POST['customs_price']) && $_POST['customs_price'] !== '') ? (float)$_POST['customs_price'] : null;
    $imgName = null;

    if ($name === '' || $brand === '' || $body_type === '' || $color === '' || $engine === '') {
        $err = $text['error_required'];
    } elseif ($price_manual === null || $price_automatic === null || $customs_price === null || $price_manual < 0 || $price_automatic < 0 || $customs_price < 0) {
        $err = $text['error_price'];
    } else {
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($fileType, $allowedTypes, true)) {
                $err = $text['error_image_type'];
            } else {
                if (!is_dir(UPLOAD_DIR)) {
                    @mkdir(UPLOAD_DIR, 0777, true);
                }
                $imgName = time() . '_' . basename($_FILES['image']['name']);
                if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/' . $imgName)) {
                    $err = $text['error_upload'];
                }
            }
        }
        // Main image is required when adding
        if ($err === '' && ($action !== 'edit') && empty($imgName)) {
            $err = $text['error_main_image_required'] ?? 'الصورة الرئيسية مطلوبة';
        }

        if ($err === '') {
            if ($action === 'edit' && $id > 0) {
                $old = $db->prepare('SELECT image FROM cars WHERE id = ?');
                $old->execute([$id]);
                $oldImg = $old->fetchColumn();

                $stmt = $db->prepare('UPDATE cars SET name = ?, brand = ?, body_type = ?, color = ?, engine = ?, price_manual = ?, price_automatic = ?, customs_price = ?, image = ? WHERE id = ?');
                $stmt->execute([$name, $brand, $body_type, $color, $engine, $price_manual, $price_automatic, $customs_price, $imgName ?: $oldImg, $id]);

                if ($imgName && $oldImg && $oldImg !== $imgName) {
                    @unlink(__DIR__ . '/../uploads/' . $oldImg);
                }
                // Handle extra images (edit)
                if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                        $n = $_FILES['images']['name'][$i] ?? '';
                        $t = $_FILES['images']['tmp_name'][$i] ?? '';
                        if ($n === '' || $t === '' || !is_file($t)) continue;
                        $ft = mime_content_type($t);
                        if (!in_array($ft, $allowedTypes, true)) continue;
                        if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0777, true); }
                        $extraName = time() . '_' . $i . '_' . basename($n);
                        if (move_uploaded_file($t, __DIR__ . '/../uploads/' . $extraName)) {
                            $db->prepare('INSERT INTO car_images (car_id, image, created_at) VALUES (?, ?, ?)')->execute([$id, $extraName, date('c')]);
                        }
                    }
                }

                $msg = $text['success_updated'];
            } else {
                $stmt = $db->prepare('INSERT INTO cars (name, brand, body_type, color, engine, price_manual, price_automatic, customs_price, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $brand, $body_type, $color, $engine, $price_manual, $price_automatic, $customs_price, $imgName]);
                $newId = (int)$db->lastInsertId();
                // Handle extra images (add)
                if ($newId > 0 && !empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                        $n = $_FILES['images']['name'][$i] ?? '';
                        $t = $_FILES['images']['tmp_name'][$i] ?? '';
                        if ($n === '' || $t === '' || !is_file($t)) continue;
                        $ft = mime_content_type($t);
                        if (!in_array($ft, $allowedTypes, true)) continue;
                        if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0777, true); }
                        $extraName = time() . '_' . $i . '_' . basename($n);
                        if (move_uploaded_file($t, __DIR__ . '/../uploads/' . $extraName)) {
                            $db->prepare('INSERT INTO car_images (car_id, image, created_at) VALUES (?, ?, ?)')->execute([$newId, $extraName, date('c')]);
                        }
                    }
                }
                $msg = $text['success_saved'];
            }
            header('Location: cars.php');
            exit;
        }
    }
}

// Handle delete
if (!empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare('SELECT image FROM cars WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r && !empty($r['image']) && file_exists(__DIR__ . '/../uploads/' . $r['image'])) {
        @unlink(__DIR__ . '/../uploads/' . $r['image']);
    }
    // Delete extra images
    try {
        $imgs = $db->prepare('SELECT image FROM car_images WHERE car_id = ?');
        $imgs->execute([$id]);
        foreach ($imgs->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $f = $row['image'] ?? '';
            if ($f && file_exists(__DIR__ . '/../uploads/' . $f)) { @unlink(__DIR__ . '/../uploads/' . $f); }
        }
        $db->prepare('DELETE FROM car_images WHERE car_id = ?')->execute([$id]);
    } catch (Throwable $e) {}
    $db->prepare('DELETE FROM cars WHERE id = ?')->execute([$id]);
    header('Location: cars.php');
    exit;
}

// Handle delete of a single extra image
if (!empty($_GET['delete_image'])) {
    $imgId = (int)$_GET['delete_image'];
    $stmt = $db->prepare('SELECT car_id, image FROM car_images WHERE id = ?');
    $stmt->execute([$imgId]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $carId = (int)($row['car_id'] ?? 0);
        $file = (string)($row['image'] ?? '');
        if ($file && file_exists(__DIR__ . '/../uploads/' . $file)) {
            @unlink(__DIR__ . '/../uploads/' . $file);
        }
        $db->prepare('DELETE FROM car_images WHERE id = ?')->execute([$imgId]);
        header('Location: cars.php?edit=' . $carId);
        exit;
    } else {
        header('Location: cars.php');
        exit;
    }
}

// Handle edit
$editCar = null;
if (!empty($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM cars WHERE id = ?');
    $stmt->execute([$id]);
    $editCar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$cars = $db->query('SELECT * FROM cars ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$page_title = $text['page_title'];
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <div class="col-md-6">
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

    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 pb-0">
        <h5 class="card-title mb-0"><?= $editCar ? $text['form_title_edit'] : $text['form_title_add'] ?></h5>
      </div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <input type="hidden" name="action" value="<?= $editCar ? 'edit' : 'add' ?>">
          <?php if ($editCar): ?>
            <input type="hidden" name="id" value="<?= (int)$editCar['id'] ?>">
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?= $text['field_name'] ?></label>
              <input class="form-control" name="name" value="<?= htmlspecialchars($editCar['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= $text['field_brand'] ?></label>
              <input class="form-control" name="brand" value="<?= htmlspecialchars($editCar['brand'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?= $text['field_body_type'] ?></label>
              <input class="form-control" name="body_type" value="<?= htmlspecialchars($editCar['body_type'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= $text['field_color'] ?></label>
              <input class="form-control" name="color" value="<?= htmlspecialchars($editCar['color'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label"><?= $text['field_engine'] ?></label>
              <input class="form-control" name="engine" value="<?= htmlspecialchars($editCar['engine'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label"><?= $text['field_price_manual'] ?></label>
              <input class="form-control" type="number" name="price_manual" step="0.01" min="0" value="<?= htmlspecialchars($editCar['price_manual'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?= $text['field_price_automatic'] ?></label>
              <input class="form-control" type="number" name="price_automatic" step="0.01" min="0" value="<?= htmlspecialchars($editCar['price_automatic'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?= $text['field_price_customs'] ?></label>
              <input class="form-control" type="number" name="customs_price" step="0.01" min="0" value="<?= htmlspecialchars($editCar['customs_price'] ?? '') ?>" required>
            </div>
          </div>

          <div>
            <label class="form-label"><?= $text['field_image'] ?></label>
            <input class="form-control" type="file" name="image" accept="image/*" <?= $editCar ? '' : 'required' ?>>
            <?php if ($editCar && !empty($editCar['image']) && file_exists(__DIR__ . '/../uploads/' . $editCar['image'])): ?>
              <div class="mt-2">
                <span class="d-block text-muted small mb-1"><?= $text['field_existing_image'] ?></span>
                <img src="<?= BASE_URL ?>/uploads/<?= urlencode($editCar['image']) ?>" style="height:60px; border-radius:4px; object-fit:cover;">
              </div>
            <?php endif; ?>
          </div>

          <div>
            <label class="form-label"><?= $text['field_images_extra'] ?? 'صور إضافية (اختياري)' ?></label>
            <input class="form-control" type="file" name="images[]" accept="image/*" multiple>
            <?php if ($editCar): ?>
              <?php
                $extras = $db->prepare('SELECT id, image FROM car_images WHERE car_id = ? ORDER BY id DESC');
                $extras->execute([$editCar['id']]);
                $extraRows = $extras->fetchAll(PDO::FETCH_ASSOC);
              ?>
              <?php if (!empty($extraRows)): ?>
                <div class="d-flex flex-wrap gap-2 mt-2">
                  <?php foreach ($extraRows as $ex): ?>
                    <?php if (!empty($ex['image']) && file_exists(__DIR__ . '/../uploads/' . $ex['image'])): ?>
                      <div class="position-relative" style="width:48px;height:48px;">
                        <img src="<?= BASE_URL ?>/uploads/<?= urlencode($ex['image']) ?>" style="height:48px; width:48px; border-radius:4px; object-fit:cover; border:1px solid #ddd;">
                        <a href="?edit=<?= (int)$editCar['id'] ?>&delete_image=<?= (int)$ex['id'] ?>" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0" style="line-height:1;padding:0 6px;" onclick="return confirm('حذف هذه الصورة؟')">&times;</a>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= $editCar ? $text['button_update'] : $text['button_save'] ?></button>
            <?php if ($editCar): ?>
              <a href="cars.php" class="btn btn-secondary"><?= $text['button_cancel'] ?></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0"><?= $text['list_title'] ?></h5>
      <span class="badge bg-light text-dark"><?= count($cars) ?></span>
    </div>
    <?php if (empty($cars)): ?>
      <div class="alert alert-info mb-0"><?= $text['list_empty'] ?></div>
    <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($cars as $c): ?>
          <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
              <div class="fw-semibold">
                <?= htmlspecialchars(($c['brand'] ? ($c['brand'] . ' ') : '') . $c['name']) ?>
              </div>
              <div class="mt-2 d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark"><?= $text['badge_body_type'] ?>: <?= htmlspecialchars($c['body_type'] ?? '') ?></span>
                <span class="badge bg-light text-dark"><?= $text['badge_color'] ?>: <?= htmlspecialchars($c['color'] ?? '') ?></span>
              </div>
              <div class="mt-2 small text-muted d-flex flex-column gap-1">
                <div><?= $text['list_price_manual'] ?>: <span class="price"><?= $text['currency'] ?> <?= number_format((float)($c['price_manual'] ?? 0), 2, ',', ' ') ?></span></div>
                <div><?= $text['list_price_automatic'] ?>: <span class="price"><?= $text['currency'] ?> <?= number_format((float)($c['price_automatic'] ?? 0), 2, ',', ' ') ?></span></div>
                <div><?= $text['list_price_customs'] ?>: <span class="price"><?= $text['currency'] ?> <?= number_format((float)($c['customs_price'] ?? 0), 2, ',', ' ') ?></span></div>
              </div>
              <?php if (!empty($c['engine'])): ?>
                <div class="small text-muted mt-1"><?= $text['list_engine_label'] ?>: <?= htmlspecialchars($c['engine']) ?></div>
              <?php endif; ?>
              <?php if (!empty($c['image']) && file_exists(__DIR__ . '/../uploads/' . $c['image'])): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= urlencode($c['image']) ?>" style="height:40px; border-radius:4px; object-fit:cover; margin-top:8px;">
              <?php endif; ?>
            </div>
            <div class="d-flex flex-column gap-2">
              <a class="btn btn-sm btn-outline-primary" href="cars_edit.php?id=<?= (int)$c['id'] ?>"><?= $text['list_edit'] ?></a>
              <a class="btn btn-sm btn-danger" href="?delete=<?= (int)$c['id'] ?>" onclick="return confirm('<?= $text['list_delete_confirm'] ?>')"><?= $text['list_delete'] ?></a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="mt-3">
      <a class="btn btn-secondary" href="dashboard.php"><?= $text['list_back_dashboard'] ?></a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
