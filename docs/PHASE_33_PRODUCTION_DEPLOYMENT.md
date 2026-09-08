# Phase 33 Production Deployment

## Summary

Phase 33 deployed the Laravel LifeWheel SaaS platform to the approved production domain:

https://lifewheel.ranksmedia.com

The deployment used ordinary cPanel shared hosting without Docker, a permanent Node server, Redis, PostgreSQL, or root access.

## Completed Work

- Confirmed the production subdomain exists and points to the Laravel `public` directory.
- Preserved the previous contents of the subdomain directory by moving them to a timestamped backup directory before installing the new application.
- Cloned the approved GitHub repository into the production application directory.
- Installed Composer production dependencies with optimized autoloading.
- Created the required Laravel writable directories and storage symlink.
- Created and configured a MySQL database and application database user.
- Created the `info@lifewheel.ranksmedia.com` mailbox.
- Ran the browser installer and locked `/install` after installation.
- Ran production migrations and database seeders.
- Verified the bootstrap Super Admin account email status after deployment.
- Verified public HTTPS, production environment mode, installer lock, login, health check, security headers, and admin 2FA enforcement.

## Production Environment

- Domain: `lifewheel.ranksmedia.com`
- Application path: `/home/thesite1/lifewheel.ranksmedia.com`
- Public document root: `/home/thesite1/lifewheel.ranksmedia.com/public`
- Database: `thesite1_lifewheel`
- Database user: `thesite1_lw`
- PHP: 8.3.33
- Composer: 2.8.12
- Laravel: 12.69.2
- Environment: production
- Debug mode: off

No credentials or secrets are stored in this repository.

## Architecture Decisions

- Production uses the cPanel-hosted Laravel application directly from the approved GitHub repository.
- The document root targets Laravel's `public` directory so framework internals, `.env`, storage, and vendor files are not web-root exposed.
- The installer is locked after installation with `APP_INSTALLED=true` and the database/user checks.
- Super Admin access remains protected by forced 2FA setup before the admin dashboard can be reached.
- The production app currently uses the mock AI route unless real provider credentials are configured through the secured AI settings flow.

## Verification

Commands and checks executed on production:

- `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`
- `php artisan storage:link`
- `php artisan optimize:clear`
- `php artisan migrate --force`
- `php artisan db:seed --force`
- `php artisan about --only=environment`
- `curl -I https://lifewheel.ranksmedia.com/install`
- `curl -I https://lifewheel.ranksmedia.com/login`
- `curl https://lifewheel.ranksmedia.com/health`
- `curl -I https://lifewheel.ranksmedia.com/admin/dashboard`
- Browser login smoke test with the bootstrap Super Admin account.

Results:

- `/install` returns 404 after installation.
- `/login` returns 200 over HTTPS.
- `/health` returns production `ok`.
- Unauthenticated `/admin/dashboard` redirects to `/login`.
- Authenticated Super Admin access redirects to required 2FA setup before admin pages.
- Security headers are present, including frame protection, content type protection, referrer policy, permissions policy, CSP, and HSTS.

## Security Review

- Confirmed `APP_ENV=production`.
- Confirmed `APP_DEBUG=false`.
- Confirmed HTTPS is active.
- Confirmed secure cookies are configured for production.
- Confirmed the document root is `public`, not the repository root.
- Confirmed installer is locked after setup.
- Confirmed admin routes are not accessible anonymously.
- Confirmed Super Admin is forced through 2FA setup before admin access.
- Confirmed secrets were not committed to Git.

## Known Limitations

- Full SMTP send/receive testing is deferred to post-deployment operations.
- Payment provider production credentials and webhooks are not configured yet.
- Real AI provider credentials are not configured yet; production remains safe with mock fallback until configured.
- A spare MySQL database from deployment experimentation may remain on the hosting account and should be reviewed before removal.
- Backups, restore procedure, performance tuning, and final operational review belong to Phase 34.

## Next Phase

Phase 34 will perform post-deployment security, performance, privacy, backup, restore, and operational readiness review.
