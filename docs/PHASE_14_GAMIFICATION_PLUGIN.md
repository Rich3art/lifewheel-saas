# Phase 14 - Gamification Plugin

## Scope

Phase 14 adds the first-party Gamification plugin as an XP ledger system outside Core.

The goal is to support event-driven rewards without storing mutable XP totals on the user record.

## Implemented

- First-party `gamification` plugin manifest.
- Plugin-owned migrations for:
  - `gamification_rules`
  - `xp_events`
- XP ledger service with idempotent award behavior.
- XP level calculation helper.
- Member XP ledger page under `/app/xp`.
- Admin gamification rule page under `/admin/gamification`.
- Default rule for `lifewheel.assessment_completed`.
- `LifeWheelAssessmentCompleted` listener that awards assessment XP.
- Runtime plugin boot hook in Core so enabled plugins can register event listeners.
- Feature declarations:
  - `gamification.use`
  - `gamification.admin`

## Core Boundary

Core received one plugin-infrastructure enhancement: enabled plugins now have their `boot()` method called during application boot.

Gamification remains plugin-owned. Core does not store XP totals, rules, or gamification product logic.

## Security Notes

- Member XP routes require authentication, verified email, 2FA, and `gamification.use`.
- Admin rule routes require existing Super Admin SaaS management permission.
- XP is recorded in a ledger with a unique idempotency constraint per user/event/source.
- Rule changes are audit logged.
- Plugin disable removes gamification routes and event listener bootstrapping without deleting XP data.
- The initial LifeWheel award uses assessment ID as the idempotent source.

## Deferred

- Badges, achievements, levels UI polish, and leaderboards.
- Manual XP adjustment screen.
- Caps/cooldowns beyond schema fields.
- Habit/goal/project XP listeners.
- Dedicated `admin.gamification.manage` permission.
- Anti-farming rules beyond idempotent source events.
