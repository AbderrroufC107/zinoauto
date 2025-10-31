<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT o.*, c.name AS car_name, c.brand AS car_brand, c.body_type AS car_body_type, c.color AS car_color, c.engine AS car_engine, c.image AS car_image FROM orders o LEFT JOIN cars c ON c.id = o.car_id WHERE o.id = ?');
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

$text = [
  'page_title_prefix' => 'Reçu #',
  'not_found' => 'Commande introuvable.',
  'receipt_title' => 'Reçu de paiement',
  'order_number' => 'Commande',
  'car_name' => 'Modèle',
  'car_brand' => 'Marque',
  'car_body_type' => 'Type de carrosserie',
  'car_color' => 'Couleur',
  'car_engine' => 'Moteur',
  'customer_section' => 'Client',
  'customer_birthdate' => 'Date de naissance',
  'customer_phone' => 'Téléphone',
  'customer_email' => 'Email',
  'customer_passport' => 'Passeport',
  'customer_photo' => 'Photo du client',
  'manager_label' => 'Préparé par',
  'created_at' => 'Créé le',
  'status_paid' => 'Payé',
  'status_shipped' => 'Expédié',
  'status_not_paid' => 'Non payé',
  'status_not_shipped' => 'Non expédié',
  'button_print' => 'Imprimer'
];

if (!$o) {
    echo htmlspecialchars($text['not_found']);
    exit;
}

$page_title = $text['page_title_prefix'] . $o['id'];
require __DIR__ . '/includes/header.php';
?>
<div dir="ltr" class="card text-start" style="text-align:left;">
  <div class="card-body">
    <h3 class="card-title mb-3 fs-2 fw-bold"><?= $text['receipt_title'] ?></h3>
    <p><strong><?= $text['order_number'] ?>:</strong> <?= (int)$o['id'] ?></p>
    <p><strong><?= $text['car_brand'] ?>:</strong> <?= htmlspecialchars((string)($o['car_brand'] ?? '-')) ?></p>
    <p><strong><?= $text['car_name'] ?>:</strong> <?= htmlspecialchars((string)($o['car_name'] ?? '-')) ?></p>
    <p><strong><?= $text['car_body_type'] ?>:</strong> <?= htmlspecialchars((string)($o['car_body_type'] ?? '-')) ?></p>
    <p><strong><?= $text['car_color'] ?>:</strong> <?= htmlspecialchars((string)($o['car_color'] ?? '-')) ?></p>
    <p><strong><?= $text['car_engine'] ?>:</strong> <?= htmlspecialchars((string)($o['car_engine'] ?? '-')) ?></p>
    <?php if (!empty($o['car_image']) && file_exists(__DIR__ . '/uploads/' . $o['car_image'])): ?>
      <img src="<?= BASE_URL ?>/uploads/<?= urlencode((string)$o['car_image']) ?>" style="height:120px; object-fit:cover;" alt="car" class="mb-3 rounded">
    <?php endif; ?>

    <h5 class="mt-4 mb-2"><?= $text['customer_section'] ?></h5>
    <p><?= htmlspecialchars(trim((string)($o['client_name'] ?? '') . ' ' . (string)($o['client_surname'] ?? ''))) ?></p>
    <p><?= $text['customer_birthdate'] ?>: <?= htmlspecialchars((string)($o['client_dob'] ?? '')) ?></p>
    <p><?= $text['customer_phone'] ?>: <?= htmlspecialchars((string)($o['client_phone'] ?? '')) ?></p>
    <p><?= $text['customer_email'] ?>: <?= htmlspecialchars((string)($o['client_email'] ?? '')) ?></p>
    <p><?= $text['customer_passport'] ?>: <?= htmlspecialchars((string)($o['client_passport'] ?? '')) ?></p>
    <?php if (!empty($o['client_photo']) && file_exists(__DIR__ . '/uploads/' . $o['client_photo'])): ?>
      <p><?= $text['customer_photo'] ?>:<br><img src="<?= BASE_URL ?>/uploads/<?= urlencode((string)$o['client_photo']) ?>" style="height:120px; object-fit:cover;" alt="client" class="rounded"></p>
    <?php endif; ?>

    <p><?= $text['manager_label'] ?>: <?= htmlspecialchars((string)($o['manager_name'] ?? '')) ?></p>
    <p><?= $text['created_at'] ?>: <?= htmlspecialchars((string)($o['created_at'] ?? '')) ?></p>
    <p><?= $text['status_paid'] ?>: <?= !empty($o['paid']) ? $text['status_paid'] : $text['status_not_paid'] ?></p>
    <p><?= $text['status_shipped'] ?>: <?= !empty($o['shipped']) ? $text['status_shipped'] : $text['status_not_shipped'] ?></p>

    <p class="mt-4"><button class="btn btn-secondary" onclick="window.print()">
      <?= $text['button_print'] ?>
    </button></p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
