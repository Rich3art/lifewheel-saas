# Phase 27 — Complete Privacy Center

Phase 27 completes the Core privacy workflow foundation for cPanel-compatible operation.

## Implemented Scope

- Member Privacy Center enhancements:
  - create export, correction, consent review, and erasure requests
  - confirm the request belongs to the signed-in user
  - download ready JSON exports
  - manage consent preferences
  - accept current legal policy versions
  - view recent request/export status
- Super Admin privacy queue enhancements:
  - queue metrics for pending, identity check, processing, and overdue requests
  - identity-confirmed marker
  - request due dates
  - export status visibility
  - admin processing of export requests
  - explicit confirmation before erasure completion
- Data model additions:
  - `privacy_consents`
  - `policy_acceptances`
  - `privacy_settings`
  - request due dates and resolution summaries
  - export completion timestamps
- Services:
  - `PrivacyExportService`
  - `PrivacyErasureService`

## Architecture Decisions

- Privacy Center remains Core because it applies to the whole SaaS account and all installed plugins.
- Export generation is synchronous for the first cPanel-compatible version. It writes JSON into private storage and serves downloads only through an authenticated controller.
- Export collection uses table allowlists and `Schema` checks so disabled or uninstalled plugin tables are skipped safely.
- Erasure is not a destructive cascade. The current implementation anonymizes account identity, clears sessions and consent rows, suspends the account, and records retained-data notes.
- Policy acceptance records point to immutable `page_versions`, not mutable `pages`.

## Security Notes

- Member privacy routes require authentication, verified email, and 2FA middleware.
- Export downloads require authenticated ownership, ready status, unexpired expiry, and an existing private file.
- Private exports are not placed in `public/`.
- Admin privacy routes require `admin.privacy.manage`.
- Erasure completion requires explicit `confirm_erasure` confirmation.
- Export payloads intentionally exclude password hashes, remember tokens, 2FA secrets, provider secrets, and raw audit metadata not scoped by `user_id`.

## Known Limitations

- Exports are JSON only. CSV and Markdown archive formats remain deferred.
- Export generation is synchronous and suitable for MVP-sized exports; very large accounts may later need queued chunking.
- Erasure currently anonymizes Core identity and deletes sessions/consents; plugin-specific destructive deletion rules remain deferred to plugin privacy hooks.
- Privacy settings are seeded defaults; a Super Admin privacy settings UI is still deferred.
- The software supports privacy workflows but does not claim legal compliance without legal review.

## Validation Target

Relevant validation for this phase:

- PHP syntax checks across Core, tests, and plugins.
- Frontend asset build.
- Git whitespace check.
- Feature tests for Privacy Center request, export, ownership, consent, policy acceptance, and admin erasure behavior when Composer dependencies are installed.
