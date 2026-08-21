<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Api.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Authorization.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/KioskoService.php';
require_once __DIR__ . '/../app/CashService.php';
require_once __DIR__ . '/../app/ReportService.php';
require_once __DIR__ . '/../app/InventoryService.php';
require_once __DIR__ . '/../app/CancellationService.php';

$env=['DB_HOST'=>getenv('DB_HOST')?:'127.0.0.1','DB_PORT'=>getenv('DB_PORT')?:'3306','DB_DATABASE'=>getenv('DB_DATABASE')?:'cubashop_kiosko','DB_USERNAME'=>getenv('DB_USERNAME')?:'cubashop','DB_PASSWORD'=>getenv('DB_PASSWORD')?:'','API_SECRET'=>getenv('API_SECRET')?:''];
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/'; $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
if($path==='/health'&&$method==='GET') Api::json(['ok'=>true,'service'=>'cubashop-kiosko-api','version'=>'0.4.0','currency'=>'CUP','public_checkout'=>false]);
if($path==='/login'&&$method==='POST') Auth::requireApiSecret($env);
$db=(new Database($env))->pdo(); $service=new KioskoService($db); $cash=new CashService($db); $reports=new ReportService($db); $inventory=new InventoryService($db); $cancel=new CancellationService($db);
try {
 if($path==='/login'&&$method==='POST'){ $b=Api::body(); $u=$service->login((string)($b['username']??''),(string)($b['password']??'')); if(!$u) Api::json(['ok'=>false,'error'=>'invalid_credentials'],401); Api::json(['ok'=>true,'user'=>$u,'token'=>Auth::issueUserToken($u,(string)$env['API_SECRET'])]); }
 $user=Auth::requireUser($env);
 if($path==='/products'&&$method==='GET') Api::json(['ok'=>true,'data'=>$service->products()]);
 if($path==='/products'&&$method==='POST'){ Authorization::requireRole($user,['kiosk_admin','server_admin']); $b=Api::body(); Api::json(['ok'=>true,'product_id'=>$inventory->create((int)$user['id'],trim((string)($b['sku']??'')),trim((string)($b['name']??'')),(float)($b['price_cup']??0),(float)($b['stock']??0))],201); }
 if($path==='/inventory/adjust'&&$method==='POST'){ Authorization::requireRole($user,['kiosk_admin','server_admin']); $b=Api::body(); $inventory->adjust((int)$user['id'],(int)($b['product_id']??0),(float)($b['delta']??0)); Api::json(['ok'=>true]); }
 if($path==='/sales'&&$method==='POST'){ $b=Api::body(); $wid=(int)($b['worker_id']??0); Authorization::workerOrAdmin($user,$wid); if($wid<1) Api::json(['ok'=>false,'error'=>'worker_id_required'],422); $id=$service->createSale($wid,is_array($b['items']??null)?$b['items']:[],(string)($b['payment_method']??''),isset($b['transfer_reference'])?(string)$b['transfer_reference']:null); Api::json(['ok'=>true,'sale_id'=>$id],201); }
 if($path==='/sales/void'&&$method==='POST'){ $b=Api::body(); $saleId=(int)($b['sale_id']??0); Authorization::requireRole($user,['kiosk_admin','server_admin']); $cancel->void((int)$user['id'],$saleId); Api::json(['ok'=>true]); }
 if($path==='/cash'&&$method==='GET'){ $wid=isset($_GET['worker_id'])?(int)$_GET['worker_id']:(int)$user['id']; Authorization::workerOrAdmin($user,$wid); Api::json(['ok'=>true,'data'=>$cash->current($wid)]); }
 if($path==='/cash/open'&&$method==='POST'){ Authorization::requireRole($user,['worker','kiosk_admin']); $b=Api::body(); $wid=(int)($b['worker_id']??$user['id']); Authorization::workerOrAdmin($user,$wid); Api::json(['ok'=>true,'session_id'=>$cash->open($wid,(float)($b['opening_amount_cup']??0))],201); }
 if($path==='/cash/close'&&$method==='POST'){ Authorization::requireRole($user,['worker','kiosk_admin']); $b=Api::body(); $wid=(int)($b['worker_id']??$user['id']); Authorization::workerOrAdmin($user,$wid); $cash->close($wid,(int)($b['session_id']??0),(float)($b['closing_amount_cup']??0)); Api::json(['ok'=>true]); }
 if($path==='/reports/sales'&&$method==='GET'){ Authorization::requireRole($user,['kiosk_admin','server_admin']); Api::json(['ok'=>true,'data'=>$reports->salesSummary($_GET['from']??null,$_GET['to']??null)]); }
 if($path==='/reports/audit'&&$method==='GET'){ Authorization::requireRole($user,['kiosk_admin','server_admin']); Api::json(['ok'=>true,'data'=>$reports->audit((int)($_GET['limit']??100))]); }
 Api::json(['ok'=>false,'error'=>'not_found'],404);
} catch(InvalidArgumentException $e){Api::json(['ok'=>false,'error'=>$e->getMessage()],422);} catch(PDOException $e){error_log($e->getMessage());Api::json(['ok'=>false,'error'=>'database_error'],500);} catch(Throwable $e){error_log($e->getMessage());Api::json(['ok'=>false,'error'=>'server_error'],500);}
