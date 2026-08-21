<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Api.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Authorization.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/KioskoService.php';
require_once __DIR__ . '/../app/CashService.php';
require_once __DIR__ . '/../app/ReportService.php';

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

if ($path === '/health' && $method === 'GET') {
    Api::json(['ok'=>true,'service'=>'cubashop-kiosko-api','version'=>'0.3.0','currency'=>'CUP','public_checkout'=>false]);
}

if ($path === '/login' && $method === 'POST') {
    Auth::requireApiSecret($env);
}

$db = (new Database($env))->pdo();
$service = new KioskoService($db);
$cash = new CashService($db);
$reports = new ReportService($db);

try {
    if ($path === '/login' && $method === 'POST') {
        $body = Api::body();
        $user = $service->login((string)($body['username'] ?? ''), (string)($body['password'] ?? ''));
        if (!$user) Api::json(['ok'=>false,'error'=>'invalid_credentials'],401);
        Api::json(['ok'=>true,'user'=>$user,'token'=>Auth::issueUserToken($user,(string)$env['API_SECRET'])]);
    }

    $user = Auth::requireUser($env);

    if ($path === '/products' && $method === 'GET') {
        Api::json(['ok'=>true,'data'=>$service->products()]);
    }

    if ($path === '/sales' && $method === 'POST') {
        $body = Api::body();
        $workerId = (int)($body['worker_id'] ?? 0);
        Authorization::workerOrAdmin($user, $workerId);
        if ($workerId < 1) Api::json(['ok'=>false,'error'=>'worker_id_required'],422);
        $saleId = $service->createSale($workerId, is_array($body['items'] ?? null) ? $body['items'] : [], (string)($body['payment_method'] ?? ''), isset($body['transfer_reference']) ? (string)$body['transfer_reference'] : null);
        Api::json(['ok'=>true,'sale_id'=>$saleId],201);
    }

    if ($path === '/cash' && $method === 'GET') {
        $workerId = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : (int)$user['id'];
        Authorization::workerOrAdmin($user,$workerId);
        Api::json(['ok'=>true,'data'=>$cash->current($workerId)]);
    }

    if ($path === '/cash/open' && $method === 'POST') {
        Authorization::requireRole($user,['worker','kiosk_admin']);
        $body=Api::body(); $workerId=(int)($body['worker_id'] ?? $user['id']);
        Authorization::workerOrAdmin($user,$workerId);
        Api::json(['ok'=>true,'session_id'=>$cash->open($workerId,(float)($body['opening_amount_cup'] ?? 0))],201);
    }

    if ($path === '/cash/close' && $method === 'POST') {
        Authorization::requireRole($user,['worker','kiosk_admin']);
        $body=Api::body(); $workerId=(int)($body['worker_id'] ?? $user['id']);
        Authorization::workerOrAdmin($user,$workerId);
        $cash->close($workerId,(int)($body['session_id'] ?? 0),(float)($body['closing_amount_cup'] ?? 0));
        Api::json(['ok'=>true]);
    }

    if ($path === '/reports/sales' && $method === 'GET') {
        Authorization::requireRole($user,['kiosk_admin','server_admin']);
        Api::json(['ok'=>true,'data'=>$reports->salesSummary($_GET['from'] ?? null,$_GET['to'] ?? null)]);
    }

    if ($path === '/reports/audit' && $method === 'GET') {
        Authorization::requireRole($user,['kiosk_admin','server_admin']);
        Api::json(['ok'=>true,'data'=>$reports->audit((int)($_GET['limit'] ?? 100))]);
    }

    Api::json(['ok'=>false,'error'=>'not_found'],404);
} catch (InvalidArgumentException $e) {
    Api::json(['ok'=>false,'error'=>$e->getMessage()],422);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Api::json(['ok'=>false,'error'=>'server_error'],500);
}
