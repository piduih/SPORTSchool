# SPORTSchool

**Athletics Management Platform**  
M0 foundation starter for native PHP + MySQL on shared hosting.

## Stack
- PHP 8.2+
- MySQL 8.0+ / compatible MariaDB
- PDO MySQL
- Vanilla JavaScript
- Bootstrap 5 (UI layer in later milestone)
- No Composer or Node.js requirement for M0

## M0
- Native PHP application skeleton
- Environment configuration
- PDO database connection with native prepared statements
- CLI-only SQL migration runner with migration tracking
- Tenant and organisation schema with same-tenant relational constraints
- Front controller and database health endpoint
- Session and CSRF foundations
- GitHub issue backlog

## Setup
1. Create a MySQL database and restricted database user.
2. Copy `.env.example` to `.env`.
3. Set database credentials and a unique random `APP_KEY` of at least 32 characters.
4. Point the web document root to `public/`.
5. Run from project root using PHP CLI: `php database/migrate.php`.
6. Test `/health`.

## Shared hosting
Keep `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `storage/` and `.env` outside `public_html`. Only expose files from `public/`. Never expose migration scripts, logs, secrets or backups.

## Security
M0 defines tenant ownership and database-level organisation boundaries. It does not yet implement authentication, tenant membership, authorization middleware, RBAC enforcement, rate limiting or production monitoring. Do not use real student data until these controls and privacy requirements are implemented.

See `docs/ENVIRONMENT.md`, `docs/DATABASE.md`, `docs/TENANCY-ACCESS-CONTROL.md`, `docs/SHARED-HOSTING-DEPLOYMENT.md` and `docs/GITHUB-ISSUES-M0.md`.
