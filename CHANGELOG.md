# Changelog

## Unreleased

### Phase 20

- Added first-party Forum/Social/Messaging plugin with manifest, lifecycle entrypoint, plugin-owned routes, migration, views, and access helper.
- Added forum categories, topics, replies, social profiles, follows, blocks, direct conversations, messages, and member reports.
- Added feature entitlements for forum access, topic creation, replies, direct messages, and follows.
- Added plugin-owned `forum.moderate` permission and moderation report queue.
- Added tests covering manifest validity, entitlement denial, topic/reply creation, reply feature denial, message participant ownership, blocking, private-message report visibility, and moderation permission.

### Phase 19

- Added first-party AI Reviews plugin with manifest, lifecycle entrypoint, plugin-owned routes, migration, views, response schema, and context builder.
- Added daily, weekly, monthly, quarterly, yearly, and custom-range review generation through the Core AI gateway using the `ai.reviews` entitlement.
- Added stored AI review history with structured executive sections, period metadata, provider/model metadata, and usage recording.
- Added selective period-scoped private context retrieval across LifeWheel, Journal, Goals, Habits, Projects, and Lessons when those plugin tables exist.
- Added tests covering manifest validity, entitlement denial, custom period validation, context ownership, review generation, usage recording, and ownership checks.

### Phase 18

- Added first-party AI Coach plugin with manifest, plugin-owned routes, migration, views, response schema, and context builder.
- Added private Ask My Life conversations and structured assistant message storage through the Core AI gateway using the `ai.coach` entitlement.
- Added selective private context retrieval across LifeWheel, Journal, Goals, Habits, Projects, Lessons, and recent coach messages when those plugin tables exist.
- Added ownership-scoped conversation routing and server-side AI usage recording for coaching messages.
- Added tests covering manifest validity, entitlement denial, selective user context, conversation generation, usage recording, and ownership checks.

### Phase 17

- Added first-party AI Life Analysis plugin with manifest, plugin-owned routes, migration, views, schema, and context builder.
- Added structured AI life analysis generation through the Core AI gateway using the `ai.analysis` entitlement.
- Added stored analysis history and detail views with structured analysis sections.
- Updated mock AI provider to return schema-shaped structured outputs for local development.
- Added tests covering manifest validity, entitlement denial, no-assessment guard, selective user context, analysis generation, usage recording, and ownership checks.

### Phase 16

- Added first-party Lessons plugin with manifest, lifecycle entrypoint, plugin-owned routes, migration, views, and domain event.
- Added durable lesson CRUD with LifeWheel area links, source metadata, learned dates, and idempotency-ready storage.
- Added entitlement-gated lessons search using `lessons.search`.
- Added `lessons.search` to editable SaaS feature seeds for Lessons and Premium packages.
- Documented Lessons plugin boundaries, ownership checks, and deferred AI extraction work.

### Phase 15

- Added Core AI provider, model route, and usage event tables.
- Added encrypted provider settings, provider-neutral request/response DTOs, AI gateway, mock provider, OpenAI client, and Anthropic client.
- Added Super Admin AI settings screens and `admin.ai.manage` permission.
- Added seeded mock/OpenAI/Anthropic providers and default AI feature routes.
- Added tests covering AI admin authorization, encrypted secret handling, entitlement checks, usage recording, and monthly route limits.

### Phase 14

- Added first-party Gamification plugin with manifest, lifecycle entrypoint, plugin-owned routes, migrations, views, and LifeWheel assessment listener.
- Added XP ledger events, configurable gamification rules, idempotent award service, and level calculation helper.
- Added member XP ledger page and Super Admin gamification rule editing route.
- Added Core runtime plugin boot hook so enabled plugins can register event listeners.
- Added tests covering XP calculation, idempotent awards, entitlement denial, event awards, and admin rule updates.

### Phase 13

- Added first-party Projects plugin with manifest, lifecycle entrypoint, plugin-owned routes, migrations, views, and domain events.
- Added project CRUD with LifeWheel area links, statuses, priorities, start dates, and due dates.
- Added project tasks, task completion, and task-based project completion calculation.
- Added `projects.tasks` to the editable SaaS feature registry and default package seeds.
- Documented Projects plugin boundaries, ownership checks, and deferred goal/habit linking.

### Phase 12

- Added first-party Habits plugin with manifest, lifecycle entrypoint, plugin-owned routes, migrations, views, and domain event.
- Added positive, quit, and numeric habit definitions with LifeWheel area links, weekdays, targets, units, statuses, and notes.
- Added daily habit logging with one log per habit/date and recent history display.
- Added 28-day adherence calculation and `habits.stats` feature registration.
- Documented Habits plugin boundaries, ownership checks, and deferred advanced streak/AI work.

### Phase 11

- Added first-party Goals plugin with manifest, lifecycle entrypoint, plugin-owned routes, migrations, views, and domain events.
- Added measurable goal CRUD with LifeWheel area links, success criteria, baseline/current/target values, units, due dates, and statuses.
- Added milestones, milestone completion, progress records, and evidence-based progress calculation.
- Added `goals.progress` to the editable SaaS feature registry and default package seeds.
- Added tests covering progress calculation, entitlement denial, ownership checks, milestones, and progress records.

### Phase 10

- Added first-party Journal plugin with manifest, lifecycle entrypoint, plugin-owned routes, migration, views, and domain event.
- Added private journal entry CRUD with title, body, LifeWheel area links, mood, energy, and entry date.
- Added entitlement-gated journal search using `journal.search`.
- Added `journal.use` and `journal.search` to the editable SaaS feature registry and default package seeds.
- Documented Journal plugin boundaries, security checks, and deferred richer editor/media work.

### Phase 9

- Added first-party LifeWheel plugin with manifest, lifecycle entrypoint, plugin-owned routes, migrations, views, and domain event.
- Added LifeWheel assessment form, append-only history, overall score calculation, SVG wheel chart, ranking, and previous-score comparison.
- Added `lifewheel.analytics` to the editable SaaS feature registry.
- Documented LifeWheel plugin boundaries, security checks, and deferred AI/question-bank work.

### Phase 8

- Added data-driven member settings section registry and Super Admin visibility controls.
- Added member settings hub with profile, security, privacy, and billing sections.
- Added privacy request and data export metadata tables for export, correction, consent review, and erasure workflows.
- Added Super Admin privacy request queue with audited status updates.
- Added tests covering settings visibility, privacy request ownership, and admin authorization.

### Phase 7

- Added Core CMS pages with public rendering, admin editing, SEO metadata, legal page flags, and page version snapshots.
- Added Core blog with public index/detail pages, admin post editing, revisions, categories, tags, and publishing states.
- Seeded default public pages and added CMS/blog authorization and publishing tests.

### Phase 6

- Added Core SaaS feature registry, packages, package features, package limits, user packages, user feature overrides, and entitlement service.
- Added SaaS admin screens for managing features and packages plus user package/override controls.
- Added default editable Free, Lessons, and Premium AI package seed data and entitlement tests.

### Phase 5

- Added Super Admin plugin management UI, routes, controller, and dashboard entry.
- Added trusted plugin ZIP upload/update service with manifest validation, zip-slip checks, denied path segments, file allowlist, size limits, and target-path confinement.
- Added typed confirmation flows for uninstall and delete-files actions plus plugin admin/security tests.

### Phase 4

- Added Core plugin registry, manifest validation, lifecycle contract, lifecycle service, dependency checks, compatibility checks, and enabled plugin route/migration loading.
- Added plugin registration tables for permissions, features, menus, and settings sections.
- Added a tiny first-party Example Audit plugin and plugin-system tests.

### Phase 3

- Added database-backed RBAC with roles, permissions, pivots, protected roles, user suspension, seed data, and bootstrap Super Admin assignment.
- Added permission middleware, admin route gates, Super Admin dashboard links, user administration, role management, and permission management screens.
- Added admin authorization tests for guest denial, member denial, permission-granted access, protected role assignment, user suspension guardrails, and management actions.

### Phase 2

- Added registration, login, logout, email verification, password reset, profile editing, password updates, TOTP 2FA, recovery codes, security headers, and audit-log foundation.
- Protected member and admin shells behind authentication, email verification, and 2FA middleware where enabled.
- Added auth/security feature tests and documented the local Composer/OpenSSL blocker.

### Phase 1

- Added Laravel 12 project foundation for PHP 8.2+ and cPanel shared hosting.
- Added public front controller, Apache rewrite/security headers, routes, controllers, Blade shells, Vite/Tailwind assets, environment template, infrastructure migrations, and PHPUnit smoke tests.
- Documented local Composer/OpenSSL blocker.

### Phase 0

- Audited upstream `jmoraispk/2nd-brain-plugin`.
- Decided on clean standalone repository architecture instead of fork.
- Selected Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade, Alpine.js, and cPanel-compatible release packaging.
- Defined core/plugin boundaries, plugin lifecycle, security model, privacy architecture, entitlement model, AI abstraction, billing abstraction, and phase roadmap.
