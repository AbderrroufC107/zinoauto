<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
$db = get_db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare(
    'SELECT o.*, 
            c.name AS car_name,
            c.brand AS car_brand,
            c.body_type AS car_body_type,
            c.color AS car_color,
            c.engine AS car_engine,
            c.image AS car_image
     FROM orders o 
     LEFT JOIN cars c ON c.id = o.car_id 
     WHERE o.id = ?'
);
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

$text = [
  'page_title_prefix' => 'Facture #',
  'not_found' => 'Commande introuvable.',
  'invoice_title' => 'Facture',
  'order_number' => 'Commande',
  'print_timestamp' => 'Imprimé le',
  'unpaid_warning' => "Avertissement: paiement non confirmé. Merci de régler le solde.",
  'customer_section' => 'Client',
  'customer_full_name' => 'Nom complet',
  'customer_birthdate' => 'Date de naissance',
  'customer_phone' => 'Téléphone',
  'customer_email' => 'Email',
  'customer_passport' => 'Passeport',
  'car_section' => 'Véhicule',
  'car_name' => 'Modèle',
  'car_brand' => 'Marque',
  'car_body_type' => 'Type de carrosserie',
  'car_color' => 'Couleur',
  'car_engine' => 'Moteur',
  'manager_label' => 'Préparé par',
  'created_at' => 'Créé le',
  'status_summary' => 'Statuts',
  'status_paid' => 'Payé',
  'status_shipped' => 'Expédié',
  'status_not_paid' => 'Non payé',
  'status_not_shipped' => 'Non expédié',
  'yes' => 'Oui',
  'no' => 'Non',
  'button_print' => 'Imprimer la facture',
  'button_back' => 'Retour aux commandes',
];

if (!$o) {
    echo htmlspecialchars($text['not_found']);
    exit;
}

$page_title = $text['page_title_prefix'] . $o['id'];
require __DIR__ . '/includes/header.php';
?>
<div dir="ltr" class="text-start" style="text-align:left;">
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h3 class="m-0 fs-2 fw-bold"><?= $text['invoice_title'] ?></h3>
        <div class="text-muted"><?= $text['order_number'] ?>: <?= (int)$o['id'] ?></div>
      </div>
      <div class="text-end">
        <div class="fw-bold" style="font-size:22px;">Zino Auto</div>
        <div class="text-muted"><?= $text['print_timestamp'] ?>: <?= date('Y-m-d H:i') ?></div>
      </div>
    </div>

    <?php if (empty($o['paid'])): ?>
      <div class="alert alert-warning"><?= $text['unpaid_warning'] ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="fw-bold mb-2"><?= $text['customer_section'] ?></div>
          <div><?= $text['customer_full_name'] ?>: <?= htmlspecialchars(trim(($o['client_name'] ?? '') . ' ' . ($o['client_surname'] ?? ''))) ?></div>
          <div><?= $text['customer_birthdate'] ?>: <?= htmlspecialchars((string)($o['client_dob'] ?? '')) ?></div>
          <div><?= $text['customer_phone'] ?>: <?= htmlspecialchars((string)($o['client_phone'] ?? '')) ?></div>
          <div><?= $text['customer_email'] ?>: <?= htmlspecialchars((string)($o['client_email'] ?? '')) ?></div>
          <div><?= $text['customer_passport'] ?>: <?= htmlspecialchars((string)($o['client_passport'] ?? '')) ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="fw-bold mb-2"><?= $text['car_section'] ?></div>
          <div><?= $text['car_brand'] ?>: <?= htmlspecialchars((string)($o['car_brand'] ?? '-')) ?></div>
          <div><?= $text['car_name'] ?>: <?= htmlspecialchars((string)($o['car_name'] ?? '-')) ?></div>
          <div><?= $text['car_body_type'] ?>: <?= htmlspecialchars((string)($o['car_body_type'] ?? '-')) ?></div>
          <div><?= $text['car_color'] ?>: <?= htmlspecialchars((string)($o['car_color'] ?? '-')) ?></div>
          <div><?= $text['car_engine'] ?>: <?= htmlspecialchars((string)($o['car_engine'] ?? '-')) ?></div>
          <?php if (!empty($o['car_image']) && file_exists(__DIR__ . '/uploads/' . $o['car_image'])): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= urlencode((string)$o['car_image']) ?>" style="height:120px;object-fit:cover" alt="car" class="mt-3 rounded">
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <div class="fw-bold mb-1"><?= $text['manager_label'] ?>:</div>
      <div><?= htmlspecialchars((string)($o['manager_name'] ?? '')) ?></div>
      <div class="text-muted mt-2"><?= $text['created_at'] ?>: <?= htmlspecialchars((string)($o['created_at'] ?? '')) ?></div>
      <div class="text-muted mt-1">
        <?= $text['status_summary'] ?>:
        <?= $text['status_paid'] ?> = <?= !empty($o['paid']) ? $text['yes'] : $text['no'] ?>
        <?= $text['status_shipped'] ?> = <?= !empty($o['shipped']) ? $text['yes'] : $text['no'] ?>
      </div>
    </div>
  </div>
  <div class="card-footer d-print-none d-flex gap-2 justify-content-end">
    <button class="btn btn-primary" onclick="window.print()"><?= $text['button_print'] ?></button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/orders.php"><?= $text['button_back'] ?></a>
  </div>
</div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
