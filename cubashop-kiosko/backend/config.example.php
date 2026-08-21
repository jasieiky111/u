<?php
declare(strict_types=1);

// Copy this file to backend/config.php on the server and replace every placeholder.
// NEVER commit backend/config.php or real credentials to GitHub.
return [
    'DB_HOST' => 'sqlXXX.infinityfree.com',
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'if0_xxxxxxxx_cubashop',
    'DB_USERNAME' => 'if0_xxxxxxxx',
    'DB_PASSWORD' => 'CHANGE_ME',
    'API_SECRET' => 'GENERATE_A_LONG_RANDOM_SECRET_HERE',
    // Exact WordPress origin, e.g. https://cubashop.wordpress.com
    'CORS_ORIGIN' => 'https://cubashop.wordpress.com',
];
