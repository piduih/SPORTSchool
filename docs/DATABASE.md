# SPORTSchool M0 Database

Engine: MySQL 8.0+ or provider-tested compatible MariaDB, InnoDB, utf8mb4.

Run migrations from CLI: `php database/migrate.php`.

M0 tables:
- tenants: SaaS customer or self-hosted installation
- organisations: national/state/district/school hierarchy
- users: staff and account identities
- roles, permissions, role_permissions: access-control definitions
- user_roles: scoped role assignment
- audit_logs: audit events

A tenant can contain multiple organisations. A school is an organisation with organisation_type = school. Application code must validate tenant and organisation scope before granting access. Student/athlete personal data is intentionally excluded from M0.
