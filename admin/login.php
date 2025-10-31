<?php
declare(strict_types=1);

define('HEADER_SKIP_SETTINGS', true);
require_once __DIR__ . '/../includes/db.php';

$text = json_decode('{
  "page_title": "\u062a\u0633\u062c\u064a\u0644 \u062f\u062e\u0648\u0644 \u0627\u0644\u0645\u0633\u0624\u0648\u062c\u0644",
  "logout_success": "\u062a\u0645 \u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062e\u0631\u0648\u062c \u0628\u0646\u062c\u0627\u062d.",
  "login_card_title": "\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062f\u062e\u0648\u0644",
  "username_label": "\u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645",
  "password_label": "\u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631",
  "submit_button": "\u062f\u062e\u0648\u0644",
  "error_invalid": "\u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u062f\u062e\u0648\u0644 \u063a\u064a\u0631 \u0635\u062d\u064a\u062d\u0629.",
  "hint_password": "\u0627\u0633\u062a\u062e\u062f\u0645 \u0627\u0644\u0633\u062c\u0627\u0644 \u0627\u0644\u0625\u062f\u0627\u0631\u064a \u0648\u0643\u0644\u0645\u0629 \u0645\u0631\u0648\u0631\u0643 \u0627\u0644\u0645\u062d\u062f\u062b\u0629.",
  "info_logout": "\u062a\u0645 \u0625\u0646\u0647\u0627\u0621 \u0627\u0644\u062c\u0644\u0633\u0629.",
  "forgot_tip": "",
  "error_too_many": "\u062a\u0645 \u062a\u062c\u0627\u0648\u0632 \u0639\u062f\u062f \u0645\u062d\u0627\u0648\u0644\u0627\u062a \u0627\u0644\u062f\u062e\u0648\u0644. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0628\u0639\u062f %d \u062b\u0627\u0646\u064a\u0629.",
  "attempts_left": "\u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0627\u062a \u0627\u0644\u0645\u062a\u0628\u0642\u064a\u0629: %d/5"
}', true);

$err = '';
$info = '';
$locked = false;
$lockedFor = 0;
$attempts = (int)($_SESSION['login_attempts'] ?? 0);
$lockUntil = (int)($_SESSION['login_lock_until'] ?? 0);
if ($lockUntil > time()) {
    $locked = true;
    $lockedFor = $lockUntil - time();
}

if (!empty($_GET['logout'])) {
    unset($_SESSION['admin_id']);
    header('Location: ' . BASE_URL . '/admin/login.php?logged_out=1');
    exit;
}

if (!empty($_GET['logged_out'])) {
    $info = $text['logout_success'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($locked) {
        $err = sprintf($text['error_too_many'], (int)$lockedFor);
    } else {
        $user = trim((string)($_POST['username'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');

        if ($user !== '' && $pass !== '') {
            $db = get_db();
            $stmt = $db->prepare('SELECT id, password FROM admin WHERE username = ?');
            $stmt->execute([$user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && is_string($row['password']) && password_verify($pass, $row['password'])) {
                unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
                $_SESSION['admin_id'] = (int)$row['id'];
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
                exit;
            }
        }

        // Failed attempt
        $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['login_lock_until'] = time() + 60; // lock 60s
            $locked = true;
            $lockedFor = 60;
            $err = sprintf($text['error_too_many'], (int)$lockedFor);
        } else {
            $err = $text['error_invalid'];
        }
    }
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
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required <?= $locked ? 'disabled' : '' ?>>
            <?php if ($locked): ?>
              <div class="form-text text-danger"><?= sprintf($text['error_too_many'], (int)$lockedFor) ?></div>
            <?php else: ?>
              <div class="form-text text-muted"><?= sprintf($text['attempts_left'], max(0, 5 - (int)($_SESSION['login_attempts'] ?? 0))) ?></div>
            <?php endif; ?>
          </div>
          <button class="btn btn-primary w-100" type="submit" <?= $locked ? 'disabled' : '' ?>><?= $text['submit_button'] ?></button>
        </form>
        
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
