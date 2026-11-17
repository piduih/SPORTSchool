# Shared Hosting Deployment Runbook

This guide targets PHP shared hosting with Apache, PHP CLI/SSH where available, and MySQL or MariaDB.

## 1. Requirements

- PHP 8.2 or newer.
- PHP extensions: pdo_mysql, mbstring, openssl and fileinfo.
- MySQL 8.0+ or a MariaDB version tested by the hosting provider.
- HTTPS enabled for the domain.
- Apache `mod_rewrite` if relying on the included front-controller rewrite rules.
- PHP CLI or SSH is recommended for running migrations and backups.

Check the PHP CLI version and loaded modules:

```sh
php -v
php -m
```

The PHP version and extensions used by the website may differ from CLI PHP. Confirm both in the hosting control panel or with a temporary, access-restricted diagnostic script, then delete that script.

## 2. Recommended directory layout

Preferred layout when the hosting provider permits a custom document root:

```text
/home/ACCOUNT/
  sportsschool/             # Private project root
    app/
    bootstrap/
    config/
    database/
    docs/
    storage/
    tests/
    .env
  public_html/               # Web document root
    index.php
    .htaccess
```

The repository's `public/` directory is the intended web root. Configure the domain document root to the repository's `public/` directory, or deploy only the contents of `public/` into the provider's document root and adjust the front controller's bootstrap path to the actual private project location.

Do not copy the complete repository into a publicly served directory. In particular, keep `.env`, `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `storage/` and `tests/` outside the web root.

If the provider forces the project under `public_html`, place private directories outside it and expose only the public entry files. If this separation is impossible, ask the hosting provider for a supported private directory arrangement rather than relying only on dotfile hiding or rewrite rules.

## 3. Configure the application

1. Create a MySQL database and a dedicated database user.
2. Grant the user only the privileges required by the application and migrations. Migration setup needs schema creation and modification privileges; runtime application credentials should be more restricted where practical.
3. Upload the private project files outside the document root.
4. Create `.env` in the private project root by copying `.env.example`.
5. Set production values. Never commit or send the real file.

Example configuration keys (use the exact database host and credentials from the provider):

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_TIMEZONE=Asia/Kuala_Lumpur
APP_KEY=PASTE_A_UNIQUE_RANDOM_VALUE_HERE

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=database_name
DB_USERNAME=database_user
DB_PASSWORD=database_password
DB_CHARSET=utf8mb4
```

Generate a key locally with PHP CLI:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Use the generated 64-character hexadecimal value for `APP_KEY`. Do not use the example placeholder above.

## 4. Apply database migrations

From the private project root, run:

```sh
php database/migrate.php
```

The runner is CLI-only. It creates the migration tracking table and applies sorted SQL files not already recorded.

If SSH/CLI is unavailable, do not upload the migration script into the web root or create a public migration URL. Use a trusted database administration method to apply reviewed SQL manually, then verify the schema and migration tracking state. MySQL DDL can partially apply before an error; inspect the database before retrying a failed migration.

## 5. Verify deployment

1. Open the domain over HTTPS.
2. Open `https://example.com/health`.
3. With valid DB configuration, expect HTTP 200 and JSON reporting `status=ok` and `database=connected`.
4. With a database outage, expect HTTP 503 and a generic unavailable response. Do not intentionally expose production credentials to test this.
5. Check server error logs through the hosting control panel.
6. Verify the session cookie uses Secure, HttpOnly and SameSite=Lax when a web session is started.
7. Confirm private project paths and `.env` are not reachable through the browser.

The health endpoint checks database connectivity using `SELECT 1`. It does not prove that every table, permission or business workflow is correct.

## 6. Backups and restore

Back up the database and uploaded application files separately. Store backups outside the public document root and, where possible, off the hosting account. Encrypt backups containing sensitive information and restrict access.

With SSH and MySQL client tools, an operator can use:

```sh
mysqldump --single-transaction --routines --triggers --default-character-set=utf8mb4 -h DB_HOST -u DB_USERNAME -p DB_DATABASE > database-backup.sql
```

The command prompts for the password. Do not put passwords directly in shell history or commit a dump to Git.

Restore to a newly created or explicitly selected database only after verifying the target:

```sh
mysql -h DB_HOST -u DB_USERNAME -p DB_DATABASE < database-backup.sql
```

If command-line tools are unavailable, use the hosting provider's supported database backup/export and import facility. Confirm upload/file backups are included if the application later stores user uploads.

Define and document a backup schedule, retention period, responsible operator and recovery objective before production use. Periodically perform a restore test in a separate non-production database. A backup is not verified until restoration has been tested.

## 7. Permissions and operational security

- Use the least permissive file and directory permissions supported by the host.
- Do not make the entire project world-writable.
- Give write access only to specific runtime storage directories when required.
- Keep debug disabled in production.
- Never expose database dumps, logs, backups, environment files or source directories.
- Use HTTPS and rotate credentials if they are exposed.
- Review logs after deployment and after failed migrations.

## 8. M0 limitations

M0 is a technical foundation, not a production-ready sports management system. Authentication, password reset, tenant membership, authorization middleware, complete RBAC enforcement, tenant-aware repositories, rate limiting, monitoring and application backup automation are not implemented yet.

Do not store real student/athlete personal data until access controls, privacy requirements, operational backups and security testing have been implemented and reviewed.
