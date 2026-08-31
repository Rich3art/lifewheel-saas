# Phase 17 - AI Life Analysis Plugin

## Scope

Phase 17 adds the first AI product plugin. It uses the Core AI service from Phase 15 and does not implement a separate provider integration.

The plugin analyzes recent LifeWheel history and stores structured analysis output for the authenticated user.

## Implemented

- First-party `ai-life-analysis` plugin manifest.
- Plugin-owned migration for `ai_life_analyses`.
- Member routes under `/app/ai-life-analysis`.
- Generate analysis action using Core `AiGateway`.
- Structured JSON schema for analysis output.
- Selective `LifeContextBuilder` that summarizes recent LifeWheel assessments and score changes.
- Stored analysis history.
- Analysis detail page with structured sections:
  - overall balance
  - strongest areas
  - weakest areas
  - biggest improvements
  - biggest declines
  - patterns
  - possible causes
  - constraints
  - recommended priority areas
  - recommended actions
  - what to avoid
  - reflection questions
  - historical comparison
- Local mock AI output now returns schema-shaped responses.

## Core Boundary

The plugin owns AI Life Analysis routes, views, migration, schema, and context assembly.

Core remains responsible for provider configuration, model routing, entitlement checks, usage metering, and server-side AI calls.

## Security Notes

- All plugin routes require authentication, verified email, 2FA, and `ai.analysis`.
- Users must complete at least one LifeWheel assessment before generating analysis.
- Context building only queries LifeWheel data for the authenticated user.
- Analysis lookup is scoped by analysis ID and authenticated user ID.
- The plugin stores summarized context and AI output history under the owning user.
- User-facing UI never receives provider credentials.

## Deferred

- Journal, goals, habits, projects, lessons, and reviews context.
- Richer dashboard widgets.
- Admin prompt customization.
- Credit charging and vouchers.
- Advanced historical trend analysis.
- Human-readable fallback formatting for malformed provider JSON.
