<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

admin_only();
$db = get_db();

$columns = [
    'paid_at TEXT',
    'shipped_at TEXT',
    'received INTEGER DEFAULT 0',
    'received_at TEXT',
    'container_code TEXT'
];
foreach ($columns as $column) {
    try {
        $db->exec("ALTER TABLE orders ADD COLUMN $column");
    } catch (Throwable $e) {
        // Column already exists; ignore error
    }
}

$text = json_decode('{
  "page_title": "\u0625\u062f\u0627\u0631\u0629 \u0627\u0644\u0637\u0644\u0628\u0627\u062a",
  "created_success": "\u062a\u0645 \u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0637\u0644\u0628 \u0628\u0646\u062c\u0627\u062d.",
  "updated_success": "\u062a\u0645 \u062d\u0641\u0638 \u0627\u0644\u062a\u0639\u062f\u064a\u0644\u0627\u062a \u0628\u0646\u062c\u0627\u062d.",
  "search_label": "\u0628\u062d\u062b \u0633\u0631\u064a\u0639 \u0641\u064a \u0627\u0644\u0637\u0644\u0628\u0627\u062a",
  "search_placeholder": "\u0627\u0628\u062d\u062b \u0628\u0627\u0633\u0645 \u0627\u0644\u0639\u0645\u064a\u0644 \u0623\u0648 \u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062a\u0641 \u0623\u0648 \u0627\u0644\u0633\u064a\u0627\u0631\u0629...",
  "search_hint": "\u064a\u062a\u0645 \u062a\u062d\u062f\u064a\u062b \u0627\u0644\u0646\u062a\u0627\u0626\u062c \u0645\u0628\u0627\u0634\u0631\u0629 \u0623\u062b\u0646\u0627\u0621 \u0627\u0644\u0643\u062a\u0627\u0628\u0629.",
  "search_icon": "\uD83D\uDD0D",
  "search_clear": "\u0645\u0633\u062D",
  "loading": "\u062c\u0627\u0631\u064a \u062a\u062d\u0645\u064a\u0644 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a...",
  "load_error": "\u062d\u062f\u062b \u062e\u0637\u0623 \u0623\u062b\u0646\u0627\u0621 \u062a\u062d\u0645\u064a\u0644 \u0627\u0644\u0637\u0644\u0628\u0627\u062a.",
  "table_empty": "\u0644\u0627 \u062a\u0648\u062c\u062f \u0637\u0644\u0628\u0627\u062a \u0645\u0633\u062c\u0644\u0629.",
  "table_headers": {
    "id": "#",
    "car": "\u0627\u0644\u0633\u064a\u0627\u0631\u0629",
    "customer": "\u0627\u0644\u0639\u0645\u064a\u0644",
    "manager": "\u0627\u0644\u0645\u0633\u064a\u0631",
    "status": "\u062d\u0627\u0644\u0629 \u0627\u0644\u0637\u0644\u0628",
    "actions": "\u0625\u062c\u0631\u0627\u0621\u0627\u062a"
  },
  "status_paid": "\u0645\u062f\u0641\u0648\u0639",
  "status_unpaid": "\u063a\u064a\u0631 \u0645\u062f\u0641\u0648\u0639",
  "status_shipped": "\u062a\u0645 \u0627\u0644\u0634\u062d\u0646",
  "status_not_shipped": "\u0644\u0645 \u064a\u0634\u062d\u0646",
  "status_received": "\u0645\u0633\u062a\u0644\u0645",
  "status_not_received": "\u062f\u0648\u0646 \u0627\u0633\u062a\u0644\u0627\u0645",
  "label_phone": "\u0627\u0644\u0647\u0627\u062a\u0641:",
  "label_address": "\u0627\u0644\u0639\u0646\u0648\u0627\u0646:",
  "label_email": "\u0627\u0644\u0628\u0631\u064a\u062f:",
  "label_shipping": "\u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646",
  "label_container": "\u0631\u0642\u0645 \u0627\u0644\u062d\u0627\u0648\u064a\u0629",
  "shipping_none": "\u0628\u062f\u0648\u0646 \u0634\u0631\u0643\u0629 \u0634\u062d\u0646",
  "container_placeholder": "CMAU1234567",
  "button_more": "\u0627\u0644\u0645\u0632\u064a\u062f",
  "dropdown_title": "\u0625\u0639\u062f\u0627\u062f\u0627\u062a \u0625\u0636\u0627\u0641\u064a\u0629",
  "dropdown_save": "\u062d\u0641\u0638",
  "button_receipt": "\u0648\u0635\u0644",
  "button_invoice": "\u0641\u0627\u062a\u0648\u0631\u0629",
  "button_receive": "\u0627\u0633\u062a\u0644\u0627\u0645",
  "button_delete": "\u062d\u0630\u0641",
  "confirm_delete": "\u0647\u0644 \u0623\u0646\u062a \u0645\u062a\u0623\u0643\u062f \u0645\u0646 \u062d\u0630\u0641 \u0647\u0630\u0627 \u0627\u0644\u0637\u0644\u0628\u061f",
  "confirm_receive": "\u062a\u0623\u0643\u064a\u062f \u0627\u0633\u062a\u0644\u0627\u0645 \u0627\u0644\u0637\u0644\u0628\u061f",
  "print_title": "\u0637\u0628\u0627\u0639\u0629",
  "back_button": "\u0627\u0644\u0639\u0648\u062f\u0629 \u0625\u0644\u0649 \u0644\u0648\u062d\u0629 \u0627\u0644\u062a\u062d\u0643\u0645",
  "gearbox_label": "\u0646\u0648\u0639 \u0639\u0644\u0628\u0629 \u0627\u0644\u0633\u0631\u0639\u0629",
  "gearbox_manual": "\u064a\u062f\u0648\u064a\u0629",
  "gearbox_automatic": "\u0623\u062a\u0648\u0645\u0627\u062a\u064a\u0643\u064a\u0629",
  "edit_manager_title": "\u062d\u0641\u0638 \u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u064a\u0631",
  "edit_manager_submit": "\u062d\u0641\u0638",
  "modal_close": "\u0625\u063a\u0644\u0627\u0642"
}', true);

$shippingCompanies = $db->query('SELECT id, name FROM shipping_companies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$shippingCompanyMap = [];
foreach ($shippingCompanies as $company) {
    $shippingCompanyMap[(int)$company['id']] = $company['name'];
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare('SELECT client_photo FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $photo = $row['client_photo'] ?? null;
        if ($photo) {
            $path = __DIR__ . '/../uploads/' . $photo;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
    $db->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
    header('Location: orders.php');
    exit;
}

if (isset($_GET['toggle_paid'])) {
    $id = (int)$_GET['toggle_paid'];
    $stmt = $db->prepare('SELECT paid FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $paid = (int)$stmt->fetchColumn();
    if ($paid === 1) {
        $db->prepare('UPDATE orders SET paid = 0, paid_at = NULL WHERE id = ?')->execute([$id]);
    } else {
        $db->prepare('UPDATE orders SET paid = 1, paid_at = ? WHERE id = ?')->execute([date('c'), $id]);
    }
    header('Location: orders.php');
    exit;
}

if (isset($_GET['toggle_shipped'])) {
    $id = (int)$_GET['toggle_shipped'];
    $stmt = $db->prepare('SELECT shipped FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $shipped = (int)$stmt->fetchColumn();
    if ($shipped === 1) {
        $db->prepare('UPDATE orders SET shipped = 0, shipped_at = NULL WHERE id = ?')->execute([$id]);
    } else {
        $db->prepare('UPDATE orders SET shipped = 1, shipped_at = ? WHERE id = ?')->execute([date('c'), $id]);
    }
    header('Location: orders.php');
    exit;
}

if (isset($_GET['mark_received'])) {
    $id = (int)$_GET['mark_received'];
    $db->prepare('UPDATE orders SET received = 1, received_at = ? WHERE id = ?')->execute([date('c'), $id]);
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_logistics'])) {
    $id = (int)($_POST['order_id'] ?? 0);
    $shippingId = (int)($_POST['shipping_company_id'] ?? 0);
    $containerCode = trim($_POST['container_code'] ?? '');
    if ($containerCode !== '') {
        $containerCode = substr($containerCode, 0, 255);
    }
    if ($id > 0) {
        $shippingValue = $shippingId > 0 && isset($shippingCompanyMap[$shippingId]) ? $shippingId : null;
        $containerValue = $containerCode !== '' ? $containerCode : null;
        $stmt = $db->prepare('UPDATE orders SET shipping_company_id = ?, container_code = ? WHERE id = ?');
        $stmt->execute([$shippingValue, $containerValue, $id]);
    }
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_manager'])) {
    $id = (int)($_POST['order_id'] ?? 0);
    $manager = trim($_POST['manager_name'] ?? '');
    if ($id > 0 && $manager !== '') {
        $db->prepare('UPDATE orders SET manager_name = ? WHERE id = ?')->execute([$manager, $id]);
    }
    header('Location: orders.php');
    exit;
}

$orders = $db->query('
    SELECT o.*, c.name AS car_name, c.brand AS car_brand, sc.name AS shipping_company_name
    FROM orders o
    LEFT JOIN cars c ON c.id = o.car_id
    LEFT JOIN shipping_companies sc ON sc.id = o.shipping_company_id
    ORDER BY o.created_at DESC
')->fetchAll(PDO::FETCH_ASSOC);

$page_title = $text['page_title'];
require __DIR__ . '/../includes/header.php';
?>

<div class="row">
  <div class="col-12">
    <?php if (!empty($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $text['created_success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $text['updated_success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label"><?= $text['search_label'] ?></label>
          <div class="input-group">
            <span class="input-group-text"><?= $text['search_icon'] ?></span>
            <input type="text" id="liveSearch" class="form-control" placeholder="<?= $text['search_placeholder'] ?>">
            <button class="btn btn-outline-secondary" type="button" id="clearSearch"><?= $text['search_clear'] ?></button>
          </div>
          <div class="form-text text-muted small"><?= $text['search_hint'] ?></div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div id="loading" class="d-none text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="mt-2 text-muted"><?= $text['loading'] ?></div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?= $text['table_headers']['id'] ?></th>
                <th><?= $text['table_headers']['car'] ?></th>
                <th><?= $text['table_headers']['customer'] ?></th>
                <th><?= $text['table_headers']['manager'] ?></th>
                <th><?= $text['table_headers']['status'] ?></th>
                <th><?= $text['table_headers']['actions'] ?></th>
              </tr>
            </thead>
            <tbody id="orders-table-container">
              <?php if (empty($orders)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted"><?= $text['table_empty'] ?></td>
                </tr>
              <?php else: ?>
                <?php foreach ($orders as $order): ?>
                  <tr data-received="<?= (int)$order['received'] ?>">
                    <td class="fw-bold"><?= $order['id'] ?></td>
                    <td>
                      <?= htmlspecialchars(trim(($order['car_brand'] ?? '') . ' ' . ($order['car_name'] ?? '-'))) ?>
                      <?php if ($order['gearbox'] === 'manual'): ?>
                        <div class="small text-muted"><?= $text['gearbox_label'] ?>: <?= $text['gearbox_manual'] ?></div>
                      <?php elseif ($order['gearbox'] === 'automatic'): ?>
                        <div class="small text-muted"><?= $text['gearbox_label'] ?>: <?= $text['gearbox_automatic'] ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= htmlspecialchars(trim($order['client_name'] . ' ' . $order['client_surname'])) ?>
                      <div class="text-muted small"><?= $text['label_phone'] ?> <?= htmlspecialchars($order['client_phone']) ?></div>
                      <?php if (!empty($order['client_address'])): ?>
                        <div class="text-muted small"><?= $text['label_address'] ?> <?= htmlspecialchars($order['client_address']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($order['client_email'])): ?>
                        <div class="text-muted small"><?= $text['label_email'] ?> <?= htmlspecialchars($order['client_email']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="post" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="edit_manager" value="1">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input class="form-control form-control-sm" name="manager_name" value="<?= htmlspecialchars($order['manager_name']) ?>" style="max-width: 150px" required>
                        <button class="btn btn-sm btn-outline-primary" type="submit" title="<?= $text['edit_manager_title'] ?>"><?= $text['edit_manager_submit'] ?></button>
                      </form>
                    </td>
                    <td>
                      <div class="d-flex flex-column gap-1">
                        <a href="?toggle_paid=<?= $order['id'] ?>" class="badge <?= $order['paid'] ? 'bg-success' : 'bg-secondary' ?>">
                          <?= $order['paid'] ? $text['status_paid'] : $text['status_unpaid'] ?>
                        </a>
                        <a href="?toggle_shipped=<?= $order['id'] ?>" class="badge <?= $order['shipped'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                          <?= $order['shipped'] ? $text['status_shipped'] : $text['status_not_shipped'] ?>
                        </a>
                        <span class="badge <?= $order['received'] ? 'bg-primary' : 'bg-secondary' ?>">
                          <?= $order['received'] ? $text['status_received'] : $text['status_not_received'] ?>
                        </span>
                        <?php if (!empty($order['shipping_company_name'])): ?>
                          <div class="small text-muted"><?= $text['label_shipping'] ?>: <?= htmlspecialchars($order['shipping_company_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($order['container_code'])): ?>
                          <div class="small text-muted"><?= $text['label_container'] ?>: <?= htmlspecialchars($order['container_code']) ?></div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        <a href="edit_order.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-warning">تعديل</a>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                onclick="openMoreSettings(<?= (int)$order['id'] ?>, <?= (int)($order['shipping_company_id'] ?? 0) ?>, '<?= htmlspecialchars((string)($order['container_code'] ?? ''), ENT_QUOTES) ?>')">
                          <?= $text['button_more'] ?>
                        </button>
                        <?php $token_receipt = make_link_token('receipt', (int)$order['id']); ?>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="loadDocumentToken('<?= htmlspecialchars($token_receipt, ENT_QUOTES, 'UTF-8') ?>')" title="<?= $text['button_receipt'] ?>"><?= $text['button_receipt'] ?></button>
                        <?php if ($order['paid']): ?>
                          <?php $token_invoice = make_link_token('invoice', (int)$order['id']); ?>
                          <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadDocumentToken('<?= htmlspecialchars($token_invoice, ENT_QUOTES, 'UTF-8') ?>')" title="<?= $text['button_invoice'] ?>"><?= $text['button_invoice'] ?></button>
                        <?php endif; ?>
                        <?php if (!$order['received']): ?>
                          <a class="btn btn-sm btn-success" href="?mark_received=<?= $order['id'] ?>" onclick="return confirm('<?= $text['confirm_receive'] ?>')"><?= $text['button_receive'] ?></a>
                        <?php endif; ?>
                        <a href="?delete=<?= $order['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= $text['confirm_delete'] ?>')"><?= $text['button_delete'] ?></a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          <a href="dashboard.php" class="btn btn-secondary"><?= $text['back_button'] ?></a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let searchTimeout;
const BASE_URL = '<?= BASE_URL ?>';
const loadingText = <?= json_encode($text['loading'], JSON_UNESCAPED_UNICODE) ?>;
const errorRow = <?= json_encode('<tr><td colspan="6" class="text-center text-danger py-4">' . $text['load_error'] . '</td></tr>', JSON_UNESCAPED_UNICODE) ?>;

function fetchOrders(query) {
  const loading = document.getElementById('loading');
  const container = document.getElementById('orders-table-container');
  loading?.classList.remove('d-none');

  fetch(BASE_URL + '/api/search_orders.php?q=' + encodeURIComponent(query))
    .then(response => response.json())
    .then(data => {
      container.innerHTML = data.html;
    })
    .catch(() => {
      container.innerHTML = <?= json_encode('<tr><td colspan="6" class="text-center text-danger py-4">' . $text['load_error'] . '</td></tr>', JSON_UNESCAPED_UNICODE) ?>;
    })
    .finally(() => {
      loading?.classList.add('d-none');
    });
}

document.getElementById('liveSearch').addEventListener('input', function () {
  clearTimeout(searchTimeout);
  const query = this.value.trim();
  searchTimeout = setTimeout(() => fetchOrders(query), 400);
});

document.getElementById('clearSearch').addEventListener('click', function () {
  const input = document.getElementById('liveSearch');
  input.value = '';
  fetchOrders('');
});

function loadDocument(type, id) {
  const modalEl = document.getElementById('documentModal');
  const contentEl = document.getElementById('documentContent');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  contentEl.innerHTML = `<div class="text-center text-muted py-5">${loadingText}</div>`;
  modal.show();
  try {
    // Scroll page and modal to top for immediate visibility
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
    contentEl.scrollTop = 0;
    modalEl.scrollTop = 0;
    modalEl.addEventListener('shown.bs.modal', () => {
      contentEl.scrollTop = 0;
      modalEl.scrollTop = 0;
    }, { once: true });
  } catch (_) {}
  fetch(BASE_URL + '/api/document.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id))
    .then(r => r.text())
    .then(html => {
      contentEl.innerHTML = html;
      // Execute any <script> tags included in the fetched document so inline handlers work
      const scripts = contentEl.querySelectorAll('script');
      scripts.forEach((oldScript) => {
        const s = document.createElement('script');
        // Preserve type if provided
        if (oldScript.type) s.type = oldScript.type;
        if (oldScript.src) {
          s.src = oldScript.src;
          s.async = false;
          if (oldScript.crossOrigin) s.crossOrigin = oldScript.crossOrigin;
          if (oldScript.referrerPolicy) s.referrerPolicy = oldScript.referrerPolicy;
        } else {
          s.textContent = oldScript.textContent || '';
        }
        document.body.appendChild(s);
      });
    })
    .catch(() => contentEl.innerHTML = <?= json_encode('<div class="text-center text-danger py-5">' . $text['load_error'] . '</div>', JSON_UNESCAPED_UNICODE) ?>);
}

// New: load by signed token (no exposed id)
function loadDocumentToken(token) {
  const modalEl = document.getElementById('documentModal');
  const contentEl = document.getElementById('documentContent');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  contentEl.innerHTML = `<div class="text-center text-muted py-5">${loadingText}</div>`;
  modal.show();
  try {
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
    contentEl.scrollTop = 0;
    modalEl.scrollTop = 0;
    modalEl.addEventListener('shown.bs.modal', () => {
      contentEl.scrollTop = 0;
      modalEl.scrollTop = 0;
    }, { once: true });
  } catch (_) {}
  fetch(BASE_URL + '/api/document.php?token=' + encodeURIComponent(token))
    .then(r => r.text())
    .then(html => {
      contentEl.innerHTML = html;
      const scripts = contentEl.querySelectorAll('script');
      scripts.forEach((oldScript) => {
        const s = document.createElement('script');
        if (oldScript.type) s.type = oldScript.type;
        if (oldScript.src) {
          s.src = oldScript.src; s.async = false;
          if (oldScript.crossOrigin) s.crossOrigin = oldScript.crossOrigin;
          if (oldScript.referrerPolicy) s.referrerPolicy = oldScript.referrerPolicy;
        } else { s.textContent = oldScript.textContent || ''; }
        document.body.appendChild(s);
      });
    })
    .catch(() => contentEl.innerHTML = <?= json_encode('<div class="text-center text-danger py-5">' . $text['load_error'] . '</div>', JSON_UNESCAPED_UNICODE) ?>);
}

// Ensure dropdown "More" panel is visible without manual scrolling
function scrollDropdownIntoView(dropdownEl) {
  try {
    const toggle = dropdownEl.querySelector('[data-bs-toggle="dropdown"]') || dropdownEl;
    const navbar = document.querySelector('.navbar');
    const navH = navbar ? navbar.offsetHeight : 64;
    const rect = toggle.getBoundingClientRect();
    let targetY = window.scrollY + rect.top - navH - 12;
    if (targetY < 0) targetY = 0;
    window.scrollTo({ top: targetY, behavior: 'smooth' });
  } catch (_) {}
}

document.addEventListener('show.bs.dropdown', function (ev) {
  const dropdown = ev.target.closest('.dropdown') || ev.target;
  scrollDropdownIntoView(dropdown);
}, false);

document.addEventListener('shown.bs.dropdown', function (ev) {
  const dropdown = ev.target.closest('.dropdown') || ev.target;
  // small additional nudge after layout settles
  setTimeout(() => scrollDropdownIntoView(dropdown), 50);
}, false);

function printDocument() {
  const contentEl = document.getElementById('documentContent');
  const w = window.open('', '_blank');
  w.document.write('<html><head><title><?= $text['print_title'] ?></title>');
  w.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">');
  w.document.write('</head><body class="p-4">');
  w.document.write(contentEl.innerHTML);
  w.document.write('</body></html>');
  w.document.close();
  w.focus();
  w.print();
  w.onafterprint = () => w.close();
}

// Open the More Settings modal and prefill fields
function openMoreSettings(orderId, shippingId, containerCode) {
  try {
    const modalEl = document.getElementById('moreSettingsModal');
    const form = modalEl.querySelector('form');
    form.querySelector('input[name="order_id"]').value = orderId;
    const sel = form.querySelector('select[name="shipping_company_id"]');
    if (sel) sel.value = String(shippingId || 0);
    const input = form.querySelector('input[name="container_code"]');
    if (input) input.value = containerCode || '';
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
  } catch (e) {
    console.error(e);
  }
}
</script>

<!-- Document modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= $text['page_title'] ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="documentContent" style="min-height: 400px; max-height: calc(100vh - 200px); overflow: auto;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="printDocument()"><?= $text['print_title'] ?></button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= $text['modal_close'] ?></button>
      </div>
    </div>
</div>
</div>

<!-- More settings modal -->
<div class="modal fade" id="moreSettingsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= $text['dropdown_title'] ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <input type="hidden" name="update_logistics" value="1">
          <input type="hidden" name="order_id" value="0">
          <div class="mb-3">
            <label class="form-label small mb-1"><?= $text['label_shipping'] ?></label>
            <select class="form-select form-select-sm" name="shipping_company_id">
              <option value="0"><?= $text['shipping_none'] ?></option>
              <?php foreach ($shippingCompanies as $company): ?>
                <option value="<?= (int)$company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small mb-1"><?= $text['label_container'] ?></label>
            <input type="text" class="form-control form-control-sm" name="container_code" placeholder="<?= $text['container_placeholder'] ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $text['modal_close'] ?></button>
          <button type="submit" class="btn btn-primary"><?= $text['dropdown_save'] ?></button>
        </div>
      </form>
    </div>
  </div>
  
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
