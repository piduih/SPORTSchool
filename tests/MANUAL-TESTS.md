# M0 Manual Smoke Tests

- [ ] PHP version meets declared baseline.
- [ ] Required PHP extensions are enabled.
- [ ] Root page loads without PHP warnings.
- [ ] /health returns status ok with a working database.
- [ ] /health returns HTTP 503 if database credentials are invalid.
- [ ] .env is outside the public document root.
- [ ] Migration runner cannot be invoked over HTTP.
- [ ] CLI migration creates foundation tables.
- [ ] Second migration run skips the applied migration.
- [ ] Git status does not show .env, logs or uploaded files.
- [ ] HTTPS session cookies are Secure, HttpOnly and SameSite=Lax.
