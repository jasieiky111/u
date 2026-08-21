<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Api.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Authorization.php';
require_once __DIR__ . '/../app/KioskoService.php';
require_once __DIR__ . '/../app/CashService.php';
require_once __DIR__ . '/../app/ReportService.php';
require_once __DIR__ . '/../app/InventoryService.php';

$files = [
    __DIR__ . '/../app/Api.php',
    __DIR__ . '/../app/Database.php',
    __DIR__ . '/../app/Auth.php',
    __DIR__ . '/../app/Authorization.php',
    __DIR__ . '/../app/KioskoService.php',
    __DIR__ . '/../app/CashService.php',
    __DIR__ . '/../app/ReportService.php',
    __DIR__ . '/../app/InventoryService.php',
];
foreach ($files as $file) {
    if (!is_file($file)) throw new RuntimeException("Missing: {$file}");
}

foreach (['Api','Database','Auth','Authorization','KioskoService','CashService','ReportService','InventoryService'] as $class) {
    if (!class_exists($class)) throw new RuntimeException("Missing class: {$class}");
}

echo "CubaShop Kiosko smoke test: OK\n";
