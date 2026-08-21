<?php
declare(strict_types=1);

final class Auth
{
    public static function bearer(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return null;
        return trim($m[1]);
    }

    public static function requireApiSecret(array $env): void
    {
        $expected = (string)($env['API_SECRET'] ?? '');
        $provided = self::bearer();
        if ($expected === '' || $provided === null || !hash_equals($expected, $provided)) {
            Api::json(['ok'=>false,'error'=>'unauthorized'],401);
        }
    }
}
