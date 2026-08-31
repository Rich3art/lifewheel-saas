# Phase 18 — AI Coach Plugin

Phase 18 adds the first conversational AI product plugin. The plugin implements "Ask My Life" as a private coaching workspace that retrieves selective user-owned context and routes generation through the Core AI gateway.

## Scope

- First-party `ai-coach` plugin manifest, routes, migration, views, and services.
- `ai.coach` feature entitlement.
- Private conversations and message history.
- Structured coach response schema.
- Selective context retrieval across installed product plugins when their tables exist.
- Server-side authorization and ownership checks.

## Architecture

The plugin owns:

- `ai_coach_conversations`
- `ai_coach_messages`

Core continues to own:

- authentication
- 2FA middleware
- feature entitlement checks
- AI provider routing
- AI usage metering

The coach plugin does not call providers directly. It submits an `AiRequest` with feature slug `ai.coach` to `AiGateway`.

## Context Retrieval

`CoachContextBuilder` builds a compact context package from:

- recent LifeWheel assessments and latest scores
- matching journal entries, goals, habits, projects, and lessons when those plugin tables exist
- recent coach messages from the same authenticated user

All private records are scoped by `user_id`. The plugin never retrieves private records by ID alone.

## Security Review

- Routes require `auth`, `verified`, `twofactor`, and `feature:ai.coach`.
- Conversation lookup uses both `id` and authenticated `user_id`.
- Context retrieval checks table/column existence before querying plugin-owned tables.
- Provider keys remain server-side in Core AI settings.
- AI usage is metered server-side by `AiGateway`.

## Limitations

- Retrieval is keyword and recency based; semantic embeddings are deferred.
- The first version does not stream responses because cPanel compatibility remains the production constraint.
- File attachments and rich conversation export are deferred.
