# Phase 4 Core Plugin System

Phase 4 implements the first working version of the Core plugin system. This phase deliberately excludes the plugin administration UI, upload ZIP flow, and destructive uninstall UX because those belong to Phase 5.

## Implemented Scope

- Plugin database registry.
- Plugin manifest parser and validator.
- Plugin lifecycle contract.
- Base plugin class.
- Plugin context object.
- Plugin discovery from `plugins/*/plugin.json`.
- Installed/enabled plugin filtering.
- Plugin lifecycle service for install, activate, deactivate, upgrade, and uninstall.
- Dependency checks before activation/deactivation.
- PHP and core-version compatibility checks.
- Enabled-plugin route loading.
- Enabled-plugin migration loading.
- Plugin registration tables for declared permissions, features, menus, and settings sections.
- Plugin repository helpers for installed plugins, enabled menus, and enabled settings sections.
- One tiny first-party example plugin: `plugins/ExampleAudit`.

## Example Plugin

The example plugin proves the package format:

- `plugin.json`
- `src/ExampleAuditPlugin.php`
- `routes/web.php`
- `database/migrations`

It registers one permission, one feature, one member menu item, one admin settings section, one route, and one plugin-owned migration.

## Security And Authorization Review

- Plugin route and migration files are loaded only from resolved paths inside the plugin directory.
- Manifest entry files must live under `src/`.
- Plugin classes must implement `App\Plugins\Contracts\Plugin`.
- Enabled plugin routes are loaded only for database-enabled plugins.
- Deactivation is blocked if another enabled plugin depends on the target plugin.
- Plugin lifecycle events are audit logged.
- Third-party plugin upload is not exposed yet; Phase 5 will add upload validation and destructive-action controls.

## Known Limitations

- There is not yet a Super Admin plugin UI.
- Plugin ZIP upload, update packages, delete files, and uninstall confirmation flows are deferred to Phase 5.
- Plugin migrations are loadable, but full migration execution could not be run locally because Composer dependencies are still unavailable.
- Full PHPUnit execution is blocked locally until PHP OpenSSL and Composer dependencies are available.

## Tests Added

- Manifest required-field validation.
- Manifest entry-path validation.
- Valid manifest parsing.
- Example plugin discovery.
- Enabled plugin filtering.
- Plugin install/activation registration sync.
- Dependency deactivation blocking.
