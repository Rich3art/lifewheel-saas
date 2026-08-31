# Phase 13 - Projects Plugin

## Scope

Phase 13 adds the first-party Projects plugin as an execution system outside Core.

Projects are distinct from goals and habits. This phase focuses on project records, project tasks, task completion, and LifeWheel area links.

## Implemented

- First-party `projects` plugin manifest.
- Plugin-owned migrations for:
  - `projects`
  - `project_tasks`
- Member routes under `/app/projects`.
- Project creation and editing.
- Project metadata:
  - name
  - description
  - LifeWheel areas
  - status
  - priority
  - start date
  - due date
- Project task creation and completion.
- Task-based completion percentage.
- `ProjectCreated` and `ProjectTaskCompleted` domain events.
- Feature declarations:
  - `projects.use`
  - `projects.tasks`

## Core Boundary

Core was not given project tables or controllers. The Projects plugin owns its routes, migration, events, views, and task completion calculation.

Core remains responsible for authentication, email verification, 2FA challenge enforcement, plugin loading, and package entitlement checks.

## Security Notes

- All routes require authentication, verified email, 2FA, and `projects.use`.
- Task writes additionally require `projects.tasks`.
- Project and task queries include authenticated user ownership checks.
- Direct access to another user's project returns 404.
- Plugin disable removes project routes without deleting project data.

## Deferred

- Goal-to-project links.
- Habit-to-project links.
- Project notes/files.
- Kanban/list board views.
- Project templates.
- AI project planning.
