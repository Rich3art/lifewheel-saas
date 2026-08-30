# Changelog

## Unreleased

### Phase 4

- Added Core plugin registry, manifest validation, lifecycle contract, lifecycle service, dependency checks, compatibility checks, and enabled plugin route/migration loading.
- Added plugin registration tables for permissions, features, menus, and settings sections.
- Added a tiny first-party Example Audit plugin and plugin-system tests.

### Phase 3

- Added database-backed RBAC with roles, permissions, pivots, protected roles, user suspension, seed data, and bootstrap Super Admin assignment.
- Added permission middleware, admin route gates, Super Admin dashboard links, user administration, role management, and permission management screens.
- Added admin authorization tests for guest denial, member denial, permission-granted access, protected role assignment, user suspension guardrails, and management actions.

### Phase 2

- Added registration, login, logout, email verification, password reset, profile editing, password updates, TOTP 2FA, recovery codes, security headers, and audit-log foundation.
- Protected member and admin shells behind authentication, email verification, and 2FA middleware where enabled.
- Added auth/security feature tests and documented the local Composer/OpenSSL blocker.

### Phase 1

- Added Laravel 12 project foundation for PHP 8.2+ and cPanel shared hosting.
- Added public front controller, Apache rewrite/security headers, routes, controllers, Blade shells, Vite/Tailwind assets, environment template, infrastructure migrations, and PHPUnit smoke tests.
- Documented local Composer/OpenSSL blocker.

### Phase 0

- Audited upstream `jmoraispk/2nd-brain-plugin`.
- Decided on clean standalone repository architecture instead of fork.
- Selected Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade, Alpine.js, and cPanel-compatible release packaging.
- Defined core/plugin boundaries, plugin lifecycle, security model, privacy architecture, entitlement model, AI abstraction, billing abstraction, and phase roadmap.
