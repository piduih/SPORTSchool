# Shared Hosting Deployment

## Requirements
- PHP 8.2+
- pdo_mysql, mbstring, openssl and fileinfo
- MySQL 8.0+ or provider-tested compatible MariaDB
- HTTPS
- Apache mod_rewrite if using the included .htaccess

## Recommended layout
Keep app/, bootstrap/, config/, database/, docs/, storage/ and .env outside public_html. Expose only the contents of public/. If the hosting panel supports changing document root, point it to the project's public/ directory.

If the hosting provider forces the project into public_html, place private application directories outside public_html where possible and expose only public entry files. Do not rely on hidden-file rules as the primary protection for secrets.

## Environment setup
1. Create a database and restricted database user.
2. Upload private project files outside the document root.
3. Create the real .env on the server. Do not upload it to GitHub.
4. Set APP_ENV=production and APP_DEBUG=false.
5. Generate a unique APP_KEY with at least 32 random characters.
6. Set DB credentials using the hosting provider's exact values.
7. Set minimum required file and directory permissions.
8. Apply SQL migrations using SSH/CLI or a trusted database administration tool.
9. Test /health and review server error logs.
10. Enable HTTPS and verify session cookies have Secure, HttpOnly and SameSite=Lax attributes.
11. Configure database and file backups.

## Security rules
- Never commit .env, credentials, private keys, database dumps or real student data.
- Keep migration scripts, logs, backups and application source outside the web document root.
- Production errors are logged server-side; visitors receive a generic error message.
- Restrict database privileges to those required by the application.
- Rotate credentials immediately if exposed.

## Generate an application key
Run this locally using PHP CLI:
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"

Copy the generated value into the server-side .env as APP_KEY. Do not paste it into an issue, commit or chat.
