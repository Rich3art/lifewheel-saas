# Phase 1 Local Setup

## PHP

The project targets PHP 8.2+.

The current local machine reports PHP 8.5.8, but the PHP CLI is missing OpenSSL. Composer cannot install Laravel dependencies until OpenSSL is enabled.

## Composer

Expected command after OpenSSL is available:

```bash
php composer.phar install
```

or, with a global Composer install:

```bash
composer install
```

## Frontend Assets

Node is development/build-time only.

```bash
pnpm install
pnpm build
```

Compiled assets will be included in future cPanel release ZIPs.

## Tests

After Composer dependencies are installed:

```bash
php artisan test
```

Phase 1 currently validates PHP syntax without vendor dependencies.
