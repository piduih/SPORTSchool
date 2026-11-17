# Shared Hosting Deployment

## Requirements
- PHP 8.2+
- pdo_mysql
- mbstring
- openssl
- fileinfo
- MySQL 8.0+ or compatible MariaDB
- HTTPS
- Apache mod_rewrite if using the included .htaccess

## Recommended layout
Keep app/, bootstrap/, config/, database/, docs/, storage/ and .env outside public_html. Expose only the contents of public/. If the hosting panel supports changing document root, point it to the project's public/ directory.

## Setup
1. Create a database and restricted database user.
2. Upload private project files outside the document root.
3. Upload only public entry files to the document root if needed.
4. Configure environment variables or a private .env.
5. Set APP_DEBUG=false in production.
6. Generate a unique random APP_KEY.
7. Apply SQL migrations using SSH/CLI or a trusted database administration tool.
8. Test /health.
9. Enable HTTPS and verify secure session cookies.
10. Configure database and file backups.

Never expose migration scripts, secrets, logs or backups through the web server.
