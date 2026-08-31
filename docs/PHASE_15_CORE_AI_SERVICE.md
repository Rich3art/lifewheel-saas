# Phase 15 - Core AI Service

## Scope

Phase 15 adds the Core AI service framework. It does not add AI product features yet.

AI Life Analysis, AI Coach, AI Reviews, AI Goal Designer, and AI Habit Designer remain later plugins that will call this Core abstraction.

## Implemented

- Core AI configuration file.
- AI provider storage with encrypted API key casting.
- AI model route storage by feature slug.
- AI usage event ledger.
- Provider-neutral AI request and response DTOs.
- Provider client contract.
- Mock AI provider for local development and safe testing.
- OpenAI provider client using server-side HTTP calls.
- Anthropic provider client using server-side HTTP calls.
- AI gateway for:
  - entitlement checks
  - model route resolution
  - route/package monthly limit checks
  - usage event recording
  - mock fallback route creation
- Super Admin AI settings page.
- Provider update flow that never displays stored API keys.
- Model route update flow.
- Seeded mock, OpenAI, and Anthropic provider records.
- Seeded default model routes for existing AI feature slugs.
- `admin.ai.manage` permission.

## Security Notes

- Provider API keys are stored through Laravel encrypted casts.
- API keys are hidden from model array serialization and never rendered in admin views.
- AI calls are server-side only.
- User-facing AI generation requires the user to have the requested AI feature entitlement.
- Usage is recorded server-side and does not trust client-reported counters.
- Disabled real providers are not used.
- Failed AI calls are recorded as failed usage events for audit/cost investigation.

## Deferred

- Product AI features and prompts.
- Structured coaching schemas beyond provider support plumbing.
- Cost estimation by exact model pricing.
- Admin provider connection test button.
- Per-package model restrictions.
- Anthropic structured output compatibility layer.
- Secret rotation workflow.
