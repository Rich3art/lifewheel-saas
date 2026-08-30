# Phase 8 - Core Member Settings / Privacy Foundation

## Scope

Phase 8 adds the first production foundation for member-facing settings and privacy workflows.

It does not implement the full legal/compliance deletion engine yet. It creates the protected data model, screens, permissions, and request flow that later phases can expand safely.

## Implemented

- Data-driven member settings sections.
- Super Admin visibility controls for member settings sections.
- Member settings hub under `/app/settings`.
- Privacy request creation for data export, correction, consent review, and erasure.
- Pending `data_exports` records for export requests.
- Super Admin privacy request queue.
- Privacy request status updates with audit logging.
- Seeded default settings sections for profile, security, privacy, billing, notifications, community, and AI preferences.
- New permissions: `admin.member_settings.manage` and `admin.privacy.manage`.

## Security Notes

- Member privacy requests are always created for the authenticated user server-side.
- Member settings only load privacy requests that belong to the authenticated user.
- Admin privacy and settings routes require explicit permissions.
- Privacy request updates are audit logged.
- Data export records are pending metadata only; no downloadable file is exposed yet.
- The erasure workflow is intentionally request-based and not an immediate cascade delete.

## Deferred

- Export file generation and expiring protected downloads.
- Full data erasure processing workflow.
- Consent preference storage.
- Policy acceptance/version workflows beyond the CMS legal-page foundation.
- Plugin-provided member settings panels.
- Admin privacy SLA metrics and assignment workflow.

## Validation

Local validation for this phase should include PHP syntax checks, frontend asset build, and feature tests for settings visibility and privacy request ownership when Composer dependencies are available.
