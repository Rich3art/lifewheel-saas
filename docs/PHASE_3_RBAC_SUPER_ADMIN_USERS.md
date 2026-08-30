# Phase 3 RBAC Super Admin And Users

Phase 3 adds database-backed roles, permissions, Super Admin bootstrapping, and user administration. It stays inside the Core platform boundary and does not add package entitlements, plugins, billing, CMS, or LifeWheel product features.

## Implemented Scope

- Role and permission models.
- Role-permission, user-role, and direct user-permission relationships.
- Permission middleware for server-side route protection.
- Global `Gate::before` permission check for admin abilities.
- System seed data for initial permissions, Super Admin, and Member roles.
- Optional `LIFEOS_BOOTSTRAP_ADMIN_EMAIL` bootstrap assignment.
- Admin dashboard links guarded by permission checks.
- Users admin list with search, status, roles, and registration date.
- User role assignment.
- Direct user permission assignment.
- User suspension and unsuspension.
- Roles admin for creating roles and assigning permissions.
- Permissions admin for creating platform permissions.
- Audit events for role changes, permission changes, suspension, role creation, and permission creation.

## Security And Authorization

- Admin routes require authentication, verified email, completed 2FA challenge where enabled, and explicit admin permissions.
- Normal members cannot access admin routes by direct URL.
- User management, role management, and permission management are separate permissions.
- Private future user content such as LifeWheel scores, journals, AI conversations, and lessons is not displayed on the user list.
- Protected roles are represented as data with `is_protected`.
- Assigning protected roles requires role-management permission, not only user-management permission.
- Admins cannot suspend their own account.
- Suspended users are logged out and blocked by web middleware on the next request.

## Default Permissions

- `admin.dashboard.view`
- `admin.users.manage`
- `admin.roles.manage`
- `admin.permissions.manage`

These permissions are seed data and part of the platform authorization registry. Future phases will expand this into package feature entitlements and plugin-registered permissions.

## Local Tooling Limitation

This workstation still has PHP available but no usable Composer/Laravel dependency install because PHP OpenSSL is missing. Full `php artisan test` is blocked until dependencies are installed. Phase 3 therefore adds PHPUnit tests and validates executable PHP syntax locally.
