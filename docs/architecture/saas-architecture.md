# SaaS Architecture

## Core Model

LifeWheel SaaS is a multi-user commercial platform. It must not be implemented as a single-user personal tool.

Core concepts:

- users
- roles
- permissions
- features
- packages
- package feature assignments
- package limits
- subscriptions
- feature overrides
- usage counters
- audit logs
- plugins
- settings

## Roles And Permissions

Initial roles:

- Super Admin
- Member

Future roles:

- Admin
- Editor
- Moderator
- Support

Application code should authorize by permissions/policies, not role-name strings.

## Entitlements

Feature entitlements are separate from role permissions.

Examples:

- `lifewheel.use`
- `lifewheel.history`
- `lifewheel.analytics`
- `goals.use`
- `habits.use`
- `projects.use`
- `lessons.use`
- `ai.analysis`
- `ai.coach`
- `ai.reviews`
- `forum.use`
- `gamification.use`

Use a centralized feature gate.

## Packages

Packages are editable data, not code constants.

Package properties:

- name
- slug
- description
- price/currency/interval
- active/public/featured flags
- trial settings
- landing page
- feature assignments
- usage limits
- payment-provider mappings
- SEO metadata

Default packages such as Free, Lessons, and Premium may be seeded but must remain editable.

## Member Settings

Member settings sections must be registry-driven:

- Profile
- Security
- 2FA
- Privacy
- Billing
- plugin-provided sections

Super Admin can hide/show allowed member settings sections.
