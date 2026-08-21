<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($path === '/health' && $method === 'GET') {
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'service' => 'cubashop-kiosko-api',
        'version' => '0.1.0',
        'currency' => 'CUP',
        'public_checkout' => false,
        'payment_methods' => ['efectivo', 'transfermovil'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
