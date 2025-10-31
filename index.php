<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$db = get_db();

$cars = $db->query('SELECT * FROM cars ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

$orders = $db->query('
    SELECT o.*, c.name AS car_name
    FROM orders o
    LEFT JOIN cars c ON c.id = o.car_id
    ORDER BY o.created_at DESC
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

$text = json_decode('{
  "page_title": "\u0627\u0644\u0631\u0626\u064a\u0633\u064a\u0629",
  "recent_orders_title": "\u0623\u062d\u062f\u062b \u0627\u0644\u0637\u0644\u0628\u0627\u062a",
  "recent_orders_empty_icon": "\uD83D\uDCCB",
  "recent_orders_empty_text": "\u0644\u0627 \u062a\u0648\u062c\u062f \u0637\u0644\u0628\u0627\u062a \u062d\u062f\u064a\u062b\u0629 \u062d\u062a\u0649 \u0627\u0644\u0622\u0646.",
  "recent_orders_headers": {
    "id": "#",
    "car": "\u0627\u0644\u0633\u064a\u0627\u0631\u0629",
    "customer": "\u0627\u0644\u0639\u0645\u064a\u0644",
    "status": "\u0627\u0644\u062d\u0627\u0644\u0629"
  },
  "label_phone": "\u0627\u0644\u0647\u0627\u062a\u0641",
  "status_paid": "\u0645\u062f\u0641\u0648\u0639",
  "status_unpaid": "\u063a\u064a\u0631 \u0645\u062f\u0641\u0648\u0639",
  "status_shipped": "\u062a\u0645 \u0627\u0644\u0634\u062d\u0646",
  "status_not_shipped": "\u062f\u0648\u0646 \u0634\u062d\u0646",
  "recent_cars_title": "\u0623\u062d\u062f\u062b \u0627\u0644\u0633\u064a\u0627\u0631\u0627\u062a",
  "recent_cars_empty_icon": "\uD83D\uDE97",
  "recent_cars_empty_text": "\u0644\u0627 \u062a\u0648\u062c\u062f \u0633\u064a\u0627\u0631\u0627\u062a \u0645\u0636\u0627\u0641\u0629 \u062d\u062f\u064a\u062b\u0627\u064b.",
  "image_placeholder": "\u0628\u062f\u0648\u0646 \u0635\u0648\u0631\u0629",
  "price_manual": "\u0633\u0639\u0631 \u0627\u0644\u0646\u0638\u0627\u0645 \u0627\u0644\u064a\u062f\u0648\u064a",
  "price_automatic": "\u0633\u0639\u0631 \u0627\u0644\u0646\u0638\u0627\u0645 \u0627\u0644\u0623\u062a\u0648\u0645\u0627\u062a\u064a\u0643\u064a",
  "price_customs": "\u0633\u0639\u0631 \u0627\u0644\u062c\u0645\u0631\u0643\u0629",
  "price_not_available": "\u063a\u064a\u0631 \u0645\u062a\u0648\u0641\u0631",
  "currency": "DZD"
}', true);

$page_title = $text['page_title'];
require __DIR__ . '/includes/header.php';
$is_admin = !empty($_SESSION['admin_id']);
?>

<div class="row g-4">
  <?php if ($is_admin): ?>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 pb-0">
        <h4 class="card-title mb-3"><?= $text['recent_orders_title'] ?></h4>
      </div>
      <div class="card-body">
        <?php if (empty($orders)): ?>
          <div class="text-center py-4">
            <div class="fs-1 mb-2"><?= $text['recent_orders_empty_icon'] ?></div>
            <p class="text-muted mb-0"><?= $text['recent_orders_empty_text'] ?></p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col"><?= $text['recent_orders_headers']['id'] ?></th>
                  <th scope="col"><?= $text['recent_orders_headers']['car'] ?></th>
                  <th scope="col"><?= $text['recent_orders_headers']['customer'] ?></th>
                  <th scope="col"><?= $text['recent_orders_headers']['status'] ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td class="fw-bold"><?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['car_name'] ?? '-') ?></td>
                    <td>
                      <div><?= htmlspecialchars(trim($o['client_name'] . ' ' . $o['client_surname'])) ?></div>
                      <div class="text-muted small"><?= $text['label_phone'] ?>: <?= htmlspecialchars($o['client_phone']) ?></div>
                    </td>
                    <td>
                      <div class="d-flex flex-column gap-1">
                        <span class="badge <?= $o['paid'] ? 'bg-success' : 'bg-secondary' ?>">
                          <?= $o['paid'] ? $text['status_paid'] : $text['status_unpaid'] ?>
                        </span>
                        <span class="badge <?= $o['shipped'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                          <?= $o['shipped'] ? $text['status_shipped'] : $text['status_not_shipped'] ?>
                        </span>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-lg-<?= $is_admin ? '6' : '12' ?>">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 pb-0">
        <h4 class="card-title mb-3"><?= $text['recent_cars_title'] ?></h4>
      </div>
      <div class="card-body">
        <?php if (empty($cars)): ?>
          <div class="text-center py-4">
            <div class="fs-1 mb-2"><?= $text['recent_cars_empty_icon'] ?></div>
            <p class="text-muted mb-0"><?= $text['recent_cars_empty_text'] ?></p>
          </div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($cars as $car): ?>
              <div class="col-md-6">
                <div class="card border h-100 shadow-none">
                  <div class="card-img-top overflow-hidden" style="height: 200px; background: #f8fafc;">
                    <?php if (!empty($car['image']) && file_exists(__DIR__ . '/uploads/' . $car['image'])): ?>
                      <img src="<?= BASE_URL ?>/uploads/<?= urlencode($car['image']) ?>?v=<?= time() ?>"
                           alt="<?= htmlspecialchars($car['name']) ?>"
                           class="w-100 h-100 object-fit-contain"
                           loading="lazy">
                    <?php else: ?>
                      <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <span><?= $text['image_placeholder'] ?></span>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="card-body p-3">
                    <h6 class="mb-2"><?= htmlspecialchars(trim(($car['brand'] ?? '') . ' ' . $car['name'])) ?></h6>
                    <?php
                      $manualPrice = (float)($car['price_manual'] ?? 0);
                      $automaticPrice = (float)($car['price_automatic'] ?? 0);
                      $customsPrice = (float)($car['customs_price'] ?? 0);
                    ?>
                    <div class="text-muted small d-flex flex-column gap-1">
                      <div><?= $text['price_manual'] ?>:
                        <span class="price">
                          <?= $manualPrice > 0 ? $text['currency'] . ' ' . number_format($manualPrice, 2, ',', ' ') : $text['price_not_available'] ?>
                        </span>
                      </div>
                      <div><?= $text['price_automatic'] ?>:
                        <span class="price">
                          <?= $automaticPrice > 0 ? $text['currency'] . ' ' . number_format($automaticPrice, 2, ',', ' ') : $text['price_not_available'] ?>
                        </span>
                      </div>
                      <div><?= $text['price_customs'] ?>:
                        <span class="price">
                          <?= $customsPrice > 0 ? $text['currency'] . ' ' . number_format($customsPrice, 2, ',', ' ') : $text['price_not_available'] ?>
                        </span>
                      </div>
                    </div>
                    <div class="mt-2 d-flex justify-content-end">
                      <button class="btn btn-sm btn-outline-primary" onclick="openCarDetails(<?= (int)$car['id'] ?>)">المزيد</button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Car details modal -->
<div class="modal fade" id="carModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تفاصيل السيارة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="carContent" style="min-height:300px;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
  
</div>

<script>
function openCarDetails(id) {
  const modalEl = document.getElementById('carModal');
  const contentEl = document.getElementById('carContent');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  contentEl.innerHTML = '<div class="text-center text-muted py-5">جاري التحميل...</div>';
  modal.show();
  fetch('<?= BASE_URL ?>/api/car_details.php?id=' + encodeURIComponent(id))
    .then(r => r.text())
    .then(html => contentEl.innerHTML = html)
    .catch(() => contentEl.innerHTML = '<div class="text-center text-danger py-5">تعذر تحميل التفاصيل</div>');
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
