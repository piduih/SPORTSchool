# M1.3 — Tenant User & Role Assignment

## Scope delivered
- Tenant administrators with `user.manage` can view memberships for the selected tenant.
- They can activate an existing SPORTSchool user account as a member of the selected tenant, or change that membership to active, suspended or removed.
- Users with `role.manage` can assign a restricted set of operational roles: competition_manager, technical_officer, judge, team_manager and viewer.
- Role assignments are tenant-scoped or scoped to an active organisation belonging to the selected tenant.
- Membership and role changes write audit events to the existing audit_logs table in the same database transaction.
- Mutations require a valid CSRF token and the relevant permission. The selected tenant is resolved from the server-side session.

## Deliberate security boundaries
- This slice does not create user accounts, send invitations, set passwords or verify email. An account must already exist. Self-registration is not exposed.
- Tenant administrators cannot assign platform_super_admin, tenant_admin or organisation_admin through this interface. Privileged administrator provisioning requires a separate controlled platform workflow.
- Roles and role_permissions remain global system definitions. This slice does not let tenant administrators create roles or edit permission mappings.
- Membership removal/suspension affects access to this tenant only; it does not disable the user's global account or affect other tenants.
- This is not a complete administration console. Role revocation is available in the service layer but a dedicated UI is not included yet.
- The current form accepts an organisation ID. The server validates that it belongs to the selected active tenant; it never trusts the submitted ID by itself.

## Routes
- `GET /admin/users` — list tenant memberships; requires `user.manage`.
- `POST /admin/users/membership` — activate an existing account's membership; requires `user.manage`.
- `POST /admin/users/membership-status` — change membership status; requires `user.manage`.
- `POST /admin/users/role` — assign an allowed role; requires `role.manage`.

## Operational notes
The first administrator and initial role grants still need to be provisioned through a controlled database/bootstrap procedure. Do not expose SQL administration or create a public default administrator. Review the audit log and authorization policy before using real school, athlete or student data.

## Acceptance status
The code is committed to the development branch. Real MySQL/MariaDB integration, multi-tenant authorization tests, browser CSRF tests and target-hosting verification remain pending. Do not treat this milestone as production-ready until those checks pass.
