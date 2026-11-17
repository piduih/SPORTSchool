<?php
declare(strict_types=1);

namespace App\Core;

final class Authorization
{
    public static function allows(string $permissionKey, ?int $organisationId = null): bool
    {
        $user = Auth::user();
        $tenant = TenantContext::current();
        if ($user === null || $tenant === null || $permissionKey === '') {
            return false;
        }

        $sql = "SELECT 1
                FROM user_roles ur
                INNER JOIN roles r ON r.id = ur.role_id
                INNER JOIN role_permissions rp ON rp.role_id = r.id
                INNER JOIN permissions p ON p.id = rp.permission_id
                WHERE ur.user_id = :user_id
                  AND ur.tenant_id = :tenant_id
                  AND p.permission_key = :permission_key
                  AND (
                      ur.organisation_id IS NULL
                      OR (:organisation_id IS NOT NULL AND ur.organisation_id = :organisation_id)
                  )
                LIMIT 1";
        $query = Database::connection()->prepare($sql);
        $query->execute([
            'user_id' => $user['id'],
            'tenant_id' => $tenant['id'],
            'permission_key' => $permissionKey,
            'organisation_id' => $organisationId,
        ]);
        return (bool) $query->fetchColumn();
    }

    public static function require(string $permissionKey, ?int $organisationId = null): void
    {
        if (!self::allows($permissionKey, $organisationId)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }
    }
}
