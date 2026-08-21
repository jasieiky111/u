<?php
declare(strict_types=1);

final class Authorization
{
    public static function requireRole(array $user, array $allowed): void
    {
        $role = (string)($user['role'] ?? '');
        if (!in_array($role, $allowed, true)) {
            Api::json(['ok'=>false,'error'=>'forbidden'],403);
        }
    }

    public static function workerOrAdmin(array $user, int $resourceUserId): void
    {
        if (($user['role'] ?? '') === 'worker' && (int)($user['id'] ?? 0) !== $resourceUserId) {
            Api::json(['ok'=>false,'error'=>'forbidden'],403);
        }
    }
}
