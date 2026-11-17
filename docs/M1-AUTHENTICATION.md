# M1 — Authentication & Tenant Membership

## Included in this slice
- Email/password authentication against active users.
- Password verification with PHP password_verify(); account creation must use password_hash().
- Session ID rotation after successful authentication.
- Logout clears session data and expires the session cookie.
- Generic failed-login response to avoid account enumeration.
- Database-backed throttle: at most five failed attempts per email or source IP in a rolling 15-minute window.
- Tenant membership records are separate from role assignments.
- A user must have an active tenant membership or the explicit platform-level super-admin role to sign in.

## Tenant context
Authentication establishes a user identity only. It does not select a tenant or grant tenant access by itself. A future tenant-selection step must load active memberships from the database and store only a validated tenant context server-side. Never trust a tenant ID supplied by a URL, form, header or browser storage.

## Deployment
1. Back up the database.
2. Apply migrations from the private project root using the CLI migration runner.
3. Ensure the web PHP runtime has PDO MySQL and sessions enabled.
4. Serve the app over HTTPS. The session cookie is Secure when HTTPS is detected by the current bootstrap.
5. Create the first user and grant membership/roles through a controlled administrative process. No default administrator is created.

## Deferred
- Registration and email verification.
- Password reset and outbound email.
- Tenant selection and tenant-aware authorization middleware.
- Complete RBAC enforcement and audit events for authentication.
- MFA, remember-me tokens and recovery codes.

## Security testing still required
Run against a disposable MySQL database and the target hosting configuration. Verify successful and failed login, inactive accounts, no-membership accounts, platform role, session ID rotation, logout cookie expiry, CSRF rejection and throttle behavior. The throttle is shared across users behind the same source IP, so tune only after operational testing.
