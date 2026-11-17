# M1 — Tenant Context & Authorization

## Tenant selection
- A signed-in user sees only active tenants connected by an active tenant_memberships row.
- Selecting a tenant submits its ID, but the server validates that ID against the authenticated user's membership and the tenant's active status.
- The selected tenant ID is stored in the server-side session.
- TenantContext::current() revalidates membership and tenant status against the database whenever the context is requested. Revoked membership or a suspended tenant clears the selected context.
- No tenant ID from a query string, form, cookie or header is accepted as authorization context.

## Permission checks
Authorization::allows() checks the authenticated user, the validated current tenant, the user's role assignments and role_permissions mapping. A tenant-level role applies across that tenant. An organisation-level role applies only when the requested organisation ID matches its assigned organisation. Missing context or permission is denied.

## Important limitations
- This slice does not implement descendant organisation inheritance.
- Platform-level super-admin assignments do not automatically bypass tenant permission checks. A platform administrator without an active tenant membership can sign in but cannot select a tenant in this slice; a separate platform administration area is not implemented.
- All tenant-owned controllers and repository queries must still include the validated tenant ID in SQL. A permission check alone does not filter returned rows.
- The current UI is intentionally minimal. Bootstrap 5 integration is a later UI task.
- Audit logging, permission administration UI and full cross-tenant integration tests remain outstanding.

## Required implementation pattern
1. Require authentication.
2. Resolve TenantContext::current(); redirect to tenant selection if absent.
3. Check Authorization::require() for the required permission.
4. Use the tenant ID returned by TenantContext::current() in every tenant-owned query and mutation.
5. For organisation resources, include both tenant ID and organisation ID in the SQL predicate.
