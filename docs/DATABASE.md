# SPORTSchool M0 Database

Engine: MySQL 8.0+ or provider-tested compatible MariaDB, InnoDB, utf8mb4.

Run migrations from the project root using PHP CLI:

```sh
php database/migrate.php
```

The migration runner:
- Refuses web requests before loading application bootstrap code.
- Reads only sorted `database/migrations/*.sql` files.
- Creates `schema_migrations` to record successfully applied filenames and batches.
- Skips filenames already recorded, making successful runs repeat-safe.
- Splits statements on semicolons outside quoted strings/identifiers and SQL comments.
- Records a migration only after all its statements execute successfully.

Migration files must use ordinary SQL statements. Custom `DELIMITER` directives, stored procedures and triggers are not supported by this runner.

**MySQL DDL limitation:** statements such as `CREATE TABLE` can implicitly commit. The runner therefore does not claim an entire migration is atomic. If a migration fails after some DDL has executed, it remains unrecorded but the database might contain partial changes. Inspect and repair that database state before retrying; do not blindly rerun a partially applied DDL migration.

## Connection layer

Use `App\\Core\\Database::connection()` to obtain the request-scoped PDO connection.

- PDO exceptions are enabled.
- Native prepared statements are enabled with `PDO::ATTR_EMULATE_PREPARES=false`.
- Fetch mode defaults to associative arrays.
- Persistent connections are disabled.
- Host, port, database name, username and charset are validated before building the DSN.
- Only utf8mb4 is accepted.
- Connection failures are wrapped in a generic application exception. The original PDO exception is retained as the previous exception for server-side diagnostics.

Example for values supplied by a user:

```php
$statement = Database::connection()->prepare(
    'SELECT id, email FROM users WHERE email = :email LIMIT 1'
);
$statement->execute(['email' => $email]);
$user = $statement->fetch();
```

Never concatenate user input into SQL. Table names and column names cannot be bound as parameters; select those only from trusted application constants or allow-lists.

## M0 tables

- tenants: SaaS customer or self-hosted installation
- organisations: national/state/district/school hierarchy
- users: staff and account identities
- roles, permissions, role_permissions: access-control definitions
- user_roles: scoped role assignment
- audit_logs: audit events

A tenant can contain multiple organisations. A school is an organisation with organisation_type = school. Application code must validate tenant and organisation scope before granting access. Student/athlete personal data is intentionally excluded from M0.
