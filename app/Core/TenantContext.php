<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class TenantContext
{
    public static function memberships(): array
    {
        if (!Auth::check()) {
            return [];
        }
        $query = Database::connection()->prepare(
            "SELECT t.id, t.name, t.deployment_mode
             FROM tenant_memberships tm
             INNER JOIN tenants t ON t.id = tm.tenant_id
             WHERE tm.user_id = :user_id
               AND tm.status = 'active'
               AND t.status = 'active'
             ORDER BY t.name ASC"
        );
        $query->execute(['user_id' => Auth::user()['id']]);
        return $query->fetchAll();
    }

    public static function select(int $tenantId): bool
    {
        if ($tenantId < 1 || !Auth::check()) {
            return false;
        }
        $query = Database::connection()->prepare(
            "SELECT t.id
             FROM tenant_memberships tm
             INNER JOIN tenants t ON t.id = tm.tenant_id
             WHERE tm.user_id = :user_id
               AND tm.tenant_id = :tenant_id
               AND tm.status = 'active'
               AND t.status = 'active'
             LIMIT 1"
        );
        $query->execute([
            'user_id' => Auth::user()['id'],
            'tenant_id' => $tenantId,
        ]);
        if (!$query->fetchColumn()) {
            return false;
        }
        $_SESSION['active_tenant_id'] = $tenantId;
        return true;
    }

    public static function current(): ?array
    {
        if (!Auth::check() || !isset($_SESSION['active_tenant_id'])) {
            return null;
        }
        $query = Database::connection()->prepare(
            "SELECT t.id, t.name, t.deployment_mode
             FROM tenant_memberships tm
             INNER JOIN tenants t ON t.id = tm.tenant_id
             WHERE tm.user_id = :user_id
               AND tm.tenant_id = :tenant_id
               AND tm.status = 'active'
               AND t.status = 'active'
             LIMIT 1"
        );
        $query->execute([
            'user_id' => Auth::user()['id'],
            'tenant_id' => (int) $_SESSION['active_tenant_id'],
        ]);
        $tenant = $query->fetch();
        if (!is_array($tenant)) {
            unset($_SESSION['active_tenant_id']);
            return null;
        }
        return $tenant;
    }

    public static function clear(): void
    {
        unset($_SESSION['active_tenant_id']);
    }
}
