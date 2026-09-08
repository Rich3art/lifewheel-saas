# Phase 31 — Release / Update System

## Scope

Phase 31 adds the first release packaging foundation for cPanel-compatible delivery. It does not deploy to production and does not alter plugin runtime behavior.

## Release Packager

The packager is:

```text
scripts/release/package.php
```

It can be run without Laravel bootstrapping:

```bash
php scripts/release/package.php --version=1.0.0
```

For validation without writing ZIPs:

```bash
php scripts/release/package.php --version=1.0.0 --dry-run
```

## Artifacts

The packager writes artifacts to:

```text
build/releases/{version}/
```

It creates:

- `lifewheel-core-{version}.zip`
- one `{plugin-id}-plugin-{plugin-version}.zip` per discovered first-party plugin
- `release-manifest.json`

## Core Package Rules

The Core ZIP excludes local and development-only paths:

- `.git`
- `.github`
- `.env`
- `build`
- `node_modules`
- local/session/cache/log/testing storage paths
- tests

Compiled frontend assets are included, so production cPanel hosting does not require Node.

Composer `vendor/` is included when present in the build environment. This is required for final release ZIPs so the installer does not require end users to run Composer manually.

## Plugin Package Rules

Each plugin ZIP is built from its own plugin directory and requires a valid `plugin.json` with:

- `id`
- `name`
- `version`
- `php`
- `entry`
- `class`

Plugin IDs must be safe artifact names. The packager sorts plugin artifacts for deterministic output.

## GitHub Actions

The workflow is:

```text
.github/workflows/release.yml
```

It is manually triggered and:

- checks out the repository
- installs PHP 8.2 with required extensions
- installs production Composer dependencies
- installs Node dependencies
- builds frontend assets
- dry-runs the packager
- creates release ZIPs
- uploads ZIP artifacts

## Update Model

Core updates are distributed as full Core release ZIPs.

First-party plugin updates are distributed as independent plugin ZIPs. The Plugin Manager remains responsible for validating uploaded plugin ZIPs, compatibility, install/update lifecycle, and data-preserving disable/delete behavior.

## Security Notes

- `.env` and local secrets are excluded from release artifacts.
- Plugin manifests are validated before packaging.
- ZIP entries use normalized relative paths.
- The packager rejects unsafe relative paths containing `..`.
- Generated artifacts are written under `build/releases`, which is excluded from Core packaging.

## Known Limitations

The update UI and rollback automation remain intentionally light in this phase. Phase 32 must validate clean install and upgrade flows using the release artifacts before production deployment.
