# Phase 6 Core SaaS Features And Packages

Phase 6 adds the provider-neutral SaaS entitlement foundation. It remains Core infrastructure: no real payment provider, checkout, subscription engine, or LifeWheel product plugin is included yet.

## Implemented Scope

- Central feature registry.
- Editable packages.
- Package-feature assignments.
- Package limits as key/value records.
- User package assignment.
- User-level feature grant/deny overrides.
- Central `EntitlementService`.
- `feature:` middleware for future product/plugin route protection.
- Super Admin feature management UI.
- Super Admin package management UI.
- Package assignment and feature overrides on the user admin screen.
- Default editable packages: Free, Lessons, Premium AI.
- Default feature taxonomy for LifeWheel, goals, habits, projects, lessons, AI, forum, and gamification.

## Architecture Decisions

- Role permissions remain separate from package feature entitlements.
- Product code should use `EntitlementService::userHasFeature($user, $slug)` or `feature:{slug}` middleware.
- No feature should check hard-coded package names such as `premium`.
- Package limits are flexible key/value records so plugins can introduce new usage concepts later.
- Defaults are seed data only and can be changed by Super Admin.

## Security And Authorization Review

- SaaS admin routes require `admin.saas.manage`.
- Normal members cannot access package/feature admin routes by direct URL.
- User package assignment and feature overrides are admin-only and audit logged.
- Entitlements are computed server-side from database state; the browser is not trusted to report access.
- Feature overrides are explicit grant/deny records and take precedence over package entitlements.

## Known Limitations

- No payment provider or normalized subscription engine yet; billing core starts in Phase 21.
- Package landing page editing is deferred to CMS/package landing phases.
- Full PHPUnit execution is still blocked locally because Composer dependencies are unavailable and PHP OpenSSL is missing.

## Tests Added

- Package feature grants entitlement.
- User override can deny package-granted entitlement.
- User override can grant a feature without a package.
- Normal member cannot access SaaS package admin.
- Admin with SaaS permission can access packages.
- Admin can create a package with features and limits.
- Admin can assign a package to a user.
