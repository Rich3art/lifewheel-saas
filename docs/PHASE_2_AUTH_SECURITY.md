# Phase 2 Auth And Security

Phase 2 adds the core authentication and account-security foundation for the LifeWheel SaaS Laravel platform. It remains deliberately platform-focused: no LifeWheel product plugin, package engine, payment provider, or Super Admin RBAC has been implemented in this phase.

## Implemented Scope

- Email/password registration and login.
- Logout with session invalidation and CSRF-protected form submission.
- Email verification routes and resend throttling.
- Password reset request and reset completion routes.
- Login throttling with per-email/IP rate limits.
- Profile editing for name, username, and timezone.
- Password change requiring the current password.
- Standards-based TOTP two-factor authentication.
- Recovery codes for 2FA account recovery.
- Session regeneration after login, registration, password change, and logout.
- Security headers middleware.
- Audit logging foundation for important account/security events.
- Auth-protected member and admin shells.

## Security Notes

- Passwords use Laravel's hashed cast and configured hashing driver.
- 2FA secrets and recovery codes use Laravel encrypted casts.
- Security-sensitive POST/PATCH/PUT/DELETE routes rely on Laravel CSRF protection.
- Login and 2FA challenge routes are throttled.
- Member and admin dashboard routes are server-side gated by `auth`, `verified`, and `twofactor` middleware.
- Audit logs redact sensitive metadata keys and do not store passwords, secrets, tokens, or recovery codes.
- Admin privilege separation and user suspension are intentionally deferred to Phase 3.

## cPanel Notes

The implementation remains compatible with ordinary cPanel shared hosting. It uses Laravel server-rendered Blade views and standard PHP session/database facilities. No production Node server, Redis, queue daemon, WebSocket server, Docker, or VPS-only service is required.

## Test Coverage Added

- Guest dashboards redirect to login.
- Login screen renders.
- Valid login works.
- Invalid login fails.
- Logout works.
- Registration screen renders.
- New users can register.
- Unverified users are redirected to email verification.
- Profile can be updated.
- Password can be changed.
- Security headers are emitted.
- 2FA setup generates a secret.
- 2FA-enabled login redirects to a challenge.
- Recovery codes work once.

## Local Tooling Limitation

This workstation has PHP available but the OpenSSL extension is missing, and Composer is not globally installed. Because Laravel dependencies cannot currently be installed or autoloaded here, full `php artisan test` execution is blocked until PHP OpenSSL/Composer are available. Syntax validation was used as the executable local check for Phase 2 PHP files.
