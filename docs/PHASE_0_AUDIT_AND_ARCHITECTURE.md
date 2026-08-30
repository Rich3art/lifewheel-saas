# Phase 0 Audit And Final Architecture

Project: LifeWheel SaaS  
Date: 2026-08-30  
Status: Phase 0 documentation baseline

## Executive Decision

Build a clean standalone Laravel SaaS repository rather than forking `jmoraispk/2nd-brain-plugin`.

Rationale:
- The upstream project is an Obsidian plugin, not a web SaaS application.
- Its storage model is Markdown files and frontmatter inside a private vault; LifeWheel SaaS needs multi-user MySQL/MariaDB tables, RBAC, entitlements, audit logs, payment integrations, and cPanel deployment.
- Its UI is tightly coupled to Obsidian DOM APIs and plugin lifecycle.
- Product concepts are useful, but direct architecture reuse would create the wrong production shape.

Recommended repository: `lifewheel-saas`.

## Upstream Source Audit

Repository inspected: `https://github.com/jmoraispk/2nd-brain-plugin` at commit `e5efd69`.

Architecture:
- TypeScript Obsidian plugin.
- Built with esbuild.
- Main runtime depends on Obsidian plugin APIs.
- Data is stored in the user's local vault as Markdown and frontmatter.
- AI calls are client/plugin initiated using user-provided OpenAI or Anthropic keys.
- No multi-user SaaS model, server authorization, billing, package system, or centralized admin.

Useful reusable concepts:
- Wheel-of-life area taxonomy and macro/sub-area grouping.
- Historical assessments/reviews as append-only records.
- Daily, weekly, monthly, quarterly, and yearly review workflows.
- Lessons ledger concept with deduplication.
- Goals as outcomes, separate from projects.
- Habits with positive, quit, numeric, mood, weight, weekday, and N-times-per-week variants.
- Event-derived goal progress instead of fabricated percentages.
- "Ask your life" using selective retrieval rather than sending all user data to an AI provider.
- Per-task AI model routing and cost awareness.
- AI prompt structure for reviews, planning, focus, drift, contradiction, leverage, and context.

Obsidian coupling:
- Imports `obsidian` types and APIs throughout.
- Uses `App`, `TFile`, `TFolder`, `Notice`, `MarkdownRenderer`, vault file reads/writes, and Obsidian settings UI.
- Uses local folder path conventions such as `🧑 Me/Logs`, `🤖 AI/Reviews`, and PARA folders.
- Uses markdown section parsing and frontmatter parsing as persistence logic.
- This should be translated into Laravel models/services/events, not copied as-is.

License findings:
- `package.json` declares `"license": "MIT"`.
- No root `LICENSE` file was present in the cloned upstream repository.
- README references Kepano 40 questions with attribution.
- Two PNG wheel images exist under `resources/`; image provenance/licensing is not independently established.

Commercial reuse policy:
- Reuse product ideas and public domain/general patterns freely.
- Do not copy upstream assets unless license provenance is verified.
- Do not copy large prompt blocks verbatim into commercial code without attribution and review.
- If any upstream code is adapted directly, preserve attribution in `THIRD_PARTY_NOTICES.md` and include the MIT license text/source reference.

## Final Technology Decision

Use Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade, Alpine.js, Vite-built CSS/JS, and standard SMTP.

Reasoning:
- Laravel 13 is current but requires PHP 8.3.
- The target hosting brief specifies ordinary cPanel shared hosting with PHP 8.2+ and no permanent Node server.
- Laravel 12 remains the better compatibility target for cPanel PHP 8.2 environments.
- Node/npm may be used during development/build only; production releases must ship compiled assets and vendor dependencies.

Production constraints:
- No Docker, VPS/root, Redis, queue daemon, WebSocket server, PostgreSQL, MongoDB, Python worker, or permanent Node process.
- Use Laravel scheduler through cPanel cron.
- Use database-backed jobs/tasks where asynchronous behavior is required.
- Use polling/AJAX where real-time UX is needed.

## Core Boundary

Core contains permanent platform infrastructure only:
- Auth, registration, login/logout, email verification, password reset, session security, 2FA/TOTP.
- RBAC, permissions, Super Admin, user administration.
- Feature entitlement engine, package manager, package limits, user overrides.
- Plugin manager and plugin lifecycle infrastructure.
- AI provider/settings framework and usage metering.
- CMS/pages/blog/legal policy versioning.
- Privacy center foundation, export/erasure request workflow.
- Audit logging, notifications, SMTP, media/file framework.
- Admin/member shells, settings visibility registry, cron/scheduler, domain events, PWA base.

Core must not contain LifeWheel scoring, habits, goals, lessons, forum, gamification, or AI product experiences except as minimal interfaces/events needed by plugins.

## Plugin Boundary

First-party plugins:
- `lifewheel`
- `journal` unless Phase 0 implementation review later proves it must be core
- `goals`
- `habits`
- `projects`
- `lessons`
- `gamification`
- `ai-life-analysis`
- `ai-coach`
- `ai-reviews`
- `forum-social`
- `payments-stripe`
- `payments-paypal`
- `payments-paystack`
- `payments-whop`

Plugins own their domain tables and migrations. Core owns users, roles, permissions, features, packages, subscriptions, settings, plugins, audit logs, CMS, media, privacy, AI provider config, and normalized billing infrastructure.

## Plugin Specification

Plugin directory:

```text
plugins/
  LifeWheel/
    plugin.json
    src/
    routes/
    database/migrations/
    resources/views/
    resources/assets/
    tests/
```

Manifest baseline:

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

Lifecycle:
- `register()`: bind services, policies, permissions, features, menus, settings sections.
- `boot()`: activate routes, listeners, scheduled tasks, views, assets.
- `install()`: run initial plugin migrations and seed plugin defaults.
- `activate()`: enable routes/menus/listeners without deleting data.
- `deactivate()`: disable functionality and keep data.
- `upgrade()`: run versioned migrations and compatibility checks.
- `uninstall(keepData: bool)`: remove plugin registration; data deletion requires explicit destructive confirmation.

## Plugin Security Model

Only Super Admin can upload or manage plugins.

Required controls:
- CSRF protection on all admin actions.
- Server-side authorization on every lifecycle action.
- ZIP size limits.
- ZIP extension and MIME validation.
- Zip-slip/path traversal protection.
- Extraction only into approved plugin staging directories.
- Manifest JSON schema validation.
- Deny executable upload outside plugin directories.
- Compatibility and dependency checks before activation.
- Data-preserving disable/delete behavior.
- Explicit typed confirmation for destructive data removal.
- Audit logs for install, enable, disable, update, uninstall, delete, and destructive cleanup.

Important limitation:
- Standard shared hosting cannot sandbox arbitrary PHP plugins. Installed PHP plugins have server-side execution capability. The product must document that only trusted plugins should be installed.

## SaaS And Entitlement Architecture

Separate these concerns:
- RBAC permissions: what an administrative or member role may do.
- Package feature entitlements: what commercial package features a user can access.
- Usage limits: how much of a metered feature the user may consume.
- User overrides: explicit grants/denials independent of package.

Core tables should include:
- `users`
- `roles`
- `permissions`
- `role_user`
- `permission_role`
- `features`
- `packages`
- `package_features`
- `package_limits`
- `subscriptions`
- `feature_overrides`
- `usage_counters`
- `audit_logs`

Entitlement checks must go through one central service, for example:
- `FeatureGate::allows($user, 'lifewheel.use')`
- `FeatureGate::limit($user, 'ai.coach.messages_per_month')`

Never scatter checks such as `if package == premium` through controllers or views.

## Payment Architecture

Use provider-neutral billing core plus provider plugins.

Core billing owns:
- normalized subscription state
- package mappings
- billing events
- entitlement activation/deactivation
- payment audit logs
- provider configuration registry

Provider plugins implement:
- `createCheckout()`
- `createSubscription()`
- `cancelSubscription()`
- `changeSubscription()`
- `getSubscription()`
- `verifyWebhook()`
- `handleWebhook()`
- `refund()` where supported
- `customerPortal()` where supported

Webhook requirements:
- Verify provider signature server-side.
- Store idempotency keys/event IDs.
- Treat browser redirects as untrusted.
- Never upgrade package or entitlements from client-submitted payment data.
- Log safely without secrets.

## AI Architecture

Core owns provider configuration and routing:
- providers: OpenAI first, Anthropic second
- encrypted credentials
- model registry
- feature-to-model routes
- usage metering
- cost metadata
- package limits
- AI audit metadata

AI product features are plugins:
- AI Life Analysis
- AI Coach / Ask My Life
- AI Reviews
- AI Goal Designer
- AI Habit Designer

AI privacy rules:
- Provider keys never reach frontend JavaScript.
- Prompts must receive only the minimum relevant user data.
- Selective retrieval is required for Ask My Life.
- User ownership policies apply before any data is sent to AI.
- AI output must avoid presenting correlation as proven causation.

## Event Architecture

Use Laravel events/listeners plus database-backed event logs where replay/idempotency matters.

Core events:
- `UserRegistered`
- `SubscriptionActivated`
- `SubscriptionCancelled`
- `PrivacyDeletionRequested`
- `PluginActivated`
- `PluginDisabled`

Plugin events:
- `LifeWheelAssessmentCompleted`
- `LifeWheelImproved`
- `HabitCompleted`
- `GoalMilestoneCompleted`
- `ForumTopicCreated`

Plugins should react to events instead of reaching into each other's internals.

## Database Architecture

Core migrations should create platform tables only. Plugin migrations create feature-specific tables.

Examples:
- LifeWheel plugin: `lifewheel_assessments`, `lifewheel_scores`, `lifewheel_reflections`.
- Goals plugin: `goals`, `goal_milestones`, `goal_records`, relationship tables.
- Habits plugin: `habits`, `habit_logs`, `habit_values`, relationship tables.
- Forum plugin: `forum_categories`, `forum_topics`, `forum_replies`, `social_follows`, `social_blocks`, `social_conversations`, `social_messages`, `social_reports`.
- Gamification plugin: `xp_events`, `gamification_rules`.

Every private plugin table must support ownership checks by `user_id` or another policy-verifiable ownership chain.

## Privacy Architecture

Do not claim legal compliance automatically. Build support for GDPR, UK GDPR, CCPA/CPRA, and UAE PDPL aligned workflows:
- privacy information pages
- profile correction
- data export request
- deletion/erasure request
- consent/preferences where applicable
- request status
- admin privacy queue
- policy versioning and acceptance records
- retention documentation

Exports must exclude password hashes, security tokens, secrets, and other users' private data.

Deletion must be workflow-based:
- request
- identity confirmation
- consequence warning
- optional grace period
- processing
- delete/anonymize eligible data
- preserve legally justified records
- audit completion

## Threat Model

Priority risks:
- IDOR/BOLA across private LifeWheel, journal, goals, habits, lessons, AI, messages, and exports.
- Privilege escalation to Super Admin.
- Package/feature entitlement bypass.
- Client-side payment spoofing.
- Unverified/replayed payment webhooks.
- Plugin ZIP path traversal and arbitrary code upload.
- XSS in CMS, blog, forum, rich text, messages, and plugin views.
- CSRF against admin/plugin/payment/settings actions.
- Unsafe file uploads.
- AI prompt oversharing and cross-user data leakage.
- Secrets exposure in frontend assets, logs, releases, or Git.

Baseline mitigations:
- Laravel policies on every private model.
- Central `FeatureGate`.
- Form requests/validators for every mutation.
- CSRF middleware.
- Rate limiting login, 2FA, password reset, webhooks where appropriate, and messaging.
- Escaped Blade output by default; sanitize rich text.
- Signed/verified webhooks with idempotent processing.
- Secure cookie/session config.
- Security headers and production error sanitization.
- Audit logs for sensitive admin actions.

## Release Model

Produce release ZIPs suitable for cPanel:
- Core release ZIP includes Laravel app, compiled assets, Composer vendor dependencies, installer, and docs.
- First-party plugin ZIPs are packaged independently.
- End user should not need SSH, Docker, Composer, npm, or a process manager for normal install/update.

Future GitHub Actions:
- run tests
- static/security checks where practical
- compile assets
- package core
- package plugins
- attach release artifacts

## Final Phase Roadmap

Use the user's supplied roadmap as the controlling roadmap, with these Phase 0 refinements:
- Phase 1 must replace the current Next.js prototype with a Laravel 12 cPanel-compatible foundation.
- Phase 4 plugin architecture must remain early and blocking before LifeWheel feature development.
- Payment providers remain plugins after provider-neutral billing core.
- Journal should initially be planned as a plugin, not core, unless later implementation proves a core privacy/export dependency requires a small core capture interface.

## Sources

- Upstream repository clone: `jmoraispk/2nd-brain-plugin`, commit `e5efd69`.
- Upstream `package.json`: declares MIT license and dependencies.
- Laravel 13 release/deployment docs: PHP 8.3 minimum. See https://laravel.com/docs/13.x/releases and https://laravel.com/docs/13.x/deployment.
- Laravel release support table: Laravel 12 supports PHP 8.2 through 8.5. See https://laravel.com/docs/13.x/releases.
