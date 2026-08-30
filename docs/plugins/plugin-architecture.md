# Plugin Architecture

## Objective

Keep LifeWheel SaaS core stable while allowing first-party and trusted third-party features to be installed, enabled, disabled, updated, and removed independently.

## Core Responsibilities

Core owns:

- plugin registry
- plugin validation
- plugin lifecycle execution
- plugin routes/menu registration
- plugin migrations
- plugin permissions/features/settings registration
- plugin upload security
- plugin audit logging

Core does not own feature domain logic such as LifeWheel assessments, habits, goals, lessons, forum, gamification, or AI product experiences.

## Plugin Package

```text
plugins/
  PluginName/
    plugin.json
    src/
    routes/
    database/migrations/
    resources/views/
    resources/assets/
    tests/
```

## Manifest

```json
{
  "id": "lifewheel",
  "name": "LifeWheel",
  "version": "1.0.0",
  "author": "Ranks Media",
  "description": "LifeWheel assessments and history.",
  "core_version": "^1.0",
  "php": ">=8.2",
  "dependencies": [],
  "permissions": [],
  "features": [],
  "settings_sections": [],
  "admin_menus": [],
  "member_menus": [],
  "events": [],
  "jobs": []
}
```

## Lifecycle

- `register()`: declare services, permissions, features, menus, settings sections, listeners.
- `boot()`: attach enabled runtime behavior.
- `install()`: run first install migrations/seeds.
- `activate()`: mark plugin enabled and register runtime behavior.
- `deactivate()`: disable runtime behavior without deleting data.
- `upgrade()`: apply versioned migrations and compatibility steps.
- `uninstall()`: remove registration; data deletion requires separate explicit confirmation.

## Dependency Rules

- A plugin may depend on core capabilities or other plugins.
- Core must block disabling a plugin that active plugins require.
- Optional integrations should use events/listeners rather than hard dependencies.

## Data Safety

Disable, delete files, and uninstall with data removal are separate operations.

- Disable: keep data.
- Delete files: keep data.
- Uninstall keeping data: remove registration but retain tables/data.
- Destructive data removal: require Super Admin authorization, warning, typed confirmation, and audit log.

## Security Limitation

PHP plugin upload means server-side executable code upload. On ordinary shared hosting, plugins cannot be strongly sandboxed. Only trusted plugins should be installed.
