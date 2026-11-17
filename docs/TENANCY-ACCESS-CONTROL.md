# Tenant and Organisation Access Boundaries

## Ownership model

| Concept | Meaning | Security boundary |
|---|---|---|
| Tenant | SaaS customer or independent self-hosted installation | Primary data ownership boundary |
| Organisation | National/state/district/school/club unit within a tenant | Sub-scope inside one tenant |
| User | Account identity | Not automatically a tenant member |
| Role | Named capability bundle | Grants access only when assigned in a validated scope |

A school is an organisation, not a tenant. A single tenant can contain several organisations.

## Role assignment scopes

The `user_roles` table supports three intended shapes:

1. Platform scope: `tenant_id IS NULL AND organisation_id IS NULL`.
2. Tenant scope: `tenant_id IS NOT NULL AND organisation_id IS NULL`.
3. Organisation scope: both IDs are non-NULL and the organisation belongs to that tenant.

The schema rejects an organisation assignment without a tenant and uses a composite foreign key to prevent assigning an organisation from a different tenant. The `organisations` parent relationship also uses a composite foreign key, preventing cross-tenant parent-child links.

The schema does not establish user membership by itself. Before inserting or using a role assignment, application authorization code must verify the user's membership in the tenant and, for organisation scope, the user's allowed organisation scope. M0 does not yet include a tenant membership table or authorization middleware.

## Privilege boundaries

- Platform administrators are authorized only by a trusted platform-level assignment and server-side policy.
- Tenant administrators are limited to their validated tenant.
- Organisation administrators are limited to their validated organisation scope and any explicitly permitted descendants.
- A tenant administrator must not gain platform privileges.
- A user-supplied role name, tenant ID or organisation ID is input, not proof of authorization.

## Required query pattern

Tenant-owned repository methods in M1 must receive a trusted tenant context resolved from the authenticated principal. Tenant filtering must be part of the SQL statement:

```sql
SELECT id, name
FROM organisations
WHERE tenant_id = :trusted_tenant_id
  AND id = :organisation_id
LIMIT 1
```

For organisation-scoped writes, validate ownership in the same tenant context before writing. Avoid fetching by resource ID alone and checking ownership later.

## M1 cross-tenant isolation test plan

Use two tenants (A and B), separate organisations, and users with distinct memberships.

- [ ] Tenant A user cannot list, view, update or delete Tenant B records by changing request IDs.
- [ ] Tenant A cannot attach a Tenant B organisation as a child organisation.
- [ ] Tenant A cannot assign a role scoped to a Tenant B organisation.
- [ ] Tenant administrator cannot create or elevate a platform-level role assignment.
- [ ] Organisation administrator cannot access a sibling organisation unless policy explicitly grants it.
- [ ] Missing or invalid tenant membership is denied by default.
- [ ] Every tenant-owned list, detail, export, report and background job applies trusted tenant scope.
- [ ] Attempts to bypass UI controls with direct HTTP requests are denied server-side.

These are acceptance tests for M1 authorization implementation; they are not claims that M0 currently enforces application-level access.
