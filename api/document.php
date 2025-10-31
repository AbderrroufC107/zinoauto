<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';
if ($token !== '') {
    $parsed = parse_link_token((string)$token);
    if ($parsed) {
        $type = $parsed['type'];
        $id = (int)$parsed['id'];
    } else {
        http_response_code(400);
        exit('<div style="padding:20px; font-family:Arial, sans-serif;">Lien invalide.</div>');
    }
}

if (!in_array($type, ['invoice', 'receipt'], true) || $id <= 0) {
    http_response_code(400);
    exit('<div style="padding:20px; font-family:Arial, sans-serif;">Demande invalide.</div>');
}

$db = get_db();

$stmt = $db->prepare("
    SELECT o.*,
           c.name AS car_name,
           c.brand AS car_brand,
           c.body_type AS car_body_type,
           c.color AS car_color,
           c.engine AS car_engine,
           c.price_manual,
           c.price_automatic,
           CASE
             WHEN o.gearbox = 'manual' THEN COALESCE(c.price_manual, 0)
             WHEN o.gearbox = 'automatic' THEN COALESCE(c.price_automatic, 0)
             ELSE COALESCE(c.price_manual, COALESCE(c.price_automatic, 0))
           END AS car_price,
           s.company_name,
           s.company_logo,
           s.company_phone,
           s.company_email,
           s.company_address,
           s.company_nif,
           s.company_rc,
           s.company_nis
    FROM orders o
    LEFT JOIN cars c ON c.id = o.car_id
    LEFT JOIN settings s ON s.id = 1
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit('<div style="padding:20px; font-family:Arial, sans-serif;">Commande introuvable.</div>');
}

header('Content-Type: text/html; charset=UTF-8');

$companyName    = htmlspecialchars($order['company_name'] ?? 'Zino Auto', ENT_QUOTES, 'UTF-8');
$companyPhone   = htmlspecialchars($order['company_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$companyEmail   = htmlspecialchars($order['company_email'] ?? '', ENT_QUOTES, 'UTF-8');
$companyAddress = htmlspecialchars($order['company_address'] ?? '', ENT_QUOTES, 'UTF-8');
$companyNif     = htmlspecialchars($order['company_nif'] ?? '', ENT_QUOTES, 'UTF-8');
$companyRc      = htmlspecialchars($order['company_rc'] ?? '', ENT_QUOTES, 'UTF-8');
$companyNis     = htmlspecialchars($order['company_nis'] ?? '', ENT_QUOTES, 'UTF-8');

$clientFullName = trim((string)($order['client_name'] ?? '') . ' ' . (string)($order['client_surname'] ?? ''));
$clientName     = htmlspecialchars($clientFullName !== '' ? $clientFullName : '-', ENT_QUOTES, 'UTF-8');
$clientPhone    = htmlspecialchars($order['client_phone'] ?? '', ENT_QUOTES, 'UTF-8');
$clientEmail    = htmlspecialchars($order['client_email'] ?? '', ENT_QUOTES, 'UTF-8');
$clientAddress  = htmlspecialchars($order['client_address'] ?? '', ENT_QUOTES, 'UTF-8');
$clientPassport = htmlspecialchars($order['client_passport'] ?? '', ENT_QUOTES, 'UTF-8');

$managerName = htmlspecialchars($order['manager_name'] ?? '', ENT_QUOTES, 'UTF-8');

$carDesignationRaw = trim((string)($order['car_brand'] ?? '') . ' ' . (string)($order['car_name'] ?? ''));
$carDesignation = htmlspecialchars($carDesignationRaw !== '' ? $carDesignationRaw : '-', ENT_QUOTES, 'UTF-8');

switch ($order['gearbox'] ?? '') {
    case 'manual':
        $gearboxLabel = 'Boite manuelle';
        break;
    case 'automatic':
        $gearboxLabel = 'Boite automatique';
        break;
    default:
        $gearboxLabel = 'Non precise';
        break;
}
$gearbox = htmlspecialchars($gearboxLabel, ENT_QUOTES, 'UTF-8');

$carPrice = max(0.0, (float)($order['car_price'] ?? 0));
// Business request: exclude customs from invoice/receipt totals
$totalPrice = $carPrice;

$formatAmountText = static function (float $value): string {
    return number_format($value, 2, ',', ' ') . ' DZD';
};
$formatAmountHtml = static function (float $value) use ($formatAmountText): string {
    return '<span class="amount">' . $formatAmountText($value) . '</span>';
};

$vehicleAmountText = $formatAmountText($carPrice);
$vehicleAmountHtml = $formatAmountHtml($carPrice);
$totalAmountText   = $formatAmountText($totalPrice);
$totalAmountHtml   = $formatAmountHtml($totalPrice);

$createdAt = $order['created_at'] ? date('d/m/Y', strtotime((string)$order['created_at'])) : date('d/m/Y');
$documentNumber = str_pad((string)($order['id'] ?? ''), 6, '0', STR_PAD_LEFT);

// HTML download removed: PDF only

$logoUrl = null;
if (!empty($order['company_logo'])) {
    $logoPath = __DIR__ . '/../uploads/' . $order['company_logo'];
    if (is_file($logoPath)) {
        $logoUrl = BASE_URL . '/uploads/' . rawurlencode((string)$order['company_logo']);
    }
}

$statusPaid = (bool)($order['paid'] ?? false);

// Car properties (display under vehicle details)
$carBodyType = htmlspecialchars($order['car_body_type'] ?? '-', ENT_QUOTES, 'UTF-8');
$carColor    = htmlspecialchars($order['car_color'] ?? '-', ENT_QUOTES, 'UTF-8');
$carEngine   = htmlspecialchars($order['car_engine'] ?? '-', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="utf-8">
  <title><?= $type === 'invoice' ? 'Facture #' . $documentNumber : 'Recu de paiement #' . $documentNumber ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
  <style>
    html { direction: ltr; }
    body {
      padding: 24px;
      font-family: 'Roboto', 'Times New Roman', serif;
      background: #fff;
      color: #000;
      font-size: 14px;
      direction: ltr;
      text-align: left;
      line-height: 1.6;
    }
    .doc-page {
      width: 794px; /* ~= 210mm at 96dpi */
      margin: 0 auto;
      background: #fff;
      padding: 16px 24px;
      box-sizing: border-box;
    }
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 30px;
      border-bottom: 2px solid #0d6efd;
      padding-bottom: 20px;
    }
    .logo {
      max-height: 70px;
      object-fit: contain;
    }
    .doc-title {
      text-transform: uppercase;
      letter-spacing: 1.5px;
      font-size: 34px;
      margin: 0;
      font-weight: 700;
      color: #000;
      font-family: 'Times New Roman', serif;
    }
    .brand-block {
      text-align: right;
    }
    .company-brand {
      font-size: 28px;
      font-weight: 700;
      margin-top: 8px;
      color: #0d6efd;
      font-family: 'Times New Roman', serif;
    }
    .ref-block {
      font-size: 13px;
      color: #333;
      margin-top: 10px;
    }
    .section-title {
      font-weight: 700;
      text-transform: uppercase;
      font-size: 14px;
      margin: 24px 0 12px;
      color: #0d6efd;
      padding-bottom: 6px;
      border-bottom: 1px solid #e0e0e0;
      letter-spacing: 0.5px;
    }
    .info-table {
      width: 100%;
    }
    .info-table td {
      padding: 6px 0;
      vertical-align: top;
      text-align: left;
    }
    .company-panel {
      background: #f9f9fb;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      padding: 14px;
    }
    .company-info {
      border-spacing: 0 8px;
    }
    .company-info td:first-child {
      font-weight: 700;
      color: #0d6efd;
      width: 180px;
      padding-right: 12px;
    }
    .signature-box {
      border-top: 1px solid #000;
      padding-top: 40px;
      margin-top: 50px;
      text-align: center;
      font-size: 14px;
      color: #000;
    }
    .amount-box {
      background: #f0f7ff;
      border: 1px solid #0d6efd;
      padding: 14px 20px;
      border-radius: 6px;
      font-weight: 700;
      font-size: 17px;
      margin: 20px 0;
      text-align: center;
    }
    .badge-status {
      padding: 6px 12px;
      border-radius: 4px;
      font-weight: 600;
      font-size: 13px;
    }
    .badge-paid {
      background: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #a5d6a7;
    }
    .badge-unpaid {
      background: #fff3e0;
      color: #e65100;
      border: 1px solid #ffab91;
    }
    .amount {
      display: inline-block;
      direction: ltr;
      unicode-bidi: bidi-override;
      text-align: right;
      white-space: nowrap;
      font-variant-numeric: tabular-nums;
      min-width: 130px;
      font-weight: 700;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      margin: 15px 0;
    }
    .table th,
    .table td {
      border: 1px solid #999;
      padding: 10px;
      text-align: left;
    }
    .table th {
      background-color: #f5f9ff;
      font-weight: 700;
      color: #0d6efd;
    }
    .table-responsive {
      width: 100%;
      overflow-x: auto;
    }
    /* Compact inline vehicle characteristics */
    .vehicle-brief {
      font-size: 12.5px;
      color: #555;
      margin: 6px 0 10px;
      line-height: 1.4;
      word-break: break-word;
    }
    .vehicle-brief strong { color: #0d6efd; font-weight: 700; }
    .vehicle-brief span { margin-right: 10px; }
    @media (max-width: 576px) {
      .doc-title { font-size: 26px; }
      .company-brand { font-size: 22px; }
      .amount { min-width: 100px; }
      .doc-header { flex-direction: column; align-items: flex-start; gap: 15px; }
      .brand-block { text-align: left; margin-top: 10px; }
    }
    @media print {
      body {
        padding: 0;
        background: none;
      }
      .doc-page { width: 210mm; margin: 0 auto; padding: 12mm; }
      .no-print { display: none !important; }
      .doc-header {
        border-bottom: 2px solid #000;
      }
      .table th {
        background: #eee !important;
        color: #000 !important;
      }
    }
  </style>
</head>
<body>

<div id="docRoot" class="doc-page">

<div class="doc-header">
  <div>
    <h1 class="doc-title"><?= $type === 'invoice' ? 'Facture' : 'Recu de paiement' ?></h1>
    <div class="ref-block">
      <div><strong>Numero :</strong> <?= $documentNumber ?></div>
      <div><strong>Date :</strong> <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></div>
      <?php if ($managerName): ?>
        <div><strong>Prepare par :</strong> <?= $managerName ?></div>
      <?php endif; ?>
      <?php if ($type === 'receipt'): ?>
        <div>
          <strong>Statut :</strong>
          <span class="badge-status <?= $statusPaid ? 'badge-paid' : 'badge-unpaid' ?>">
            <?= $statusPaid ? 'Paiement recu' : 'Paiement en attente' ?>
          </span>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="brand-block">
  <?php if ($logoUrl): ?>
    <img class="logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" crossorigin="anonymous">
  <?php endif; ?>
    <div class="company-brand"><?= $companyName ?></div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="section-title">Societe</div>
    <div class="company-panel">
    <table class="info-table company-info">
      <tr><td><strong>Nom :</strong></td><td><?= $companyName ?></td></tr>
      <?php if ($companyAddress): ?><tr><td><strong>Adresse :</strong></td><td><?= $companyAddress ?></td></tr><?php endif; ?>
      <?php if ($companyPhone): ?><tr><td><strong>Telephone :</strong></td><td><?= $companyPhone ?></td></tr><?php endif; ?>
      <?php if ($companyEmail): ?><tr><td><strong>Email :</strong></td><td><?= $companyEmail ?></td></tr><?php endif; ?>
      <?php if ($companyNif): ?><tr><td><strong>NIF :</strong></td><td><?= $companyNif ?></td></tr><?php endif; ?>
      <?php if ($companyRc): ?><tr><td><strong>RC :</strong></td><td><?= $companyRc ?></td></tr><?php endif; ?>
      <?php if ($companyNis): ?><tr><td><strong>NIS :</strong></td><td><?= $companyNis ?></td></tr><?php endif; ?>
    </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="section-title">Client</div>
    <table class="info-table">
      <tr><td><strong>Nom complet :</strong></td><td><?= $clientName ?></td></tr>
      <?php if ($clientAddress): ?><tr><td><strong>Adresse :</strong></td><td><?= $clientAddress ?></td></tr><?php endif; ?>
      <?php if ($clientPhone): ?><tr><td><strong>Telephone :</strong></td><td><?= $clientPhone ?></td></tr><?php endif; ?>
      <?php if ($clientEmail): ?><tr><td><strong>Email :</strong></td><td><?= $clientEmail ?></td></tr><?php endif; ?>
      <?php if ($clientPassport): ?><tr><td><strong>Passeport :</strong></td><td><?= $clientPassport ?></td></tr><?php endif; ?>
    </table>
  </div>
</div>

<?php if ($type === 'invoice'): ?>
  <div class="mb-4">
    <div class="section-title">Details du vehicule</div>
    <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th style="width: 20%;">Reference</th>
          <th style="width: 40%;">Vehicule</th>
          <th style="width: 20%;">Boite</th>
          <th style="width: 20%;" class="text-end">Montant</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= $documentNumber ?></td>
          <td><?= $carDesignation ?></td>
          <td><?= $gearbox ?></td>
          <td class="text-end"><?= $vehicleAmountHtml ?></td>
        </tr>
        <tr class="table-primary fw-semibold">
          <td></td>
          <td colspan="2">Total a payer</td>
          <td class="text-end"><?= $totalAmountHtml ?></td>
        </tr>
      </tbody>
    </table>
    </div>
  </div>

  <div class="mb-2">
    <div class="section-title">Caracteristiques du vehicule</div>
    <div class="vehicle-brief">
      <span><strong>Type:</strong> <?= $carBodyType ?></span> •
      <span><strong>Couleur:</strong> <?= $carColor ?></span> •
      <span><strong>Moteur:</strong> <?= $carEngine ?></span> •
      <span><strong>Boite:</strong> <?= $gearbox ?></span>
    </div>
  </div>

  <div class="amount-box mb-4">
    Montant total a payer : <?= $totalAmountHtml ?>
  </div>

  <p class="mb-5">
    Arrete la presente facture a la somme de <strong><?= htmlspecialchars($totalAmountText, ENT_QUOTES, 'UTF-8') ?></strong>.
  </p>

  <div class="row signature-box">
    <div class="col-md-6">
      Le client<br><span style="font-size: 12px;">Nom et signature</span>
    </div>
    <div class="col-md-6">
      Pour la societe<br><span style="font-size: 12px;">Cachet et signature</span>
    </div>
  </div>
<?php else: ?>
  <div class="mb-4">
    <div class="section-title">Informations du paiement</div>
    <table class="info-table">
      <tr>
        <td><strong>Montant du vehicule :</strong></td>
        <td><?= $vehicleAmountHtml ?></td>
      </tr>
      <tr>
        <td><strong>Montant total :</strong></td>
        <td><?= $totalAmountHtml ?></td>
      </tr>
      <tr>
        <td><strong>Date de commande :</strong></td>
        <td><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
      <tr>
        <td><strong>Mode :</strong></td>
        <td><?= $statusPaid ? 'Paiement confirme' : 'Paiement en attente' ?></td>
      </tr>
    </table>
  </div>

  <div class="mb-2">
    <div class="section-title">Details du vehicule</div>
    <div class="vehicle-brief">
      <span><strong>Vehicule:</strong> <?= $carDesignation ?></span> •
      <span><strong>Type:</strong> <?= $carBodyType ?></span> •
      <span><strong>Couleur:</strong> <?= $carColor ?></span> •
      <span><strong>Moteur:</strong> <?= $carEngine ?></span> •
      <span><strong>Boite:</strong> <?= $gearbox ?></span>
    </div>
  </div>

  <p class="mb-4">
    Nous confirmons avoir recu de <strong><?= $clientName ?></strong>
    la somme de <strong><?= htmlspecialchars($totalAmountText, ENT_QUOTES, 'UTF-8') ?></strong>
    pour la commande du vehicule <strong><?= $carDesignation ?></strong>.
  </p>

  <?php if (!$statusPaid): ?>
    <div class="alert alert-warning">
      Le paiement integral n'a pas encore ete confirme. Merci de regler le solde restant dans les meilleurs delais.
    </div>
  <?php endif; ?>

  <div class="row signature-box">
    <div class="col-md-6">
      Signature du client
    </div>
    <div class="col-md-6">
      Signature de la societe
    </div>
  </div>
<?php endif; ?>

<!-- close A4 wrapper before controls -->
</div>

<div class="text-center mt-4 no-print">
  <button type="button" class="btn btn-primary" onclick="downloadPDF()">Telecharger PDF</button>
  <a class="btn btn-outline-secondary ms-2" href="<?= htmlspecialchars(BASE_URL . '/admin/orders.php', ENT_QUOTES, 'UTF-8') ?>">Retour aux commandes</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-YcsIP7wA8m9Kz0kZwF1v6h0kFQmZr9dUo6M9i9l9m3kE2y5YzN1s2VQwG3sY5fLQv6H1j6pHh0lShvYdVbK9mw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
function downloadPDF() {
  const filename = '<?= $type === 'invoice' ? 'facture_' : 'recu_' ?>' + '<?= addslashes($documentNumber) ?>' + '.pdf';
  const hidden = Array.from(document.querySelectorAll('.no-print'));
  const original = hidden.map(el => el.style.display);
  hidden.forEach(el => el.style.display = 'none');
  const target = document.getElementById('docRoot');
  const isMobile = Math.min(window.innerWidth, window.innerHeight) < 576;
  const scale = isMobile ? 1.3 : (window.devicePixelRatio > 1 ? 2 : 1.5);
  const prevWidth = target.style.width;
  // enforce A4 width for capture to avoid mobile layout shifts
  target.style.width = '794px';

  if (window.html2pdf) {
    const opt = {
      margin:       10,
      filename:     filename,
      image:        { type: 'jpeg', quality: 0.95 },
      html2canvas:  { scale, useCORS: true, allowTaint: false, backgroundColor: '#ffffff', width: target.scrollWidth, height: target.scrollHeight },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'p' }
    };
    window.scrollTo(0, 0);
    window.html2pdf().set(opt).from(target).save().then(() => {
      // done
    }).catch((e) => {
      console.error(e);
      alert('Echec de generation PDF');
    }).finally(() => {
      target.style.width = prevWidth;
      hidden.forEach((el, i) => el.style.display = original[i] || '');
    });
    return;
  }

  // Fallback to html2canvas + jsPDF
  if (!window.jspdf || !window.jspdf.jsPDF || !window.html2canvas) {
    alert('Les bibliotheques PDF ne sont pas chargees. Verifiez votre connexion.');
    target.style.width = prevWidth;
    hidden.forEach((el, i) => el.style.display = original[i] || '');
    return;
  }
  const { jsPDF } = window.jspdf;
  window.scrollTo(0, 0);
  html2canvas(target, { scale, useCORS: true, allowTaint: false, backgroundColor: '#ffffff', width: target.scrollWidth, height: target.scrollHeight }).then(canvas => {
    const pdf = new jsPDF('p', 'mm', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const imgWidth = pageWidth;
    const imgHeight = canvas.height * imgWidth / canvas.width;
    let heightLeft = imgHeight;
    let position = 0;
    const imgData = canvas.toDataURL('image/jpeg', 0.95);
    pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
    heightLeft -= pageHeight;
    while (heightLeft > 0) {
      pdf.addPage();
      position = -(imgHeight - heightLeft);
      pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;
    }
    pdf.save(filename);
  }).catch((e) => { console.error(e); alert('Echec de generation PDF'); }).finally(() => {
    target.style.width = prevWidth;
    hidden.forEach((el, i) => el.style.display = original[i] || '');
  });
}
</script>

</body>
</html>
