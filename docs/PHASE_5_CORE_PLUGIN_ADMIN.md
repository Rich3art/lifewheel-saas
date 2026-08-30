# Phase 5 Core Plugin Admin

Phase 5 adds the first Super Admin plugin management interface on top of the Phase 4 plugin lifecycle system. It stays inside Core and does not build any product feature plugin beyond the existing tiny example plugin.

## Implemented Scope

- Super Admin plugin manager at `/admin/plugins`.
- Permission-gated plugin admin routes using `admin.plugins.manage`.
- Installed/discovered plugin listing.
- Plugin upload and install from ZIP.
- Install discovered plugin.
- Enable plugin.
- Disable plugin.
- Update installed plugin from ZIP.
- Uninstall plugin with typed confirmation.
- Optional uninstall with plugin data removal flag.
- Delete plugin files with typed confirmation after uninstall.
- Plugin ZIP validation service.
- Admin dashboard link for plugin management.

## Upload Security

The upload service applies these controls before extraction:

- ZIP extension validation.
- Configurable size limit through `PLUGIN_UPLOAD_MAX_MB`.
- Root-level `plugin.json` requirement.
- Manifest validation through `App\Plugins\PluginManifest`.
- Zip-slip/path traversal rejection.
- Absolute Windows path rejection.
- Denied segments: `.env`, `.git`, `vendor`, `node_modules`.
- File extension allowlist.
- Extraction only into the configured plugin directory.
- Existing plugin id collision prevention on fresh upload.
- Update id matching, so a ZIP for one plugin cannot overwrite another.

## Data Safety

- Disable keeps plugin files and user/plugin data.
- Uninstall removes plugin registration and can call the plugin uninstall hook.
- Destructive data removal requires explicit `remove_data` selection and typed `UNINSTALL` confirmation.
- Delete files is separate from uninstall and requires typed `DELETE FILES` confirmation.
- Installed plugins cannot have their files deleted until they are uninstalled.

## Authorization Review

- `/admin/plugins` and all plugin mutation routes require `auth`, verified email, 2FA where enabled, `admin.dashboard.view`, and `admin.plugins.manage`.
- Normal members cannot access plugin administration by direct URL.
- All plugin mutations use non-GET form methods with CSRF protection.
- Plugin lifecycle and package actions are audit logged.

## Known Limitations

- Full PHPUnit execution is still blocked locally because Composer dependencies are unavailable and PHP OpenSSL is missing.
- Plugin upload uses PHP `ZipArchive`, so production cPanel must have the zip extension enabled.
- Rollback is a basic filesystem backup during update; formal release rollback documentation is deferred to the release/update phase.
- Plugin UI is functional but intentionally simple; richer update-source workflows are deferred.

## Tests Added

- Plugin admin route denies normal members.
- Plugin admin route allows users with plugin-management permission.
- ZIP without root manifest is rejected.
- ZIP path traversal is rejected.
- ZIP with disallowed executable extension is rejected.
