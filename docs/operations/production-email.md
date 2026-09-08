# Production Email

## Current Production SMTP

The production site sends mail through the cPanel-hosted mailbox for:

`info@lifewheel.ranksmedia.com`

Laravel 12 reads the SMTP encryption mode from `MAIL_SCHEME`. The application also supports the older `MAIL_ENCRYPTION` variable as a fallback for compatibility.

For the current cPanel host, SSL SMTP must use the server hostname rather than `localhost`; otherwise certificate validation can fail because the SSL certificate common name does not match `localhost`.

Current working shape:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=s4572.sgp1.stableserver.net
MAIL_PORT=465
MAIL_USERNAME=info@lifewheel.ranksmedia.com
MAIL_FROM_ADDRESS=info@lifewheel.ranksmedia.com
MAIL_FROM_NAME="LifeWheel SaaS"
```

Do not commit `MAIL_PASSWORD`.

## Verification

Production verification performed:

- Confirmed the cPanel mailbox exists and is not suspended.
- Confirmed SMTP ports are open from the cPanel server.
- Confirmed Laravel config reads `MAIL_SCHEME=smtps`, host `s4572.sgp1.stableserver.net`, and port `465`.
- Sent a Laravel `Mail::raw` test message successfully from production.

## Troubleshooting Notes

- `ssl://localhost:465` failed because the mail server certificate did not match `localhost`.
- Blank SMTP scheme caused Laravel to connect without the intended SSL mode.
- Use `php artisan optimize:clear` after changing mail environment variables.
