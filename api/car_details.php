<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/html; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('<div class="p-4 text-danger">طلب غير صالح</div>');
}

$db = get_db();

$carStmt = $db->prepare('SELECT * FROM cars WHERE id = ?');
$carStmt->execute([$id]);
$car = $carStmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    http_response_code(404);
    exit('<div class="p-4 text-danger">السيارة غير موجودة</div>');
}

$imgRows = $db->prepare('SELECT image FROM car_images WHERE car_id = ? ORDER BY id DESC');
$imgRows->execute([$id]);
$extraImages = $imgRows->fetchAll(PDO::FETCH_ASSOC);

$settings = $db->query('SELECT company_phone, company_email, company_address FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];

$name = htmlspecialchars(trim(($car['brand'] ?? '') . ' ' . ($car['name'] ?? '')));
$body = htmlspecialchars((string)($car['body_type'] ?? ''));
$color = htmlspecialchars((string)($car['color'] ?? ''));
$engine = htmlspecialchars((string)($car['engine'] ?? ''));
$priceM = (float)($car['price_manual'] ?? 0);
$priceA = (float)($car['price_automatic'] ?? 0);
$priceC = (float)($car['customs_price'] ?? 0);

function fmt(float $v): string { return $v > 0 ? number_format($v, 2, ',', ' ') . ' DZD' : 'غير متوفر'; }

$mainImg = (!empty($car['image']) && file_exists(__DIR__ . '/../uploads/' . $car['image']))
  ? (BASE_URL . '/uploads/' . rawurlencode((string)$car['image'])) : '';

?>
<style>
  .amount { display:inline-block; direction:ltr; unicode-bidi:bidi-override; text-align:right; white-space:nowrap; font-variant-numeric: tabular-nums; }
</style>
<div class="container-fluid">
  <div class="row g-3">
    <div class="col-md-5">
      <div class="border rounded p-2 bg-light" style="min-height:200px; display:flex; align-items:center; justify-content:center;">
        <?php if ($mainImg): ?>
          <img src="<?= htmlspecialchars($mainImg, ENT_QUOTES, 'UTF-8') ?>" alt="car" class="img-fluid" style="max-height:260px; object-fit:contain; cursor: zoom-in;" data-lightbox>
        <?php else: ?>
          <div class="text-muted">بدون صورة</div>
        <?php endif; ?>
      </div>
      <?php if (!empty($extraImages)): ?>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <?php foreach ($extraImages as $ex): $f = (string)($ex['image'] ?? ''); if ($f && file_exists(__DIR__ . '/../uploads/' . $f)): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= urlencode($f) ?>" style="height:56px; width:56px; object-fit:cover; border-radius:4px; border:1px solid #ddd; cursor: zoom-in;" data-lightbox>
          <?php endif; endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-7">
      <h5 class="mb-2"><?= $name ?></h5>
      <div class="text-muted small mb-2">نوع الهيكل: <?= $body ?> • اللون: <?= $color ?> • المحرك: <?= $engine ?></div>
      <div class="row g-2">
        <div class="col-sm-6"><div class="border rounded p-2">سعر اليدوي: <strong class="amount"><?= fmt($priceM) ?></strong></div></div>
        <div class="col-sm-6"><div class="border rounded p-2">سعر الأوتوماتيك: <strong class="amount"><?= fmt($priceA) ?></strong></div></div>
        <div class="col-sm-6"><div class="border rounded p-2">سعر الجمركة: <strong class="amount"><?= fmt($priceC) ?></strong></div></div>
      </div>
      <hr>
      <h6 class="mb-2">معلومات الاتصال</h6>
      <div class="text-muted">
        <?php if (!empty($settings['company_phone'])): ?><div>الهاتف: <?= htmlspecialchars((string)$settings['company_phone']) ?></div><?php endif; ?>
        <?php if (!empty($settings['company_email'])): ?><div>البريد: <?= htmlspecialchars((string)$settings['company_email']) ?></div><?php endif; ?>
        <?php if (!empty($settings['company_address'])): ?><div>العنوان: <?= htmlspecialchars((string)$settings['company_address']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
