# Phase 28 — Security Hardening

Phase 28 performs a focused hardening pass across authentication, admin access, sessions, headers, plugin upload safety, and privacy export download safety.

## Implemented Scope

- Added `EnsureAdminTwoFactorConfigured` middleware.
- Added `FORCE_ADMIN_TWO_FACTOR` configuration with protected admin roles forced to configure TOTP before using admin routes.
- Added stricter security headers:
  - Content Security Policy
  - Cross-Origin-Opener-Policy
  - X-Permitted-Cross-Domain-Policies
  - existing frame, MIME, referrer, permissions, and HSTS behavior retained
- Enabled encrypted session payloads by default.
- Hardened plugin ZIP validation:
  - reject null-byte paths
  - reject dot-segment paths
  - reject additional unsafe parent path variants
  - enforce uncompressed ZIP size ceiling
  - preserve manifest and extension allowlist checks
- Hardened plugin target path confinement using normalized exact-root or root-child matching.
- Hardened privacy export downloads to require resolved file paths under `storage/app/private/privacy_exports`.
- Added security regression tests for the new controls.

## Security Review Summary

- Auth/session:
  - login throttling already exists
  - suspended login blocking already exists
  - encrypted session default added
  - protected admin 2FA enforcement added
- RBAC/admin:
  - admin groups remain permission-gated server-side
  - force-admin-2FA is ordered after the base admin dashboard permission so unauthorized users are denied before setup redirects
- Entitlements:
  - feature middleware remains server-side
  - no client-side entitlement grant paths were added
- Plugins:
  - upload remains Super Admin permission-gated
  - ZIP traversal and extraction controls were tightened
  - third-party PHP plugin execution risk remains documented
- Privacy:
  - downloads remain owner-scoped
  - resolved paths must stay inside private export storage
- Payments:
  - provider webhooks remain signature-verified and idempotent
  - browser checkout redirects do not grant access
- AI:
  - product AI plugins continue to call Core AI routing and usage controls
  - provider keys remain server-side

## Known Limitations

- This phase is not a formal third-party penetration test.
- Composer is not installed on the current machine, so Laravel feature tests could not be executed locally.
- CSP may need adjustment later if approved third-party frontend assets or analytics are added.
- Protected admin 2FA is scoped to protected roles; future staff policies can add finer-grained role requirements.

## Validation Target

Relevant validation for this phase:

- PHP syntax checks across Core, tests, and plugins.
- Frontend asset build.
- Git whitespace check.
- Security feature tests where Composer dependencies are installed.
