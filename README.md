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
- PDO database connection
- SQL migration runner
- Front controller and health endpoint
- Session and CSRF foundations
- Tenant, organisation, users, roles and permissions schema
- GitHub issue backlog

## Setup
1. Create a MySQL database and restricted database user.
2. Copy `.env.example` to `.env`.
3. Set database credentials and a long random `APP_KEY`.
4. Point the web document root to `public/`.
5. Run from project root: `php database/migrate.php`.
6. Test `/health`.

## Shared hosting
Keep `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `storage/` and `.env` outside `public_html`. Only expose files from `public/`. Never expose migration scripts, logs, secrets or backups.

## Security
M0 is a foundation, not a complete production application. Authentication, RBAC enforcement, tenant isolation middleware, rate limiting and production monitoring are subsequent work. Do not use real student data until security controls and privacy requirements are implemented.

See `docs/ENVIRONMENT.md`, `docs/SHARED-HOSTING-DEPLOYMENT.md` and `docs/GITHUB-ISSUES-M0.md`.
