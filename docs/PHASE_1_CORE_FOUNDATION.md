# Phase 1 Core Application Foundation

Status: In progress baseline  
Date: 2026-08-30

## Scope Completed

Phase 1 establishes a Laravel 12 cPanel-compatible foundation only.

Included:

- Laravel-style project manifest and bootstrap files.
- Public front controller and Apache `.htaccess`.
- Basic web routes.
- Home page.
- Health endpoint.
- Placeholder member dashboard shell.
- Placeholder admin dashboard shell.
- Blade layout.
- Vite/Tailwind/Alpine-ready frontend entrypoints.
- MySQL/MariaDB default environment template.
- Database-backed session/cache/queue migrations for cPanel compatibility.
- PHPUnit configuration and route smoke tests.

Not included:

- LifeWheel feature.
- Authentication flows.
- RBAC.
- Plugin manager.
- Packages/entitlements.
- CMS/blog.
- AI provider implementation.
- Billing provider implementation.

Those belong to later approved phases.

## cPanel Compatibility Decisions

- PHP target remains `^8.2`.
- Laravel target remains `^12.0`.
- MySQL/MariaDB is the production default.
- Sessions, cache, and queues use database-backed drivers by default.
- No Redis, queue daemon, WebSockets, Docker, or permanent Node process is required.
- Frontend assets are built with Vite during release packaging and served statically.

## Local Environment Note

The local PHP CLI reports PHP 8.5.8 but lacks the OpenSSL extension. Composer installation could not complete because Composer requires OpenSSL for secure HTTPS transfers.

Resolution needed before full Laravel test/build execution:

- Enable PHP OpenSSL locally, or
- Install Composer with a PHP build that includes OpenSSL.

Until then, Phase 1 validation is limited to static file review and PHP syntax checks available without vendor dependencies.

## Security Review

Phase 1 security-relevant choices:

- Public document root is `public/`.
- Apache rewrite rules deny directory indexes.
- Baseline security headers are set in `public/.htaccess`.
- No authenticated/private product routes are implemented yet.
- No secrets are committed.
- `.env.example` contains placeholders only.

Deferred to Phase 2:

- login throttling
- email verification
- password reset
- session hardening beyond config defaults
- 2FA/TOTP
- auth policies

Deferred to Phase 3:

- RBAC
- Super Admin enforcement
- user suspension

Deferred to Phase 4/5:

- plugin upload and lifecycle security.
