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

## M0 and M1 foundation
- Native PHP application skeleton
- Environment configuration
- PDO database connection with native prepared statements
- CLI-only SQL migration runner with migration tracking
- Tenant and organisation schema with same-tenant relational constraints
- Front controller and database health endpoint
- Session and CSRF foundations
- M1 email/password login, logout, session rotation and login throttling
- Tenant membership schema separated from role assignments
- Server-validated tenant selection and session context
- Tenant-scoped permission lookup foundation
- GitHub issue backlog

## Quick setup

1. Create a MySQL database and a dedicated database user.
2. Copy `.env.example` to `.env` in the private project root.
3. Set `APP_ENV=production`, `APP_DEBUG=false`, database credentials and a unique random `APP_KEY` of at least 32 characters.
4. Configure the domain document root to expose only the contents of `public/`.
5. Confirm PHP 8.2+ and the required extensions are enabled: `pdo_mysql`, `mbstring`, `openssl` and `fileinfo`. Check web PHP and CLI PHP separately.
6. From the private project root, run `php database/migrate.php` using PHP CLI.
7. Open `https://your-domain/health`. Expect HTTP 200 and `database=connected` when database configuration is valid.

Generate an APP_KEY with:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Never commit the real `.env` file or paste secrets into GitHub issues.

## Shared hosting

Keep `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `storage/`, `tests/` and `.env` outside the web document root. Expose only `public/`. Never expose migration scripts, logs, secrets or backups.

Read [the shared-hosting deployment runbook](docs/SHARED-HOSTING-DEPLOYMENT.md) for directory layout, migration, HTTPS, permissions, backup and restore instructions.

## Security and M0 limitations

M1 adds basic authentication and tenant membership records. Tenant selection, tenant-aware authorization middleware, complete RBAC enforcement, MFA, password reset, monitoring and automated backup/restore remain outstanding. Authentication is not a substitute for tenant authorization. Do not use real student data until these controls and privacy requirements are implemented and reviewed.

Read [M1 authentication notes](docs/M1-AUTHENTICATION.md) and [tenant context notes](docs/M1-TENANT-CONTEXT.md) before enabling login in a live environment. Do not use real student data until these controls and privacy requirements are implemented and reviewed.

Run the [manual smoke-test checklist](tests/MANUAL-TESTS.md) in a disposable development environment. Database and hosting acceptance checks require a real MySQL/MariaDB service and target hosting account.

See `docs/ENVIRONMENT.md`, `docs/DATABASE.md`, `docs/TENANCY-ACCESS-CONTROL.md`, `docs/SHARED-HOSTING-DEPLOYMENT.md` and `docs/GITHUB-ISSUES-M0.md`.
