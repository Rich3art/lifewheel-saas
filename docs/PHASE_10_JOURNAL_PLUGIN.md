# Phase 10 - Journal Plugin

## Scope

Phase 10 adds the first-party Journal plugin as a private member feature outside Core.

Journal remains plugin-owned because Core should provide platform infrastructure, not private reflection domain logic.

## Implemented

- First-party `journal` plugin manifest.
- Plugin-owned migration for `journal_entries`.
- Member journal routes under `/app/journal`.
- Create, read, update, and delete journal entries.
- Entry metadata:
  - title
  - body
  - LifeWheel area links
  - mood score
  - energy score
  - entry date
- Private timeline view.
- Single-entry view and edit form.
- Search gated separately by `journal.search`.
- `JournalEntryCreated` domain event.
- Feature declarations:
  - `journal.use`
  - `journal.search`

## Core Boundary

Core was not given journal tables or controllers. The plugin owns its routes, migration, event, and views.

Core remains responsible for authentication, email verification, 2FA challenge enforcement, plugin loading, and package entitlement checks.

## Security Notes

- All routes require authentication, verified email, 2FA, and `journal.use`.
- Search requests with a query require `journal.search`; direct URL manipulation is denied.
- Entry lookup, update, and delete queries include both entry ID and authenticated user ID.
- Journal content is not exposed to Super Admin list screens.
- Plugin disable removes the journal routes without deleting user data.

## Deferred

- Rich text editor.
- Screenshot/media attachments.
- Export integration with the privacy center.
- AI review/analysis consumption of journal entries.
- Journal settings registered into the member settings registry.
- Soft delete/version history for edited entries.
