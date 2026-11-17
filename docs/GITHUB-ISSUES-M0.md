# GitHub Issues Draft — M0 Foundation

Repository: piduih/SPORTSchool  
Milestone: M0 — Foundation & Architecture

## Labels
- milestone:m0
- type:feature
- type:chore
- type:security
- type:documentation
- priority:high
- priority:medium

## Issue 1 — Bootstrap native PHP project structure
- Establish native PHP directory structure and autoloader.
- Add front controller, routing and environment template.
- Acceptance: root page loads; private configuration is not web-accessible; no Composer or Node runtime required.

## Issue 2 — Environment configuration and secure defaults
- Load environment variables, configure timezone and secure session cookies.
- Acceptance: secrets excluded from Git; production debug disabled; safe errors.

## Issue 3 — PDO database connection
- Add PDO MySQL connection using native prepared statements.
- Acceptance: valid connection works; failed connection returns generic browser error; health endpoint returns 503 on failure.

## Issue 4 — SQL migration runner and M0 schema
- Add migration tracking and foundation tables.
- Acceptance: CLI only; repeat-safe; tenant, organisation, users, roles, permissions and audit tables created.

## Issue 5 — Tenant and organisation access boundaries
- Define tenant ownership and scoped role assignments.
- Acceptance: server-side tenant checks specified; cross-tenant isolation tests planned for M1.

## Issue 6 — M0 smoke tests and deployment documentation
- Document shared hosting layout, PHP extensions, migrations and backup process.
- Acceptance: new developer can configure a clean environment from README.
