<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$db = get_db();

// Ensure extra images table exists (first-visit safety)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS car_images (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      car_id INTEGER NOT NULL,
      image TEXT NOT NULL,
      created_at TEXT,
      FOREIGN KEY(car_id) REFERENCES cars(id) ON DELETE CASCADE
    )");
} catch (Throwable $e) {}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: cars.php');
    exit;
}

// Load car
$stmt = $db->prepare('SELECT * FROM cars WHERE id = ?');
$stmt->execute([$id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$car) {
    header('Location: cars.php');
    exit;
}

$msg = '';
$err = '';

// Handle delete of a single extra image
if (!empty($_GET['delete_image'])) {
    $imgId = (int)$_GET['delete_image'];
    $s = $db->prepare('SELECT car_id, image FROM car_images WHERE id = ?');
    $s->execute([$imgId]);
    if ($row = $s->fetch(PDO::FETCH_ASSOC)) {
        $file = (string)($row['image'] ?? '');
        if ($file && file_exists(__DIR__ . '/../uploads/' . $file)) {
            @unlink(__DIR__ . '/../uploads/' . $file);
        }
        $db->prepare('DELETE FROM car_images WHERE id = ?')->execute([$imgId]);
    }
    header('Location: cars_edit.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $body_type = trim($_POST['body_type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $engine = trim($_POST['engine'] ?? '');
    $price_manual = (isset($_POST['price_manual']) && $_POST['price_manual'] !== '') ? (float)$_POST['price_manual'] : null;
    $price_automatic = (isset($_POST['price_automatic']) && $_POST['price_automatic'] !== '') ? (float)$_POST['price_automatic'] : null;
    $customs_price = (isset($_POST['customs_price']) && $_POST['customs_price'] !== '') ? (float)$_POST['customs_price'] : null;

    if ($name === '' || $brand === '' || $body_type === '' || $color === '' || $engine === '') {
        $err = 'يرجى ملء جميع الحقول الأساسية';
    } elseif ($price_manual === null || $price_automatic === null || $customs_price === null || $price_manual < 0 || $price_automatic < 0 || $customs_price < 0) {
        $err = 'الأسعار غير صالحة';
    } else {
        $imgName = null;
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $fileType = @mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($fileType, $allowedTypes, true)) {
                $err = 'صيغة الصورة غير مدعومة (JPG/PNG/WEBP)';
            } else {
                if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0777, true); }
                $imgName = time() . '_' . basename($_FILES['image']['name']);
                if (!@move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/' . $imgName)) {
                    $err = 'تعذر رفع الصورة';
                }
            }
        }

        if ($err === '') {
            $old = $db->prepare('SELECT image FROM cars WHERE id = ?');
            $old->execute([$id]);
            $oldImg = $old->fetchColumn();

            $stmtU = $db->prepare('UPDATE cars SET name = ?, brand = ?, body_type = ?, color = ?, engine = ?, price_manual = ?, price_automatic = ?, customs_price = ?, image = ? WHERE id = ?');
            $stmtU->execute([$name, $brand, $body_type, $color, $engine, $price_manual, $price_automatic, $customs_price, $imgName ?: $oldImg, $id]);

            if ($imgName && $oldImg && $oldImg !== $imgName) {
                @unlink(__DIR__ . '/../uploads/' . $oldImg);
            }

            // Extra images
            if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    $n = $_FILES['images']['name'][$i] ?? '';
                    $t = $_FILES['images']['tmp_name'][$i] ?? '';
                    if ($n === '' || $t === '' || !is_file($t)) continue;
                    $ft = @mime_content_type($t);
                    if (!in_array($ft, $allowedTypes, true)) continue;
                    if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0777, true); }
                    $extraName = time() . '_' . $i . '_' . basename($n);
                    if (move_uploaded_file($t, __DIR__ . '/../uploads/' . $extraName)) {
                        $db->prepare('INSERT INTO car_images (car_id, image, created_at) VALUES (?, ?, ?)')->execute([$id, $extraName, date('c')]);
                    }
                }
            }

            header('Location: cars.php');
            exit;
        }
    }
}

$page_title = 'تعديل سيارة';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">تعديل سيارة</h5>
        <a href="cars.php" class="btn btn-sm btn-secondary">رجوع</a>
      </div>
      <div class="card-body">
        <?php if ($err): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($err) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">اسم السيارة</label>
              <input class="form-control" name="name" value="<?= htmlspecialchars($car['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">العلامة التجارية</label>
              <input class="form-control" name="brand" value="<?= htmlspecialchars($car['brand'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">نوع الهيكل</label>
              <input class="form-control" name="body_type" value="<?= htmlspecialchars($car['body_type'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">اللون</label>
              <input class="form-control" name="color" value="<?= htmlspecialchars($car['color'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">المحرك</label>
              <input class="form-control" name="engine" value="<?= htmlspecialchars($car['engine'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">سعر اليدوي (DZD)</label>
              <input class="form-control" type="number" name="price_manual" step="0.01" min="0" value="<?= htmlspecialchars((string)($car['price_manual'] ?? '')) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">سعر الأوتوماتيك (DZD)</label>
              <input class="form-control" type="number" name="price_automatic" step="0.01" min="0" value="<?= htmlspecialchars((string)($car['price_automatic'] ?? '')) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">سعر الجمركة (DZD)</label>
              <input class="form-control" type="number" name="customs_price" step="0.01" min="0" value="<?= htmlspecialchars((string)($car['customs_price'] ?? '')) ?>" required>
            </div>
          </div>

          <div>
            <label class="form-label">الصورة الرئيسية</label>
            <input class="form-control" type="file" name="image" accept="image/*">
            <?php if (!empty($car['image']) && file_exists(__DIR__ . '/../uploads/' . $car['image'])): ?>
              <div class="mt-2">
                <span class="d-block text-muted small mb-1">الصورة الحالية</span>
                <img src="<?= BASE_URL ?>/uploads/<?= urlencode($car['image']) ?>" style="height:60px; border-radius:4px; object-fit:cover;">
              </div>
            <?php endif; ?>
          </div>

          <div>
            <label class="form-label">صور إضافية (اختياري)</label>
            <input class="form-control" type="file" name="images[]" accept="image/*" multiple>
            <?php
              $extras = $db->prepare('SELECT id, image FROM car_images WHERE car_id = ? ORDER BY id DESC');
              $extras->execute([$id]);
              $extraRows = $extras->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (!empty($extraRows)): ?>
              <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach ($extraRows as $ex): ?>
                  <?php if (!empty($ex['image']) && file_exists(__DIR__ . '/../uploads/' . $ex['image'])): ?>
                    <div class="position-relative" style="width:48px;height:48px;">
                      <img src="<?= BASE_URL ?>/uploads/<?= urlencode($ex['image']) ?>" style="height:48px; width:48px; border-radius:4px; object-fit:cover; border:1px solid #ddd;">
                      <a href="cars_edit.php?id=<?= (int)$id ?>&delete_image=<?= (int)$ex['id'] ?>" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0" style="line-height:1;padding:0 6px;" onclick="return confirm('حذف هذه الصورة؟')">&times;</a>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">حفظ التعديلات</button>
            <a href="cars.php" class="btn btn-secondary">رجوع</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

