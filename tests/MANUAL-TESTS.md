# M0 Manual Smoke Tests

## Runtime and configuration
- [ ] PHP version meets the declared baseline.
- [ ] Required PHP extensions are enabled, including pdo_mysql.
- [ ] Root page loads without PHP warnings.
- [ ] Production startup rejects a missing or placeholder APP_KEY.
- [ ] Production startup rejects APP_DEBUG=true.
- [ ] The real .env file is outside the public document root and is not tracked by Git.

## Database connection
- [ ] Valid database credentials allow /health to return HTTP 200 and database=connected.
- [ ] Invalid database credentials make /health return HTTP 503 with a generic response.
- [ ] Missing DB_DATABASE fails without exposing configuration values to the browser.
- [ ] Invalid DB_PORT and unsupported DB_CHARSET are rejected before connecting.
- [ ] A query using user-supplied values uses PDO prepare() and execute(), never SQL string concatenation.
- [ ] Repeated Database::connection() calls reuse the same PDO connection during the request.
- [ ] Database::disconnect() clears the cached connection.

## Migration and deployment
- [ ] Migration runner cannot be invoked over HTTP.
- [ ] CLI migration creates foundation tables.
- [ ] Second migration run skips the applied migration.
- [ ] Git status does not show .env, logs or uploaded files.
- [ ] HTTPS session cookies are Secure, HttpOnly and SameSite=Lax.
