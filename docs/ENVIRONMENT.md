# Environment Configuration

SPORTSchool reads environment values from the process environment first, then from a private project-root .env file. Existing process environment values are not overwritten.

| Variable | Purpose | Production requirement |
|---|---|---|
| APP_ENV | Runtime environment | production |
| APP_DEBUG | Detailed error output | false |
| APP_URL | Canonical application URL | HTTPS URL |
| APP_TIMEZONE | PHP timezone | Asia/Kuala_Lumpur by default |
| APP_KEY | Per-installation secret | At least 32 random characters |
| DB_HOST | Database server | Provider value |
| DB_PORT | Database port | Usually 3306 |
| DB_DATABASE | Database name | Required for DB access |
| DB_USERNAME | Restricted database user | Required for DB access |
| DB_PASSWORD | Database password | Required for DB access |
| DB_CHARSET | Connection character set | utf8mb4 |

## Defaults and validation
- Production is the default environment when APP_ENV is absent.
- Debug output is disabled unless explicitly enabled.
- Production startup fails if APP_KEY is shorter than 32 characters or debug is enabled.
- Session cookies use HttpOnly and SameSite=Lax. Secure is enabled when PHP detects HTTPS.
- The application logs uncaught exceptions and returns a generic production error message.

## Important
The built-in .env reader is intentionally small. Use simple KEY=value lines. It supports optional export, single/double quoted values and unquoted trailing comments. It is not a full dotenv implementation and does not perform variable interpolation.

Never commit the real .env file or share APP_KEY/database credentials.
