# Phase 12 - Habits Plugin

## Scope

Phase 12 adds the first-party Habits plugin as a behavior tracking system outside Core.

Habits are distinct from goals and projects. Habit definitions can change over time while historical logs remain attached to the habit and date they were recorded for.

## Implemented

- First-party `habits` plugin manifest.
- Plugin-owned migrations for:
  - `habits`
  - `habit_logs`
- Member routes under `/app/habits`.
- Habit creation and editing.
- Habit types:
  - positive
  - quit
  - numeric
- Habit metadata:
  - LifeWheel areas
  - weekdays
  - target count
  - numeric target value
  - unit
  - status
  - notes
- Daily habit logging with one log per habit/date.
- Recent log history.
- 28-day adherence calculation.
- `HabitCompleted` domain event.
- Feature declarations:
  - `habits.use`
  - `habits.stats`

## Core Boundary

Core was not given habit tables or controllers. The Habits plugin owns its routes, migration, event, views, and adherence calculation.

Core remains responsible for authentication, email verification, 2FA challenge enforcement, plugin loading, and package entitlement checks.

## Security Notes

- All routes require authentication, verified email, 2FA, and `habits.use`.
- Habit lookup, update, and log writes include authenticated user ownership checks.
- Direct access to another user's habit returns 404.
- Habit logs are unique by habit and date to reduce duplicate/farming behavior.
- Plugin disable removes habit routes without deleting habit data.

## Deferred

- Goal-to-habit links.
- Mood and weight tracker special dashboards.
- Advanced streak calculations.
- Admin habit rule templates.
- AI habit designer and optimizer.
- Rich charts beyond initial adherence labels.
