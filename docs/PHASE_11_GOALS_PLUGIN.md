# Phase 11 - Goals Plugin

## Scope

Phase 11 adds the first-party Goals plugin as a measurable outcome system outside Core.

Goals are distinct from habits and projects. This phase focuses on goals, milestones, progress records, and LifeWheel area links.

## Implemented

- First-party `goals` plugin manifest.
- Plugin-owned migrations for:
  - `goals`
  - `goal_milestones`
  - `goal_progress_records`
- Member routes under `/app/goals`.
- Goal creation and editing.
- Goal metadata:
  - name
  - why
  - LifeWheel areas
  - status
  - success criterion
  - measure
  - baseline
  - current
  - target
  - unit
  - due date
- Milestone creation and completion.
- Progress record creation.
- Evidence-based progress calculation from baseline/current/target.
- `GoalCreated` and `GoalMilestoneCompleted` domain events.
- Feature declarations:
  - `goals.use`
  - `goals.progress`

## Core Boundary

Core was not given goal tables or goal controllers. The Goals plugin owns its routes, migration, events, views, and progress calculation.

Core remains responsible for authentication, email verification, 2FA challenge enforcement, plugin loading, and package entitlement checks.

## Security Notes

- All routes require authentication, verified email, 2FA, and `goals.use`.
- Progress records additionally require `goals.progress`.
- Goal, milestone, and progress queries include authenticated user ownership checks.
- Direct access to another user's goal returns 404.
- Plugin disable removes goal routes without deleting goal data.

## Deferred

- Goal-to-habit links.
- Goal-to-project links.
- Advanced records and charts.
- AI goal designer.
- Goal templates.
- Soft delete/archive workflows beyond status changes.
