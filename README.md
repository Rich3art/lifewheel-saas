# LifeWheel SaaS

Commercial, modular, AI-capable LifeWheel SaaS platform designed for ordinary cPanel shared hosting.

## Current Status

Phase 6 is the active baseline. The project has been redirected from an earlier Next.js prototype toward the approved target architecture:

- Laravel 12
- PHP 8.2+
- MySQL/MariaDB
- Blade and Alpine.js
- cPanel-compatible release ZIPs
- Core plus independently managed plugins

Do not build feature plugins until the core/plugin architecture phases are complete.

## Phase Discipline

This project is phase-gated. At the end of every phase:

- complete only that phase
- run relevant tests
- perform security/authorization review
- update documentation
- commit
- push
- stop and request approval for the next phase

## Phase 0 Documentation

Start here:

- [Phase 0 Audit And Final Architecture](docs/PHASE_0_AUDIT_AND_ARCHITECTURE.md)
- [Phase 1 Core Foundation](docs/PHASE_1_CORE_FOUNDATION.md)
- [Phase 2 Auth And Security](docs/PHASE_2_AUTH_SECURITY.md)
- [Phase 3 RBAC Super Admin And Users](docs/PHASE_3_RBAC_SUPER_ADMIN_USERS.md)
- [Phase 4 Core Plugin System](docs/PHASE_4_CORE_PLUGIN_SYSTEM.md)
- [Phase 5 Core Plugin Admin](docs/PHASE_5_CORE_PLUGIN_ADMIN.md)
- [Phase 6 Core SaaS Features And Packages](docs/PHASE_6_CORE_SAAS_FEATURES_PACKAGES.md)
- [Plugin Architecture](docs/plugins/plugin-architecture.md)
- [Security Model](docs/security/security-model.md)
- [cPanel Deployment Architecture](docs/deployment/cpanel-architecture.md)
- [Privacy Architecture](docs/privacy/privacy-architecture.md)

## Upstream Attribution

The upstream reference project is:

https://github.com/jmoraispk/2nd-brain-plugin

Useful product concepts were studied from that repository. The SaaS application will be a clean standalone Laravel platform, not an Obsidian plugin fork.

## Current Auth Foundation

Phase 2 adds core registration, login, logout, email verification, password reset, profile updates, password changes, TOTP 2FA, recovery codes, security headers, and account/security audit logging. Dashboard shells are protected by authentication, verified email, and 2FA challenge middleware where enabled.

## Current RBAC Foundation

Phase 3 adds database-backed roles, permissions, protected Super Admin bootstrapping, user administration, user suspension, direct user permission overrides, and permission-gated admin routes.

## Current Plugin Foundation

Phase 4 adds the Core plugin registry, manifest parser, lifecycle contract, lifecycle service, enabled route/migration loading, plugin registration tables, and a tiny first-party example plugin. The Super Admin plugin upload/manage UI is intentionally deferred to Phase 5.

## Current Plugin Admin

Phase 5 adds the permission-gated Super Admin plugin manager for installing, enabling, disabling, updating, uninstalling, and deleting trusted plugin packages with ZIP validation and typed destructive confirmations.

## Current SaaS Foundation

Phase 6 adds the central feature registry, editable packages, package limits, user package assignment, user-level feature overrides, and server-side entitlement checks.
