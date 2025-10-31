<?php
require_once __DIR__ . '/db.php';

function admin_only() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function current_admin() {
    if (empty($_SESSION['admin_id'])) return null;
    $db = get_db();
    $stmt = $db->prepare('SELECT id, username FROM admin WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>