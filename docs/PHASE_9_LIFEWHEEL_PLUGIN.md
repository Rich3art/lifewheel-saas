# Phase 9 - LifeWheel Plugin

## Scope

Phase 9 adds the first real first-party product plugin. LifeWheel is implemented outside Core so it can be installed, enabled, disabled, updated, and eventually packaged independently.

## Implemented

- First-party `lifewheel` plugin manifest.
- Plugin-owned migrations for:
  - `lifewheel_assessments`
  - `lifewheel_scores`
- Default LifeWheel areas:
  - Health: Body, Mind, Soul
  - Relationships: Romance, Family, Friends
  - Work: Mission, Money, Growth
- Member assessment form with 1-10 scoring.
- Append-only assessment history.
- Overall score calculation.
- SVG wheel chart.
- Weakest-to-strongest ranking.
- Previous assessment comparison indicators.
- Individual history detail page.
- `LifeWheelAssessmentCompleted` domain event.
- Plugin feature declarations for:
  - `lifewheel.use`
  - `lifewheel.history`
  - `lifewheel.analytics`

## Core Boundary

Core was not given LifeWheel domain tables or controllers. The plugin owns its routes, migrations, event, and views.

Core remains responsible for:

- authentication
- verified email
- two-factor challenge enforcement
- package feature entitlement checks
- plugin discovery and lifecycle

## Security Notes

- LifeWheel routes require authentication, verified email, 2FA challenge completion, and `lifewheel.use` entitlement.
- History detail routes additionally require `lifewheel.history`.
- Assessment records are always written for the authenticated user server-side.
- History records are queried by both assessment ID and authenticated user ID to avoid IDOR/BOLA exposure.
- Scores are server-validated as integers from 1 to 10 for every required area.
- Disabling the plugin removes its routes from the enabled plugin route loader without deleting user data.

## Deferred

- Admin-configurable category questions.
- AI feedback per category.
- 11-category commercial LifeOS question bank.
- Credits/vouchers/paid-only access.
- Rich analytics charts beyond the initial wheel and ranking.
- Dashboard widget rendering through a dynamic plugin menu/widget registry.

Those items are intentionally deferred to later AI, billing, and LifeWheel enhancement phases.
