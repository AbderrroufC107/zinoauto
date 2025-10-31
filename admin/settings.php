<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

admin_only();
$db = get_db();

$db->exec('CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  company_name TEXT,
  company_logo TEXT,
  company_phone TEXT DEFAULT "",
  company_email TEXT DEFAULT "",
  company_address TEXT DEFAULT "",
  company_nif TEXT DEFAULT "",
  company_rc TEXT DEFAULT "",
  company_nis TEXT DEFAULT ""
)');

$columns = ['company_phone','company_email','company_address','company_nif','company_rc','company_nis'];
foreach ($columns as $column) {
    try {
        $db->exec("ALTER TABLE settings ADD COLUMN {$column} TEXT DEFAULT ''");
    } catch (Throwable $e) {
        // column already exists
    }
}

$db->exec("INSERT OR IGNORE INTO settings(
    id, company_name, company_logo, company_phone, company_email, company_address, company_nif, company_rc, company_nis
) VALUES (1, 'Zino Auto', NULL, '', '', '', '', '', '')");

$settings = $db->query('SELECT * FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$admins = $db->query('SELECT id, username FROM admin ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_company'])) {
    $name = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['company_phone'] ?? '');
    $email = trim($_POST['company_email'] ?? '');
    $address = trim($_POST['company_address'] ?? '');
    $nif = trim($_POST['company_nif'] ?? '');
    $rc = trim($_POST['company_rc'] ?? '');
    $nis = trim($_POST['company_nis'] ?? '');

    $row = $db->query('SELECT company_logo FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    $oldLogo = $row['company_logo'] ?? null;
    $logoName = $oldLogo;

    if (!empty($_FILES['company_logo']['name'])) {
        if (!is_dir(UPLOAD_DIR)) {
            @mkdir(UPLOAD_DIR, 0777, true);
        }
        $fileName = basename($_FILES['company_logo']['name']);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $logoName = time() . '_' . uniqid('', true) . '.' . $extension;
        $targetPath = UPLOAD_DIR . '/' . $logoName;
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $targetPath)) {
            if ($oldLogo && is_file(UPLOAD_DIR . '/' . $oldLogo)) {
                @unlink(UPLOAD_DIR . '/' . $oldLogo);
            }
        } else {
            $err = 'لم يتم رفع الشعار، حاول مرة أخرى.';
        }
    }

    if ($err === '') {
        $stmt = $db->prepare('UPDATE settings SET company_name = ?, company_logo = ?, company_phone = ?, company_email = ?, company_address = ?, company_nif = ?, company_rc = ?, company_nis = ? WHERE id = 1');
        $stmt->execute([
            $name !== '' ? $name : 'Zino Auto',
            $logoName,
            $phone,
            $email,
            $address,
            $nif,
            $rc,
            $nis
        ]);
        $msg = 'تم حفظ بيانات الشركة.';
        $settings = $db->query('SELECT * FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === '' || strlen($p) < 6) {
        $err = 'يجب إدخال اسم مستخدم وكلمة مرور لا تقل عن ستة أحرف.';
    } else {
        $stmt = $db->prepare('SELECT 1 FROM admin WHERE username = ?');
        $stmt->execute([$u]);
        if ($stmt->fetch()) {
            $err = 'اسم المستخدم موجود مسبقاً.';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $db->prepare('INSERT INTO admin (username, password) VALUES (?, ?)')->execute([$u, $hash]);
            $msg = 'تمت إضافة المشرف.';
            $admins = $db->query('SELECT id, username FROM admin ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

if (isset($_GET['delete_admin'])) {
    $id = (int)$_GET['delete_admin'];
    $db->prepare('DELETE FROM admin WHERE id = ?')->execute([$id]);
    $msg = 'تم حذف المشرف.';
    if ($id === ($_SESSION['admin_id'] ?? 0)) {
        session_destroy();
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    $admins = $db->query('SELECT id, username FROM admin ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
}

$previewFields = ['company_name','company_phone','company_email','company_address','company_nif','company_rc','company_nis'];
$hasPreview = false;
foreach ($previewFields as $field) {
    if (!empty($settings[$field])) {
        $hasPreview = true;
        break;
    }
}

$page_title = 'إعدادات النظام';
require __DIR__ . '/../includes/header.php';
?>

<div class="row gy-4">
  <div class="col-12">
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
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title mb-4">بيانات الشركة</h5>
        <div class="mb-4">
          <h6 class="text-muted mb-3">البيانات الحالية</h6>
          <?php if ($hasPreview): ?>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between"><span>اسم الشركة</span><span><?= htmlspecialchars($settings['company_name'] ?? '') ?></span></li>
              <li class="list-group-item d-flex justify-content-between"><span>الهاتف</span><span><?= htmlspecialchars($settings['company_phone'] ?? '') ?></span></li>
              <li class="list-group-item d-flex justify-content-between"><span>البريد الإلكتروني</span><span><?= htmlspecialchars($settings['company_email'] ?? '') ?></span></li>
              <li class="list-group-item d-flex justify-content-between"><span>العنوان</span><span><?= htmlspecialchars($settings['company_address'] ?? '') ?></span></li>
              <li class="list-group-item d-flex justify-content-between"><span>NIF</span><span><?= htmlspecialchars($settings['company_nif'] ?? '') ?></span></li>
              <li class="list-group-item d-flex justify-content-between"><span>RC</span><span><?= htmlspecialchars($settings['company_rc'] ?? '') ?></span></li>
              <li class="list-group-item d-flex justify-content-between"><span>NIS</span><span><?= htmlspecialchars($settings['company_nis'] ?? '') ?></span></li>
            </ul>
          <?php else: ?>
            <div class="alert alert-info mb-0">لم يتم حفظ بيانات بعد.</div>
          <?php endif; ?>
        </div>

        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <input type="hidden" name="save_company" value="1">
          <label class="form-label">اسم الشركة</label>
          <input class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? 'Zino Auto') ?>">

          <label class="form-label">رقم الهاتف</label>
          <input class="form-control" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>" placeholder="+213 00 00 00 00">

          <label class="form-label">البريد الإلكتروني</label>
          <input class="form-control" type="email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>" placeholder="contact@example.dz">

          <label class="form-label">العنوان</label>
          <textarea class="form-control" name="company_address" rows="2" placeholder="المدينة، الشارع، رقم المبنى"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>

          <label class="form-label">NIF</label>
          <input class="form-control" name="company_nif" value="<?= htmlspecialchars($settings['company_nif'] ?? '') ?>" placeholder="000000000000000">

          <label class="form-label">RC</label>
          <input class="form-control" name="company_rc" value="<?= htmlspecialchars($settings['company_rc'] ?? '') ?>" placeholder="00/00-0000000A00">

          <label class="form-label">NIS</label>
          <input class="form-control" name="company_nis" value="<?= htmlspecialchars($settings['company_nis'] ?? '') ?>" placeholder="000000000000000">

          <label class="form-label">الشعار (اختياري)</label>
          <input class="form-control" type="file" name="company_logo" accept="image/*">
          <?php if (!empty($settings['company_logo']) && file_exists(__DIR__ . '/../uploads/' . $settings['company_logo'])): ?>
            <div class="mt-2">
              <img src="<?= BASE_URL ?>/uploads/<?= urlencode($settings['company_logo']) ?>" alt="شعار الشركة" style="height:60px; border-radius:6px; object-fit:cover">
            </div>
          <?php endif; ?>

          <button class="btn btn-primary" type="submit">حفظ البيانات</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title mb-4">إدارة المشرفين</h5>
        <form method="post" class="mb-4">
          <input type="hidden" name="add_admin" value="1">
          <div class="row g-2">
            <div class="col-md-5"><input class="form-control" name="username" placeholder="اسم المستخدم" required></div>
            <div class="col-md-5"><input class="form-control" type="password" name="password" placeholder="كلمة المرور (6 أحرف على الأقل)" required minlength="6"></div>
            <div class="col-md-2"><button class="btn btn-success w-100" type="submit">إضافة</button></div>
          </div>
        </form>

        <ul class="list-group list-group-flush">
          <?php foreach ($admins as $a): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <?= htmlspecialchars($a['username']) ?>
                <?php if ($a['id'] == ($_SESSION['admin_id'] ?? 0)): ?>
                  <span class="badge bg-primary ms-2">حسابك</span>
                <?php endif; ?>
              </div>
              <div>
                <a class="btn btn-sm btn-outline-danger" href="?delete_admin=<?= (int)$a['id'] ?>" onclick="return confirm('هل تريد حذف هذا المشرف؟')">حذف</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

