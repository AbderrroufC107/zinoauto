<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$db = get_db();

$text = json_decode('{
  "page_title": "\u0634\u0631\u0643\u0627\u062a \u0627\u0644\u0634\u062d\u0646",
  "form_add": "\u0625\u0636\u0627\u0641\u0629 \u0634\u0631\u0643\u0629 \u0634\u062d\u0646",
  "form_edit": "\u062a\u0639\u062f\u064a\u0644 \u0634\u0631\u0643\u0629 \u0634\u062d\u0646",
  "name_label": "\u0627\u0633\u0645 \u0627\u0644\u0634\u0631\u0643\u0629",
  "phone_label": "\u0627\u0644\u0647\u0627\u062a\u0641",
  "email_label": "\u0627\u0644\u0628\u0631\u064a\u062f \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a",
  "website_label": "\u0627\u0644\u0645\u0648\u0642\u0639 \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a",
  "notes_label": "\u0645\u0644\u0627\u062d\u0638\u0627\u062a",
  "active_label": "\u0641\u0639\u0627\u0644\u0629",
  "save_button": "\u062d\u0641\u0638",
  "add_button": "\u0625\u0636\u0627\u0641\u0629",
  "cancel_button": "\u0625\u0644\u063a\u0627\u0621",
  "list_title": "\u0634\u0631\u0643\u0627\u062a \u0627\u0644\u0634\u062d\u0646 \u0627\u0644\u0645\u062a\u0639\u0627\u0642\u062f \u0645\u0639\u0647\u0627",
  "empty_list": "\u0644\u0627 \u062a\u0648\u062c\u062f \u0634\u0631\u0643\u0627\u062a \u062d\u0627\u0644\u064a\u0627\u064b.",
  "inactive_badge": "\u063a\u064a\u0631 \u0641\u0639\u0627\u0644\u0629",
  "edit_button": "\u062a\u0639\u062f\u064a\u0644",
  "delete_button": "\u062d\u0630\u0641",
  "delete_confirm": "\u0647\u0644 \u062a\u0631\u063a\u0628 \u062d\u0642\u0627\u064b \u0628\u062d\u0630\u0641 \u0647\u0630\u0647 \u0627\u0644\u0634\u0631\u0643\u0629\u061f",
  "back_button": "\u0627\u0644\u0639\u0648\u062f\u0629 \u0625\u0644\u0649 \u0644\u0648\u062d\u0629 \u0627\u0644\u062a\u062d\u0643\u0645",
  "msg_added": "\u062a\u0645\u062a \u0625\u0636\u0627\u0641\u0629 \u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646 \u0628\u0646\u062c\u0627\u062d.",
  "msg_updated": "\u062a\u0645 \u062a\u062d\u062f\u064a\u062b \u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646.",
  "msg_deleted": "\u062a\u0645 \u062d\u0630\u0641 \u0634\u0631\u0643\u0629 \u0627\u0644\u0634\u062d\u0646.",
  "err_required": "\u0627\u0633\u0645 \u0627\u0644\u0634\u0631\u0643\u0629 \u0645\u0637\u0644\u0648\u0628."
}', true);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if ($name === '') {
        $err = $text['err_required'];
    } else {
        if ($action === 'edit' && $id > 0) {
            $stmt = $db->prepare('UPDATE shipping_companies SET name=?, contact_phone=?, contact_email=?, website=?, notes=?, active=?, updated_at=? WHERE id=?');
            $stmt->execute([$name, $contact_phone, $contact_email, $website, $notes, $active, date('c'), $id]);
            $msg = $text['msg_updated'];
        } else {
            $stmt = $db->prepare('INSERT INTO shipping_companies (name, contact_phone, contact_email, website, notes, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $contact_phone, $contact_email, $website, $notes, $active, date('c'), date('c')]);
            $msg = $text['msg_added'];
        }
        header('Location: shipping.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare('DELETE FROM shipping_companies WHERE id=?')->execute([$id]);
    header('Location: shipping.php?deleted=1');
    exit;
}

if (!empty($_GET['deleted'])) {
    $msg = $text['msg_deleted'];
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM shipping_companies WHERE id=?');
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$companies = $db->query('SELECT * FROM shipping_companies ORDER BY active DESC, name')->fetchAll(PDO::FETCH_ASSOC);

$page_title = $text['page_title'];
require __DIR__ . '/../includes/header.php';
?>

<div class="row">
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

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h5 class="card-title mb-4"><?= $edit ? $text['form_edit'] : $text['form_add'] ?></h5>
        <form method="post">
          <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
          <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label"><?= $text['name_label'] ?></label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?= $text['phone_label'] ?></label>
              <input class="form-control" name="contact_phone" value="<?= htmlspecialchars($edit['contact_phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= $text['email_label'] ?></label>
              <input type="email" class="form-control" name="contact_email" value="<?= htmlspecialchars($edit['contact_email'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label"><?= $text['website_label'] ?></label>
            <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($edit['website'] ?? '') ?>" placeholder="https://example.com">
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $text['notes_label'] ?></label>
            <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($edit['notes'] ?? '') ?></textarea>
          </div>

          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="active" name="active" <?= !isset($edit['active']) || (int)$edit['active'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="active"><?= $text['active_label'] ?></label>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= $edit ? $text['save_button'] : $text['add_button'] ?></button>
            <?php if ($edit): ?><a class="btn btn-secondary" href="shipping.php"><?= $text['cancel_button'] ?></a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <h5 class="mb-3"><?= $text['list_title'] ?></h5>
    <?php if (empty($companies)): ?>
      <div class="alert alert-info"><?= $text['empty_list'] ?></div>
    <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($companies as $company): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold">
                <?php if (!empty($company['website'])): ?>
                  <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= htmlspecialchars($company['name']) ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($company['name']) ?>
                <?php endif; ?>
                <?php if ((int)$company['active'] !== 1): ?>
                  <span class="badge bg-secondary ms-2"><?= $text['inactive_badge'] ?></span>
                <?php endif; ?>
              </div>
              <div class="small text-muted">
                <?php if (!empty($company['contact_phone'])): ?>📞 <?= htmlspecialchars($company['contact_phone']) ?><?php endif; ?>
                <?php if (!empty($company['contact_email'])): ?>
                  <?php if (!empty($company['contact_phone'])): ?> • <?php endif; ?>
                  ✉️ <?= htmlspecialchars($company['contact_email']) ?>
                <?php endif; ?>
              </div>
              <?php if (!empty($company['notes'])): ?>
                <div class="small mt-1"><?= nl2br(htmlspecialchars($company['notes'])) ?></div>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-1">
              <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$company['id'] ?>"><?= $text['edit_button'] ?></a>
              <a class="btn btn-sm btn-danger" href="?delete=<?= (int)$company['id'] ?>" onclick="return confirm('<?= $text['delete_confirm'] ?>')"><?= $text['delete_button'] ?></a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="mt-3">
      <a class="btn btn-secondary" href="dashboard.php"><?= $text['back_button'] ?></a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
