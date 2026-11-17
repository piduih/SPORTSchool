<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class UserManagement
{
    private const ASSIGNABLE_ROLES = [
        'competition_manager',
        'technical_officer',
        'judge',
        'team_manager',
        'viewer',
    ];

    public static function users(int $tenantId): array
    {
        $query = Database::connection()->prepare(
            "SELECT u.id, u.name, u.email, u.status AS user_status, tm.status AS membership_status
             FROM tenant_memberships tm
             INNER JOIN users u ON u.id = tm.user_id
             WHERE tm.tenant_id = :tenant_id
             ORDER BY u.name, u.email"
        );
        $query->execute(['tenant_id' => $tenantId]);
        return $query->fetchAll();
    }

    public static function roles(): array
    {
        $query = Database::connection()->prepare(
            'SELECT id, role_key, name FROM roles WHERE role_key IN (' .
            implode(',', array_fill(0, count(self::ASSIGNABLE_ROLES), '?')) .
            ') ORDER BY name'
        );
        $query->execute(self::ASSIGNABLE_ROLES);
        return $query->fetchAll();
    }

    public static function addMembership(int $tenantId, string $email): void
    {
        $email = strtolower(trim($email));
        if ($tenantId < 1 || $email === '' || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Invalid user email or tenant.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $userQuery = $pdo->prepare('SELECT id, status FROM users WHERE email = :email LIMIT 1 FOR UPDATE');
            $userQuery->execute(['email' => $email]);
            $user = $userQuery->fetch();
            if (!is_array($user)) {
                throw new RuntimeException('User account not found. Account registration and invitation are not part of this release.');
            }

            if ($user['status'] !== 'active') {
                throw new RuntimeException('Only an active user account can be added to a tenant.');
            }
            $userId = (int) $user['id'];

            $tenantQuery = $pdo->prepare("SELECT id FROM tenants WHERE id = :tenant_id AND status = 'active' LIMIT 1");
            $tenantQuery->execute(['tenant_id' => $tenantId]);
            if (!$tenantQuery->fetchColumn()) {
                throw new RuntimeException('Active tenant not found.');
            }

            $membership = $pdo->prepare(
                "INSERT INTO tenant_memberships (tenant_id, user_id, status)
                 VALUES (:tenant_id, :user_id, 'active')
                 ON DUPLICATE KEY UPDATE status = 'active', updated_at = CURRENT_TIMESTAMP"
            );
            $membership->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
            self::audit($pdo, $tenantId, 'membership.activated', 'user', (int) $userId, null, ['email' => $email]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function setMembershipStatus(int $tenantId, int $userId, string $status): void
    {
        if (!in_array($status, ['active', 'suspended', 'removed'], true) || $tenantId < 1 || $userId < 1) {
            throw new RuntimeException('Invalid membership change.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare(
                'SELECT status FROM tenant_memberships WHERE tenant_id = :tenant_id AND user_id = :user_id FOR UPDATE'
            );
            $query->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
            $old = $query->fetchColumn();
            if (!is_string($old)) throw new RuntimeException('Tenant membership not found.');
            $update = $pdo->prepare(
                'UPDATE tenant_memberships SET status = :status WHERE tenant_id = :tenant_id AND user_id = :user_id'
            );
            $update->execute(['status' => $status, 'tenant_id' => $tenantId, 'user_id' => $userId]);
            self::audit($pdo, $tenantId, 'membership.status_changed', 'user', $userId, ['status' => $old], ['status' => $status]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function assignRole(int $tenantId, int $userId, string $roleKey, ?int $organisationId): void
    {
        if ($tenantId < 1 || $userId < 1 || !in_array($roleKey, self::ASSIGNABLE_ROLES, true)) {
            throw new RuntimeException('Role assignment is not permitted.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $member = $pdo->prepare(
                "SELECT 1 FROM tenant_memberships
                 WHERE tenant_id = :tenant_id AND user_id = :user_id AND status = 'active' FOR UPDATE"
            );
            $member->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
            if (!$member->fetchColumn()) throw new RuntimeException('User has no active membership in this tenant.');

            if ($organisationId !== null) {
                $org = $pdo->prepare(
                    "SELECT 1 FROM organisations
                     WHERE id = :organisation_id AND tenant_id = :tenant_id AND status = 'active' LIMIT 1"
                );
                $org->execute(['organisation_id' => $organisationId, 'tenant_id' => $tenantId]);
                if (!$org->fetchColumn()) throw new RuntimeException('Organisation does not belong to this active tenant.');
            }

            $role = $pdo->prepare('SELECT id FROM roles WHERE role_key = :role_key LIMIT 1');
            $role->execute(['role_key' => $roleKey]);
            $roleId = $role->fetchColumn();
            if (!$roleId) throw new RuntimeException('Role not found.');

            $existing = $pdo->prepare(
                'SELECT id FROM user_roles
                 WHERE tenant_id = :tenant_id AND user_id = :user_id AND role_id = :role_id
                   AND ' . ($organisationId === null ? 'organisation_id IS NULL' : 'organisation_id = :organisation_id') . '
                 LIMIT 1'
            );
            $params = ['tenant_id' => $tenantId, 'user_id' => $userId, 'role_id' => $roleId];
            if ($organisationId !== null) $params['organisation_id'] = $organisationId;
            $existing->execute($params);
            if ($existing->fetchColumn()) throw new RuntimeException('Role is already assigned in this scope.');

            $insert = $pdo->prepare(
                'INSERT INTO user_roles (tenant_id, organisation_id, user_id, role_id)
                 VALUES (:tenant_id, :organisation_id, :user_id, :role_id)'
            );
            $insert->execute([
                'tenant_id' => $tenantId,
                'organisation_id' => $organisationId,
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
            self::audit($pdo, $tenantId, 'role.assigned', 'user_role', (int) $pdo->lastInsertId(), null, [
                'user_id' => $userId, 'role_key' => $roleKey, 'organisation_id' => $organisationId,
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function removeRole(int $tenantId, int $assignmentId): void
    {
        if ($tenantId < 1 || $assignmentId < 1) throw new RuntimeException('Invalid role assignment.');
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare(
                "SELECT ur.user_id, r.role_key, ur.organisation_id
                 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id
                 WHERE ur.id = ? AND ur.tenant_id = ?
                   AND r.role_key IN (" . implode(',', array_fill(0, count(self::ASSIGNABLE_ROLES), '?')) . ")
                 FOR UPDATE"
            );
            $query->execute(array_merge([$assignmentId, $tenantId], self::ASSIGNABLE_ROLES));
            $row = $query->fetch();
            if (!is_array($row)) throw new RuntimeException('Role assignment not found or not manageable.');
            $delete = $pdo->prepare('DELETE FROM user_roles WHERE id = :id AND tenant_id = :tenant_id');
            $delete->execute(['id' => $assignmentId, 'tenant_id' => $tenantId]);
            self::audit($pdo, $tenantId, 'role.revoked', 'user_role', $assignmentId, [
                'user_id' => (int) $row['user_id'], 'role_key' => $row['role_key'],
                'organisation_id' => $row['organisation_id'] === null ? null : (int) $row['organisation_id'],
            ], null);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function audit(PDO $pdo, int $tenantId, string $action, string $entityType, int $entityId, ?array $old, ?array $new): void
    {
        $actor = Auth::user();
        $query = $pdo->prepare(
            'INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
             VALUES (:tenant_id, :user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip, :agent)'
        );
        $query->execute([
            'tenant_id' => $tenantId,
            'user_id' => $actor['id'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old === null ? null : json_encode($old, JSON_THROW_ON_ERROR),
            'new_values' => $new === null ? null : json_encode($new, JSON_THROW_ON_ERROR),
            'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }
}
