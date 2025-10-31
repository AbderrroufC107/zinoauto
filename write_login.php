<?php
file_put_contents(__DIR__ . '/../admin/login.php', <<'PHP'
<?php
declare(strict_types=1);

define('HEADER_SKIP_SETTINGS', true);
require_once __DIR__ . '/../includes/db.php';

$text = json_decode('{
  "page_title": "\u062a\u0633\u062c\u064a\u0644 \u062f\u062e\u0648\u0644 \u0627\u0644\u0645\u0633\u0624\u0648\u0644",
  "logout_success": "\u062a\u0645 \u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062e\u0631\u0648\u062c \u0628\u0646\u062c\u0627\u062d.",
  "login_card_title": "\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062f\u062e\u0648\u0644",
  "username_label": "\u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645",
  "password_label": "\u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631",
  "submit_button": "\u062f\u062e\u0648\u0644",
  "error_invalid": "\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u062f\u062e\u0648\u0644 \u063a\u064a\u0631 \u0635\u062d\u064a\u062d\u0629.",
  "hint_password": "\u0627\u0633\u062a\u062e\u062f\u0645 \u0627\u0644\u0633\u062c\u0644 \u0627\u0644\u0625\u062f\u0627\u0631\u064a \u0648\u0643\u0644\u0645\u0629 \u0645\u0631\u0648\u0631\u0643 \u0627\u0644\u0645\u062d\u062f\u062b\u0629.",
  "info_logout": "\u062a\u0645 \u0625\u0646\u0647\u0627\u0621 \u0627\u0644\u062c\u0644\u0633\u0629.",
  "forgot_tip": "\u0625\u0630\u0627 \u0646\u0633\u064a\u062a \u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631 \u0627\u0633\u062a\u062e\u062f\u0645 \u0627\u0644\u0645\u0644\u0641 set_admin_password.php \u0644\u062a\u0639\u064a\u064a\u0646 \u0648\u0627\u062d\u062f\u0629 \u062c\u062f\u064a\u062f\u0629."
}', true);

$err = '';
$info = '';

if (!empty($_GET['logout'])) {
    unset($_SESSION['admin_id']);
    header('Location: ' . BASE_URL . '/admin/login.php?logged_out=1');
    exit;
}

if (!empty($_GET['logged_out'])) {
    $info = $text['logout_success'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $user = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if ($user !== '' && $pass !== '') {
        $db = get_db();
        $stmt = $db->prepare('SELECT id, password FROM admin WHERE username = ?');
        $stmt->execute([$user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && is_string($row['password']) && password_verify($pass, $row['password'])) {
            $_SESSION['admin_id'] = (int)$row['id'];
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        }
    }

    $err = $text['error_invalid'];
}

$page_title = $text['page_title'];
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-body">
        <h3 class="card-title mb-3"><?= $text['login_card_title'] ?></h3>
        <?php if ($info): ?>
          <div class="alert alert-info" role="alert"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <form method="post" class="vstack gap-3">
          <div>
            <label class="form-label" for="username"><?= $text['username_label'] ?></label>
            <input class="form-control" id="username" name="username" autocomplete="username" required>
          </div>
          <div>
            <label class="form-label" for="password"><?= $text['password_label'] ?></label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
            <div class="form-text"><?= $text['hint_password'] ?></div>
          </div>
          <button class="btn btn-primary w-100" type="submit"><?= $text['submit_button'] ?></button>
        </form>
        <div class="text-muted small mt-3"><?= $text['forgot_tip'] ?></div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
PHP
);