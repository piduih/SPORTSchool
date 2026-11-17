# SPORTSchool M0 Architecture

## Architecture decision
M0 uses a dependency-free native PHP modular monolith for shared hosting.

- PHP 8.2+
- MySQL 8.0+ or provider-tested compatible MariaDB
- PDO with native prepared statements
- Vanilla JavaScript
- Bootstrap 5 UI layer in later milestones
- No Node.js runtime requirement
- No Composer dependency in the initial foundation

## Request lifecycle
Browser -> public/index.php -> bootstrap/app.php -> Controller/route handling -> service/repository layer (introduced later) -> PDO/MySQL.

## Deployment boundary
Only public/ is web-accessible. Application code, configuration, migrations, logs and backups remain outside the document root.

## Tenant model
A tenant represents one SaaS customer or one self-hosted installation. Organisations represent the hierarchy inside a tenant (national, state, district, school). Tenant filtering must be enforced server-side in every data query. Never trust a tenant ID supplied by a browser without checking authenticated membership.

## M0 limitations
M0 provides foundations only. Complete authentication, password reset, RBAC enforcement, tenant-aware repositories, rate limiting and production observability are later work.
