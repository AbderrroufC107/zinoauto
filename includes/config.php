<?php
// Basic config
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure UTF-8 everywhere
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) { @mb_internal_encoding('UTF-8'); }
if (function_exists('mb_http_output')) { @mb_http_output('UTF-8'); }

// Persist login session across pages (7 days)
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params(60 * 60 * 24 * 7, '/', '', false, true);
}
session_start();

define('DATA_DIR', __DIR__ . '/../data');
define('DB_FILE', DATA_DIR . '/app.db');
define('UPLOAD_DIR', __DIR__ . '/../uploads');

// === اكتشاف BASE_URL تلقائيًا (يدعم localhost ونطاقات حقيقية) ===
$__isHttps = false;
try {
    $v = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    if ($v === 'on' || $v === '1') $__isHttps = true;
    $xfp = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($xfp !== '' && strpos($xfp, 'https') !== false) $__isHttps = true;
    $xfs = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    if ($xfs === 'on') $__isHttps = true;
    $sch = strtolower((string)($_SERVER['REQUEST_SCHEME'] ?? ''));
    if ($sch === 'https') $__isHttps = true;
    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) $__isHttps = true;
} catch (Throwable $e) { }
$protocol = $__isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$appRootFs = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$relativePath = '';
if ($docRoot && strpos($appRootFs, $docRoot) === 0) {
    $relativePath = substr($appRootFs, strlen($docRoot));
} else {
    $relativePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
}
$scriptDir = rtrim($relativePath, '/');
$base = rtrim($protocol . '://' . $host . $scriptDir, '/');
define('BASE_URL', $base);
