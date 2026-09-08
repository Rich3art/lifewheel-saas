# Phase 32 — Clean Install & Upgrade QA

## Scope

Phase 32 adds the formal clean-install and upgrade QA gate before production deployment. It does not deploy the application.

## Added QA Assets

- `docs/deployment/clean-install-upgrade-qa.md`
- `scripts/qa/phase32-release-readiness.php`

The QA script performs repository-level release readiness checks that do not require Laravel bootstrapping. The checklist covers the manual cPanel-like clean install and upgrade scenarios that must be run against generated release ZIP artifacts.

## Automated Readiness Checks

Run:

```bash
php scripts/qa/phase32-release-readiness.php
```

The script verifies:

- release packager exists
- GitHub Actions release workflow exists
- installer controller/view exists
- install routes are registered
- pre-install `.env.example` settings are cPanel-safe
- release packager excludes local secrets and runtime files
- plugin manifests are readable
- plugin manifest required keys exist
- plugin entry files exist

## Manual QA Coverage

The clean install checklist covers:

- artifact contents
- `/install` flow
- installer lock behavior
- Super Admin sign-in
- Core authorization checks
- plugin upload/install/enable/disable/update/uninstall/delete behavior
- package entitlement enforcement
- IDOR/BOLA checks
- privacy export/erasure flow
- payment webhook denial/verified-event paths
- PWA installability/offline fallback
- upgrade from a previous release artifact

## Known Limitations

This local Windows environment still does not have Composer on PATH, `vendor/autoload.php`, or PHP `ext-zip`, so full Laravel test execution and local ZIP creation remain blocked here. The GitHub Actions release workflow installs the needed dependencies/extensions for artifact generation.
