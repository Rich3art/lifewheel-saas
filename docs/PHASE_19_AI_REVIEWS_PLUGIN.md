# Phase 19 — AI Reviews Plugin

Phase 19 adds a first-party AI Reviews plugin for daily, weekly, monthly, quarterly, yearly, and custom-range executive life reviews.

## Scope

- `ai-reviews` plugin manifest, lifecycle entrypoint, routes, migration, views, schema, and context builder.
- `ai.reviews` feature entitlement.
- Period-aware review generation and history.
- Structured review outputs through the Core AI gateway.
- Selective private context from installed first-party product plugins.

## Architecture

The plugin owns the `ai_reviews` table. Each generated review stores:

- user owner
- period type and date range
- normalized context snapshot
- raw provider content
- structured JSON output
- provider/model/tokens

Core still owns auth, 2FA, entitlement checks, AI provider routing, encrypted provider credentials, and usage metering.

## Review Periods

Supported periods:

- daily
- weekly
- monthly
- quarterly
- yearly
- custom date range

Custom reviews validate both dates and reject ranges where the end precedes the start.

## Security Review

- Routes require `auth`, `verified`, `twofactor`, and `feature:ai.reviews`.
- Review reads are scoped by both review ID and authenticated user ID.
- Context retrieval uses only records where `user_id` matches the authenticated user.
- The plugin checks optional plugin tables before querying them.
- AI provider credentials are never exposed to browser code.

## Limitations

- Context retrieval is structured and period-based, not semantic search.
- Reviews are generated synchronously for cPanel compatibility.
- The plugin does not yet create scheduled reviews automatically; scheduler integration is deferred.
