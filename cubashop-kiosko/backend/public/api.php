<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Api.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/KioskoService.php';

$env = [
    'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
    'DB_PORT' => getenv('DB_PORT') ?: '3306',
    'DB_DATABASE' => getenv('DB_DATABASE') ?: 'cubashop_kiosko',
    'DB_USERNAME' => getenv('DB_USERNAME') ?: 'cubashop',
    'DB_PASSWORD' => getenv('DB_PASSWORD') ?: '',
    'API_SECRET' => getenv('API_SECRET') ?: '',
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($path === '/health' && $method === 'GET') Api::json(['ok'=>true,'service'=>'cubashop-kiosko-api','version'=>'0.2.0','currency'=>'CUP','public_checkout'=>false]);

Auth::requireApiSecret($env);
$db = (new Database($env))->pdo();
$service = new KioskoService($db);

try {
    if ($path === '/products' && $method === 'GET') Api::json(['ok'=>true,'data'=>$service->products()]);

    if ($path === '/login' && $method === 'POST') {
        $body = Api::body();
        $user = $service->login((string)($body['username'] ?? ''), (string)($body['password'] ?? ''));
        if (!$user) Api::json(['ok'=>false,'error'=>'invalid_credentials'],401);
        Api::json(['ok'=>true,'user'=>$user]);
    }

    if ($path === '/sales' && $method === 'POST') {
        $body = Api::body();
        $workerId = (int)($body['worker_id'] ?? 0);
        if ($workerId < 1) Api::json(['ok'=>false,'error'=>'worker_id_required'],422);
        $saleId = $service->createSale($workerId, is_array($body['items'] ?? null) ? $body['items'] : [], (string)($body['payment_method'] ?? ''), isset($body['transfer_reference']) ? (string)$body['transfer_reference'] : null);
        Api::json(['ok'=>true,'sale_id'=>$saleId],201);
    }

    Api::json(['ok'=>false,'error'=>'not_found'],404);
} catch (InvalidArgumentException $e) {
    Api::json(['ok'=>false,'error'=>$e->getMessage()],422);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Api::json(['ok'=>false,'error'=>'server_error'],500);
}
