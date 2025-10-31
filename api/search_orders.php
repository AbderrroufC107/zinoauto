<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json; charset=utf-8');

$text = json_decode('{
  "page_title": "\u0625\u062f\u0627\u0631\u0629 \u0627\u0644\u0637\u0644\u0628\u0627\u062a",
  "created_success": "\u062a\u0645 \u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0637\u0644\u0628 \u0628\u0646\u062c\u0627\u062d.",
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

$q = trim($_GET['q'] ?? '');
$db = get_db();

$shippingCompanies = $db->query('SELECT id, name FROM shipping_companies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$where = '';
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where = 'WHERE (o.client_name LIKE ? OR o.client_surname LIKE ? OR o.client_phone LIKE ? OR c.name LIKE ? OR c.brand LIKE ?)';
    $params = [$like, $like, $like, $like, $like];
}

$stmt = $db->prepare("
    SELECT o.*, c.name AS car_name, c.brand AS car_brand, sc.name AS shipping_company_name
    FROM orders o
    LEFT JOIN cars c ON c.id = o.car_id
    LEFT JOIN shipping_companies sc ON sc.id = o.shipping_company_id
    $where
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
if (empty($orders)): ?>
    <tr>
        <td colspan="6" class="text-center py-4 text-muted"><?= htmlspecialchars($text['table_empty']) ?></td>
    </tr>
<?php else:
    foreach ($orders as $order):
        $carLabel = trim((string)($order['car_brand'] ?? '') . ' ' . (string)($order['car_name'] ?? ''));
        $carLabel = $carLabel !== '' ? $carLabel : '-';
        $clientFullName = trim((string)$order['client_name'] . ' ' . (string)$order['client_surname']);
        $clientFullName = $clientFullName !== '' ? $clientFullName : '-';
?>
    <tr data-received="<?= (int)$order['received'] ?>">
        <td class="fw-bold"><?= (int)$order['id'] ?></td>
        <td>
            <?= htmlspecialchars($carLabel) ?>
            <?php if ($order['gearbox'] === 'manual'): ?>
                <div class="small text-muted"><?= $text['gearbox_label'] ?>: <?= $text['gearbox_manual'] ?></div>
            <?php elseif ($order['gearbox'] === 'automatic'): ?>
                <div class="small text-muted"><?= $text['gearbox_label'] ?>: <?= $text['gearbox_automatic'] ?></div>
            <?php endif; ?>
        </td>
        <td>
            <?= htmlspecialchars($clientFullName) ?>
            <div class="text-muted small"><?= $text['label_phone'] ?> <?= htmlspecialchars((string)$order['client_phone']) ?></div>
            <?php if (!empty($order['client_address'])): ?>
                <div class="text-muted small"><?= $text['label_address'] ?> <?= htmlspecialchars((string)$order['client_address']) ?></div>
            <?php endif; ?>
            <?php if (!empty($order['client_email'])): ?>
                <div class="text-muted small"><?= $text['label_email'] ?> <?= htmlspecialchars((string)$order['client_email']) ?></div>
            <?php endif; ?>
        </td>
        <td>
            <form method="post" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="edit_manager" value="1">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input class="form-control form-control-sm" name="manager_name" value="<?= htmlspecialchars((string)$order['manager_name']) ?>" style="max-width: 150px" required>
                <button class="btn btn-sm btn-outline-primary" type="submit" title="<?= htmlspecialchars($text['edit_manager_title']) ?>"><?= $text['edit_manager_submit'] ?></button>
            </form>
        </td>
        <td>
            <div class="d-flex flex-column gap-1">
                <a href="?toggle_paid=<?= (int)$order['id'] ?>" class="badge <?= $order['paid'] ? 'bg-success' : 'bg-secondary' ?>">
                    <?= $order['paid'] ? $text['status_paid'] : $text['status_unpaid'] ?>
                </a>
                <a href="?toggle_shipped=<?= (int)$order['id'] ?>" class="badge <?= $order['shipped'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                    <?= $order['shipped'] ? $text['status_shipped'] : $text['status_not_shipped'] ?>
                </a>
                <span class="badge <?= $order['received'] ? 'bg-primary' : 'bg-secondary' ?>">
                    <?= $order['received'] ? $text['status_received'] : $text['status_not_received'] ?>
                </span>
                <?php if (!empty($order['shipping_company_name'])): ?>
                    <div class="small text-muted"><?= $text['label_shipping'] ?>: <?= htmlspecialchars($order['shipping_company_name']) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['container_code'])): ?>
                    <div class="small text-muted"><?= $text['label_container'] ?>: <?= htmlspecialchars((string)$order['container_code']) ?></div>
                <?php endif; ?>
            </div>
        </td>
        <td>
            <div class="d-flex flex-wrap gap-1">
                <a href="edit_order.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-warning">تعديل</a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openMoreSettings(<?= (int)$order['id'] ?>, <?= (int)($order['shipping_company_id'] ?? 0) ?>, '<?= htmlspecialchars((string)($order['container_code'] ?? ''), ENT_QUOTES) ?>')">
                    <?= $text['button_more'] ?>
                </button>
                <?php $token_receipt = make_link_token('receipt', (int)$order['id']); ?>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="loadDocumentToken('<?= htmlspecialchars($token_receipt, ENT_QUOTES, 'UTF-8') ?>)" title="<?= $text['button_receipt'] ?>"><?= $text['button_receipt'] ?></button>
                <?php if ($order['paid']): ?>
                    <?php $token_invoice = make_link_token('invoice', (int)$order['id']); ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadDocumentToken('<?= htmlspecialchars($token_invoice, ENT_QUOTES, 'UTF-8') ?>)" title="<?= $text['button_invoice'] ?>"><?= $text['button_invoice'] ?></button>
                <?php endif; ?>
                <?php if (!$order['received']): ?>
                    <a class="btn btn-sm btn-success" href="?mark_received=<?= (int)$order['id'] ?>" onclick="return confirm('<?= $text['confirm_receive'] ?>')"><?= $text['button_receive'] ?></a>
                <?php endif; ?>
                <a href="?delete=<?= (int)$order['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= $text['confirm_delete'] ?>')"><?= $text['button_delete'] ?></a>
            </div>
        </td>
    </tr>
<?php
    endforeach;
endif;

$html = ob_get_clean();
echo json_encode(['html' => $html], JSON_UNESCAPED_UNICODE);
