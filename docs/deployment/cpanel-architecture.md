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

Future `/install` flow:

- verify PHP version/extensions
- verify writable directories
- collect database credentials
- collect site settings
- create initial Super Admin
- run migrations
- seed core defaults
- lock installer after completion

Never commit production credentials.
