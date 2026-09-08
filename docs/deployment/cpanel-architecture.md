# cPanel Deployment Architecture

## Production Target

Domain: `lifewheel.ranksmedia.com` under `ranksmedia.com`.

Do not deploy until the approved deployment phase.

## Runtime Requirements

- Apache
- PHP 8.2+
- MySQL or MariaDB
- PDO
- cURL
- JSON
- OpenSSL
- Cron Jobs
- `.htaccess`
- standard SMTP

## Prohibited Production Requirements

Production must not require:

- Docker
- VPS/root
- Kubernetes
- Redis
- RabbitMQ
- Kafka
- Celery
- Supervisor
- permanent Node server
- Python worker
- WebSocket daemon
- PostgreSQL
- MongoDB

## Laravel Version

Recommend Laravel 12 for PHP 8.2 compatibility.

Laravel 13 is current but requires PHP 8.3, which is a poor default for ordinary cPanel shared hosting where PHP 8.2+ is the stated baseline.

## Release ZIP Requirements

Release ZIPs should include:

- Laravel application code
- Composer `vendor/`
- compiled frontend assets
- installer
- `.htaccess`
- documentation
- first-party plugin ZIPs where relevant

The end user should not need to run Composer or npm manually for normal installation.

## Cron

Use cPanel cron to call Laravel scheduler:

```bash
php /home/account/path/to/artisan schedule:run
```

No queue worker daemon is assumed. Use synchronous jobs or database-backed jobs processed by cron.

## Installer

The browser installer foundation is available at `/install` before installation.

It verifies PHP version/extensions, writable directories, `.env` writability, and MySQL/MariaDB connectivity. It collects database credentials, site settings, timezone, and the first Super Admin account.

On successful install it writes production `.env` values, runs migrations and seeders, creates or updates the Super Admin user, assigns the protected `super-admin` role, writes `storage/app/installed.lock`, and redirects to login.

The installer is locked when `APP_INSTALLED=true`, the lock file exists, or the configured database already has users. Existing database users intentionally close the installer even if the lock file is missing.

Pre-install defaults use file sessions and file cache so the installer can render before database tables exist. The installer switches production installs to database-backed sessions and cache after database setup.

Never commit production credentials.
