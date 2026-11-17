# SPORTSchool M0 Architecture

## Architecture decision
M0 uses a dependency-free native PHP modular monolith for shared hosting.

- PHP 8.2+
- MySQL 8.0+ or provider-tested compatible MariaDB
- PDO with native prepared statements
- Vanilla JavaScript
- Bootstrap 5 UI layer in later milestones
- No Node.js runtime requirement
- No Composer dependency in the initial foundation

## Request lifecycle
Browser -> public/index.php -> bootstrap/app.php -> Controller/route handling -> service/repository layer (introduced later) -> PDO/MySQL.

## Deployment boundary
Only public/ is web-accessible. Application code, configuration, migrations, logs and backups remain outside the document root.

## Tenant and organisation ownership

- A **tenant** is the security and data-ownership boundary. In SaaS mode it represents one customer account; in self-hosted mode it represents one independent installation.
- An **organisation** is a unit inside exactly one tenant. The hierarchy can represent national, state, district, school, club or other units.
- An organisation cannot be parented under an organisation belonging to another tenant. The M0 schema enforces this with a composite foreign key on (tenant_id, parent_id).
- Tenant-owned rows must carry tenant_id. Queries must scope by the authenticated tenant on the server, not by a tenant ID supplied by a browser.
- A school is an organisation with organisation_type = school. It is not a tenant.

## Role assignment semantics

- A platform-level assignment has tenant_id = NULL and organisation_id = NULL. Only the platform authorization layer may create or use this scope.
- A tenant-level assignment has tenant_id set and organisation_id = NULL.
- An organisation-level assignment has both tenant_id and organisation_id set. A composite foreign key ensures the organisation belongs to the same tenant.
- A user role assignment must be validated against the authenticated user's membership and the requested scope. The schema does not itself prove that a user is a tenant member or that a role is appropriate for the actor.
- Platform super-admin privileges must not be inferred from a browser-supplied role key or tenant ID.

## Server-side authorization requirements

For every tenant-owned read or write:
1. Resolve the authenticated principal from the server-side session/token.
2. Resolve the tenant membership and allowed organisation scope from trusted database records.
3. Derive the effective tenant ID from that validated context.
4. Include tenant_id in every tenant-owned query and mutation.
5. For organisation-scoped operations, validate both tenant_id and organisation_id together.
6. Deny by default when membership, scope or resource ownership is missing or ambiguous.
7. Never accept a browser-provided tenant ID as authorization evidence.

Repositories/services introduced in M1 must require an explicit trusted tenant context; controllers must not pass raw request tenant IDs directly into unrestricted queries.

## M0 limitations
M0 defines ownership boundaries and database relationships. It does not yet implement authentication, tenant membership tables, authorization middleware, repository guards or complete RBAC enforcement. Those are required before real multi-tenant data is used. Cross-tenant isolation tests are specified for M1.
