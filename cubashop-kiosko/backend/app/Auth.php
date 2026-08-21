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

    public static function issueUserToken(array $user, string $secret, int $ttl = 28800): string
    {
        $payload = ['uid'=>(int)$user['id'], 'role'=>(string)$user['role'], 'exp'=>time()+$ttl];
        $body = self::b64(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = self::b64(hash_hmac('sha256', $body, $secret, true));
        return $body.'.'.$sig;
    }

    public static function userFromToken(string $token, string $secret): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || $secret === '') return null;
        [$body,$sig] = $parts;
        $expected = self::b64(hash_hmac('sha256', $body, $secret, true));
        if (!hash_equals($expected, $sig)) return null;
        $payload = json_decode(self::unb64($body), true);
        if (!is_array($payload) || (int)($payload['exp'] ?? 0) < time() || (int)($payload['uid'] ?? 0) < 1) return null;
        return ['id'=>(int)$payload['uid'], 'role'=>(string)$payload['role']];
    }

    public static function requireApiSecret(array $env): void
    {
        $expected = (string)($env['API_SECRET'] ?? '');
        $provided = self::bearer();
        if ($expected === '' || $provided === null || !hash_equals($expected, $provided)) {
            Api::json(['ok'=>false,'error'=>'unauthorized'],401);
        }
    }

    public static function requireUser(array $env): array
    {
        $token = self::bearer();
        $user = $token ? self::userFromToken($token, (string)($env['API_SECRET'] ?? '')) : null;
        if (!$user) Api::json(['ok'=>false,'error'=>'unauthorized'],401);
        return $user;
    }

    private static function b64(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
    private static function unb64(string $value): string { return base64_decode(strtr($value, '-_', '+/')) ?: ''; }
}
