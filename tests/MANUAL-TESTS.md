# M0 Manual Smoke Tests

Run these checks in a disposable development environment first. Do not use real student/athlete records.

## 1. Runtime and configuration

- [ ] Web PHP runtime is PHP 8.2 or newer.
- [ ] Web PHP runtime has pdo_mysql, mbstring, openssl and fileinfo enabled.
- [ ] PHP CLI version and extensions are checked separately from the web runtime.
- [ ] Root page loads without PHP warnings or notices.
- [ ] Production startup rejects a missing or placeholder APP_KEY.
- [ ] Production startup rejects APP_DEBUG=true.
- [ ] A real .env file exists only in the private project root and is not tracked by Git.
- [ ] .env, source directories, tests, logs and backups are not reachable through HTTP.

## 2. Front controller and health endpoint

- [ ] GET / redirects unauthenticated visitors to /login and shows the authenticated home page after sign-in.
- [ ] GET /health with valid database credentials returns HTTP 200 and database=connected.
- [ ] GET /health during a controlled database outage returns HTTP 503 with a generic response.
- [ ] Health responses do not disclose credentials, DSN values, SQL statements or stack traces.
- [ ] Unknown paths return HTTP 404.
- [ ] HTTPS is enabled and the domain redirects or otherwise enforces HTTPS according to host configuration.

## 3. Database connection

- [ ] Missing DB_DATABASE fails without exposing configuration values to the browser.
- [ ] Invalid DB_PORT and unsupported DB_CHARSET are rejected before connecting.
- [ ] A query using user-supplied values uses PDO prepare() and execute(), never SQL string concatenation.
- [ ] Repeated Database::connection() calls reuse the same PDO connection during the request.
- [ ] Database::disconnect() clears the cached connection.

## 4. Migration runner

- [ ] Migration runner rejects an HTTP request before loading application bootstrap code.
- [ ] CLI migration creates schema_migrations and the M0 foundation tables.
- [ ] Successful migration is recorded with its filename and batch.
- [ ] Second migration run skips the already-recorded migration.
- [ ] SQL semicolons inside quoted strings do not split a statement.
- [ ] SQL line and block comments are ignored by the statement splitter.
- [ ] A failed migration is not inserted into schema_migrations.
- [ ] If DDL partially executes before failure, database state is inspected and repaired before retrying.

## 5. Tenant schema boundaries

- [ ] An organisation cannot reference a parent organisation in another tenant.
- [ ] An organisation-scoped user role cannot reference an organisation in another tenant.
- [ ] An organisation-scoped user role cannot omit tenant_id.
- [ ] Platform, tenant and organisation role-scope semantics match docs/TENANCY-ACCESS-CONTROL.md.

## 6. Shared-hosting deployment and backup

- [ ] Web document root exposes only public entry files.
- [ ] Private source, configuration, migration files, .env, logs and backups are outside the document root.
- [ ] Database user is dedicated and has only required privileges.
- [ ] Session cookie is Secure, HttpOnly and SameSite=Lax when a web session is started over HTTPS.
- [ ] Database backup is created using the hosting provider's supported method.
- [ ] Backup is stored outside the public document root and access is restricted.
- [ ] Restore is tested against a separate non-production database.
- [ ] PHP CLI and pdo_mysql are available if CLI migrations are part of deployment.

## Acceptance notes

Record the test date, PHP web version, PHP CLI version, database product/version, hosting provider and pass/fail result. Do not include credentials, APP_KEY values, personal data or database dumps in the test report.

Tests requiring a real MySQL/MariaDB service or the target hosting account remain pending until executed in that environment.


## 7. M1 authentication (disposable database only)

- [ ] Apply migration 002 after migration 001.
- [ ] Login page renders and issues a CSRF token.
- [ ] POST login without a valid CSRF token returns HTTP 419.
- [ ] Active user with correct password and active tenant membership can sign in.
- [ ] Incorrect password, pending/disabled account and user without active membership receive the same generic failure message.
- [ ] Explicit platform-level platform_super_admin assignment permits sign-in without tenant membership.
- [ ] Successful login rotates the session ID.
- [ ] Five failed attempts within 15 minutes trigger throttling by email or source IP.
- [ ] Logout requires CSRF and expires the session cookie.
- [ ] Authenticated home page does not accept a browser-supplied tenant ID as authorization context.
- [ ] Confirm session cookie Secure flag over HTTPS on the target host.

M1 runtime and MySQL acceptance checks remain pending until executed against a real test database and target hosting environment.
