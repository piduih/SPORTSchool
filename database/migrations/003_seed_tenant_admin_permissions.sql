-- SPORTSchool M1.3 baseline tenant administration permissions
-- Apply after migrations 001 and 002. Safe to rerun.

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.permission_key IN ('user.manage', 'role.manage')
WHERE r.role_key = 'tenant_admin'
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
