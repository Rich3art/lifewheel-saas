# Phase 16 - Lessons Plugin

## Scope

Phase 16 adds the first-party Lessons plugin as a durable lessons ledger outside Core.

Lessons are separately entitled from unrestricted AI features. This phase does not add AI generation; it prepares a safe ledger that later AI plugins may write to when the user has the Lessons entitlement.

## Implemented

- First-party `lessons` plugin manifest.
- Plugin-owned migration for `lessons`.
- Member routes under `/app/lessons`.
- Lesson creation, viewing, editing, and deletion.
- Lesson metadata:
  - title
  - body
  - LifeWheel areas
  - source type
  - source ID
  - idempotency key
  - learned date
- Search gated separately by `lessons.search`.
- `LessonCreated` domain event.
- Feature declarations:
  - `lessons.use`
  - `lessons.search`

## Core Boundary

Core was not given lesson tables or controllers. The Lessons plugin owns its routes, migration, event, views, and deduplication-ready schema.

Core remains responsible for authentication, email verification, 2FA challenge enforcement, plugin loading, and package entitlement checks.

## Security Notes

- All routes require authentication, verified email, 2FA, and `lessons.use`.
- Search requests with a query require `lessons.search`, including direct URL manipulation.
- Lesson lookup, update, and delete queries include authenticated user ownership checks.
- The unique `(user_id, idempotency_key)` guard supports future AI-generated lesson deduplication.
- Plugin disable removes lesson routes without deleting lesson data.

## Deferred

- AI-generated lesson extraction.
- Search indexing beyond SQL `LIKE`.
- Goal/habit/project relation tables.
- Privacy export integration.
- Soft deletes and lesson revisions.
