# Phase 30 — cPanel Installer

## Scope

Phase 30 adds the browser installer foundation for ordinary cPanel shared hosting. It does not deploy to `lifewheel.ranksmedia.com`.

## Installer Route

The installer is available at:

```text
/install
```

The route is public only before installation. It is locked when:

- `APP_INSTALLED=true` is configured.
- `storage/app/installed.lock` exists.
- the configured database already contains user records.

This prevents reopening the installer on an existing production site if the lock file is missing.

## Installer Flow

The installer:

- checks PHP version, required extensions, writable directories, and `.env` writability
- collects site name, application URL, and timezone
- collects MySQL/MariaDB database credentials
- can test the database connection before install
- writes production `.env` values
- generates a Laravel `APP_KEY` if one is not already configured
- runs migrations and seeders
- creates or updates the first Super Admin user
- assigns the protected `super-admin` role
- writes `storage/app/installed.lock`
- redirects to login

## cPanel Compatibility Decisions

The default session driver is now `file` before installation so `/install` can render before database tables exist. The installer writes `SESSION_DRIVER=database` after successful setup.

The default cache store in `.env.example` is now `file` for the same pre-install reason. The installer writes `CACHE_STORE=database` once the database has been configured.

No Node server, Docker, queue daemon, Redis, PostgreSQL, or external worker is introduced.

## Security Notes

- Installer POST routes use Laravel CSRF protection.
- The installer never logs database passwords.
- The lock state is enforced server-side; hiding links is not treated as protection.
- Existing users in the database close the installer even if the lock file is absent.
- The Super Admin password must be at least 12 characters.
- Installation writes secure production defaults where possible, including encrypted database sessions after install.

## Known Limitations

The final release packaging phase still needs to validate a clean ZIP install end to end in a true cPanel-like environment, including first-boot `.env` and `APP_KEY` handling. This phase adds the Laravel installer foundation but does not perform real hosting deployment.

## Tests

Phase 30 adds tests for:

- unlocked installer visibility
- `APP_INSTALLED` lock denial for GET and POST routes
- user-table lock denial after users exist
- requirement checker baseline output
