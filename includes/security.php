<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('APP_SECRET')) {
    define('APP_SECRET', $_ENV['APP_SECRET'] ?? 'zinoauto-local-secret');
}

function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function make_link_token(string $type, int $id): string {
    $payload = [
        't' => $type,
        'i' => $id,
        'ts' => time(),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $b64 = b64url_encode($json ?: '');
    $sig = hash_hmac('sha256', $b64, APP_SECRET, true);
    return $b64 . '.' . b64url_encode($sig);
}

function parse_link_token(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;
    [$b64, $sigB64] = $parts;
    $calc = b64url_encode(hash_hmac('sha256', $b64, APP_SECRET, true));
    if (!hash_equals($calc, $sigB64)) return null;
    $json = b64url_decode($b64);
    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['t'], $data['i'])) return null;
    $type = (string)$data['t'];
    $id = (int)$data['i'];
    if ($id <= 0 || !in_array($type, ['invoice', 'receipt'], true)) return null;
    return ['type' => $type, 'id' => $id];
}

