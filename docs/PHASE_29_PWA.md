# Phase 29 — PWA

Phase 29 adds the first installable PWA foundation without introducing any production Node server requirement.

## Implemented Scope

- `public/manifest.webmanifest`
- `public/sw.js`
- `public/offline.html`
- `public/icons/icon.svg`
- `public/icons/maskable-icon.svg`
- `public/browserconfig.xml`
- PWA metadata in both application layout files
- Progressive service worker registration in `resources/js/app.js`
- Mobile safe-area/tap polish in `resources/css/app.css`
- PWA feature tests for metadata and static assets

## Architecture Decisions

- PWA assets are static files under `public/` so they work on ordinary Apache/cPanel hosting.
- The service worker is deliberately conservative:
  - caches only app-shell files, icons, and compiled assets
  - uses network-first navigation with an offline fallback
  - does not attempt offline mutation sync
  - ignores non-GET and cross-origin requests
- The app remains server-rendered and fully usable without service worker support.
- Icons are SVG for MVP packaging simplicity. PNG icons can be added during release packaging if platform testing requires them.

## Security Notes

- The service worker caches only same-origin GET requests.
- Authenticated HTML pages are not pre-cached.
- Offline mode does not expose private data.
- No background sync or offline write queue is implemented.
- CSP already allows same-origin service worker registration.

## Known Limitations

- Advanced offline syncing is not implemented.
- Push notifications are not implemented.
- Platform-specific PNG icon sizes are deferred to release polish if needed.
- Installability should be verified in browser tooling during later QA/deployment phases.

## Validation Target

Relevant validation for this phase:

- PHP syntax checks across Core, tests, and plugins.
- Frontend asset build.
- Git whitespace check.
- PWA feature tests where Composer dependencies are installed.
