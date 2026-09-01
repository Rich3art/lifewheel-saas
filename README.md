# LifeWheel SaaS

Commercial, modular, AI-capable LifeWheel SaaS platform designed for ordinary cPanel shared hosting.

## Current Status

Phase 23 is the active baseline. The project has been redirected from an earlier Next.js prototype toward the approved target architecture:

- Laravel 12
- PHP 8.2+
- MySQL/MariaDB
- Blade and Alpine.js
- cPanel-compatible release ZIPs
- Core plus independently managed plugins

Do not build feature plugins until the core/plugin architecture phases are complete.

## Phase Discipline

This project is phase-gated. At the end of every phase:

- complete only that phase
- run relevant tests
- perform security/authorization review
- update documentation
- commit
- push
- stop and request approval for the next phase

## Phase 0 Documentation

Start here:

- [Phase 0 Audit And Final Architecture](docs/PHASE_0_AUDIT_AND_ARCHITECTURE.md)
- [Phase 1 Core Foundation](docs/PHASE_1_CORE_FOUNDATION.md)
- [Phase 2 Auth And Security](docs/PHASE_2_AUTH_SECURITY.md)
- [Phase 3 RBAC Super Admin And Users](docs/PHASE_3_RBAC_SUPER_ADMIN_USERS.md)
- [Phase 4 Core Plugin System](docs/PHASE_4_CORE_PLUGIN_SYSTEM.md)
- [Phase 5 Core Plugin Admin](docs/PHASE_5_CORE_PLUGIN_ADMIN.md)
- [Phase 6 Core SaaS Features And Packages](docs/PHASE_6_CORE_SAAS_FEATURES_PACKAGES.md)
- [Phase 7 Core CMS Blog And Public Pages](docs/PHASE_7_CORE_CMS_BLOG_PUBLIC_PAGES.md)
- [Phase 8 Core Member Settings And Privacy Foundation](docs/PHASE_8_CORE_MEMBER_SETTINGS_PRIVACY_FOUNDATION.md)
- [Phase 9 LifeWheel Plugin](docs/PHASE_9_LIFEWHEEL_PLUGIN.md)
- [Phase 10 Journal Plugin](docs/PHASE_10_JOURNAL_PLUGIN.md)
- [Phase 11 Goals Plugin](docs/PHASE_11_GOALS_PLUGIN.md)
- [Phase 12 Habits Plugin](docs/PHASE_12_HABITS_PLUGIN.md)
- [Phase 13 Projects Plugin](docs/PHASE_13_PROJECTS_PLUGIN.md)
- [Phase 14 Gamification Plugin](docs/PHASE_14_GAMIFICATION_PLUGIN.md)
- [Phase 15 Core AI Service](docs/PHASE_15_CORE_AI_SERVICE.md)
- [Phase 16 Lessons Plugin](docs/PHASE_16_LESSONS_PLUGIN.md)
- [Phase 17 AI Life Analysis Plugin](docs/PHASE_17_AI_LIFE_ANALYSIS_PLUGIN.md)
- [Phase 18 AI Coach Plugin](docs/PHASE_18_AI_COACH_PLUGIN.md)
- [Phase 19 AI Reviews Plugin](docs/PHASE_19_AI_REVIEWS_PLUGIN.md)
- [Phase 20 Forum Social Messaging Plugin](docs/PHASE_20_FORUM_SOCIAL_MESSAGING_PLUGIN.md)
- [Phase 21 Billing Core](docs/PHASE_21_BILLING_CORE.md)
- [Phase 22 Stripe Plugin](docs/PHASE_22_STRIPE_PLUGIN.md)
- [Phase 23 PayPal Plugin](docs/PHASE_23_PAYPAL_PLUGIN.md)
- [Plugin Architecture](docs/plugins/plugin-architecture.md)
- [Security Model](docs/security/security-model.md)
- [cPanel Deployment Architecture](docs/deployment/cpanel-architecture.md)
- [Privacy Architecture](docs/privacy/privacy-architecture.md)

## Upstream Attribution

The upstream reference project is:

https://github.com/jmoraispk/2nd-brain-plugin

Useful product concepts were studied from that repository. The SaaS application will be a clean standalone Laravel platform, not an Obsidian plugin fork.

## Current Auth Foundation

Phase 2 adds core registration, login, logout, email verification, password reset, profile updates, password changes, TOTP 2FA, recovery codes, security headers, and account/security audit logging. Dashboard shells are protected by authentication, verified email, and 2FA challenge middleware where enabled.

## Current RBAC Foundation

Phase 3 adds database-backed roles, permissions, protected Super Admin bootstrapping, user administration, user suspension, direct user permission overrides, and permission-gated admin routes.

## Current Plugin Foundation

Phase 4 adds the Core plugin registry, manifest parser, lifecycle contract, lifecycle service, enabled route/migration loading, plugin registration tables, and a tiny first-party example plugin. The Super Admin plugin upload/manage UI is intentionally deferred to Phase 5.

## Current Plugin Admin

Phase 5 adds the permission-gated Super Admin plugin manager for installing, enabling, disabling, updating, uninstalling, and deleting trusted plugin packages with ZIP validation and typed destructive confirmations.

## Current SaaS Foundation

Phase 6 adds the central feature registry, editable packages, package limits, user package assignment, user-level feature overrides, and server-side entitlement checks.

## Current CMS Foundation

Phase 7 adds database-backed public pages, legal page version snapshots, blog posts, revisions, categories, tags, SEO fields, and admin-only publishing routes.

## Current Member Settings And Privacy Foundation

Phase 8 adds a data-driven member settings hub, Super Admin visibility controls, privacy request records, pending data export metadata, and an admin privacy request queue.

## Current LifeWheel Plugin

Phase 9 adds the first real first-party product plugin with append-only LifeWheel assessments, score history, a wheel chart, weakest-to-strongest ranking, previous-score comparison, and plugin-owned migrations/routes/views.

## Current Journal Plugin

Phase 10 adds a first-party private journal plugin with member-only CRUD, mood/energy tracking, LifeWheel area links, entitlement-gated search, and plugin-owned migrations/routes/views.

## Current Goals Plugin

Phase 11 adds a first-party measurable goals plugin with member-only goal CRUD, milestones, progress records, LifeWheel area links, entitlement-gated progress tracking, and plugin-owned migrations/routes/views.

## Current Habits Plugin

Phase 12 adds a first-party habits plugin with member-only habit CRUD, positive/quit/numeric habit types, weekdays, LifeWheel area links, daily logs, recent history, adherence calculation, and plugin-owned migrations/routes/views.

## Current Projects Plugin

Phase 13 adds a first-party projects plugin with member-only project CRUD, priority/status tracking, LifeWheel area links, project tasks, task completion, and plugin-owned migrations/routes/views.

## Current Gamification Plugin

Phase 14 adds a first-party gamification plugin with an XP ledger, configurable rules, member XP page, LifeWheel assessment XP listener, and plugin-owned migrations/routes/views.

## Current AI Service Foundation

Phase 15 adds the Core AI provider abstraction, encrypted provider settings, model routing, usage metering, entitlement checks, mock local fallback, and Super Admin AI settings screens.

## Current Lessons Plugin

Phase 16 adds a first-party lessons ledger plugin with member-only lesson CRUD, LifeWheel area links, source metadata, idempotency-ready storage, entitlement-gated search, and plugin-owned migrations/routes/views.

## Current AI Life Analysis Plugin

Phase 17 adds a first-party AI Life Analysis plugin that uses Core AI routing, selective LifeWheel context, structured output schemas, stored analysis history, and entitlement-gated member routes.

## Current AI Coach Plugin

Phase 18 adds a first-party AI Coach plugin for private Ask My Life conversations, selective user-owned context retrieval, structured coaching responses, server-side AI usage metering, and entitlement-gated member routes.

## Current AI Reviews Plugin

Phase 19 adds a first-party AI Reviews plugin for daily, weekly, monthly, quarterly, yearly, and custom-range executive reviews using period-scoped private context and Core AI routing.

## Current Forum Social Messaging Plugin

Phase 20 adds a first-party community plugin with forum categories, topics, replies, following, blocking, private messages, report submission, and permission-gated moderation.

## Current Billing Core

Phase 21 adds provider-neutral billing infrastructure with payment providers, package-provider mappings, normalized subscriptions, subscription events, invoices, a billing service, Super Admin billing console, and member billing history.

## Current Stripe Plugin

Phase 22 adds a first-party Stripe provider plugin with checkout payload preparation, verified webhook handling, subscription/invoice event translation, and Billing Core integration.

## Current PayPal Plugin

Phase 23 adds a first-party PayPal provider plugin with checkout order payload preparation, local RSA webhook verification, subscription/payment event translation, and Billing Core integration.
