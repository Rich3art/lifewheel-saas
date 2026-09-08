# Changelog

## Unreleased

### Phase 33

- Deployed LifeWheel SaaS to `https://lifewheel.ranksmedia.com` on cPanel shared hosting.
- Configured the Laravel document root to the production `public` directory.
- Created the production MySQL database/user and `info@lifewheel.ranksmedia.com` mailbox.
- Ran production dependency installation, migrations, seeders, installer locking, and health checks.
- Fixed Super Admin installer bootstrap so `email_verified_at` can be persisted by the `User` model.
- Verified production debug mode is off, `/install` is locked, `/login` is live, `/health` is OK, admin routes require authentication, and Super Admin admin access requires 2FA setup.

### Phase 32

- Added a Phase 32 release-readiness QA script that validates installer, release workflow, packaging exclusions, and plugin manifest/entry integrity without Laravel bootstrapping.
- Added a cPanel clean-install and upgrade QA checklist for artifact validation, installer flow, authorization, plugin lifecycle, IDOR/BOLA, privacy, payments, and PWA checks.
- Updated project documentation to mark Phase 32 as the active baseline.

### Phase 31

- Added a standalone PHP release packager for cPanel-compatible Core and first-party plugin ZIP artifacts.
- Added plugin manifest validation during packaging.
- Added deterministic release manifest output for Core and plugin artifacts.
- Added a manual GitHub Actions workflow for installing dependencies, building assets, validating the packager, and uploading release ZIPs.
- Documented the release/update model, artifact boundaries, exclusions, and remaining clean-install QA work.

### Phase 30

- Added a public pre-install `/install` browser installer with cPanel-oriented server requirement checks.
- Added MySQL/MariaDB connection testing without exposing database passwords in old input.
- Added installer services for installation locking, environment writing, requirement checks, migrations/seeding, and first Super Admin creation.
- Locked the installer through `APP_INSTALLED`, `storage/app/installed.lock`, and existing users in the configured database.
- Changed pre-install defaults to file-backed sessions and cache so the installer can render before database tables exist.
- Added Phase 30 installer documentation and focused route/requirement tests.

### Phase 29

- Added installable PWA manifest with standalone display, app identity, icons, shortcuts, theme color, and app scope.
- Added static SVG app icons and maskable icon for cPanel-compatible release packaging.
- Added offline fallback page and service worker with app-shell caching, navigation fallback, and build/icon asset caching.
- Added layout metadata for manifest, theme color, mobile web app capability, Apple web app capability, and icons.
- Added progressive service worker registration in the compiled app bundle.
- Added mobile safe-area and tap-highlight polish.
- Added PWA feature tests for metadata, manifest, service worker, and offline page availability.

### Phase 28

- Added protected-admin 2FA enforcement middleware with configurable `FORCE_ADMIN_TWO_FACTOR` support.
- Added stricter baseline security headers including Content Security Policy, Cross-Origin-Opener-Policy, and cross-domain policy denial.
- Changed the default session encryption setting to enabled.
- Hardened plugin ZIP validation against dot-segment entries, null-byte paths, unsafe parent path variants, and excessive uncompressed size.
- Hardened plugin target path confinement to require exact plugin root containment instead of prefix-only matching.
- Hardened privacy export downloads so database paths must resolve inside the private export directory.
- Added security regression tests for protected-admin 2FA, privacy export path confinement, plugin ZIP path rejection, and security headers.

### Phase 27

- Added privacy consent, policy acceptance, and privacy settings tables with models and seed defaults.
- Added protected JSON data export generation to private storage for Core and known plugin-owned user tables.
- Added member export download route with ownership, status, expiry, and file existence checks.
- Added member consent preference updates for product updates and optional research/feedback contact.
- Added legal policy acceptance records tied to immutable `page_versions`.
- Added request due dates, user identity confirmation metadata, admin queue metrics, and resolution summaries.
- Added admin processing support for data export generation and confirmed erasure/anonymization workflows.
- Updated member Privacy Center and Super Admin privacy queue UI.
- Added tests for ready exports, export ownership, consent updates, policy acceptance, admin export processing, and erasure confirmation.

### Phase 26

- Added real Super Admin billing metrics for total subscriptions, active subscriptions, past-due subscriptions, MRR, and annual run rate.
- Added editable provider mapping management for external product IDs, price IDs, amount, currency, and active status.
- Added member package checkout selection using active public packages and enabled provider mappings.
- Added a centralized Core checkout router that hands package-provider mappings to enabled payment plugin checkout routes.
- Added recent invoice and billing event visibility to the Super Admin billing console.
- Hardened direct provider checkout services so inactive or private packages cannot be purchased through manipulated plugin URLs.
- Added tests for billing metrics, provider mapping updates, member checkout visibility, inactive mapping denial, and provider route handoff.

### Phase 25

- Added first-party Whop provider plugin with manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- Added Whop checkout configuration payload preparation for active provider package mappings.
- Added verified Whop webhook route using Standard Webhooks HMAC-SHA256 verification over `{webhook-id}.{webhook-timestamp}.{raw body}`.
- Added replay protection for stale Whop webhook timestamps.
- Added Whop webhook handling for payment success, membership activation/deactivation, membership cancel-at-period-end changes, invoice events, and ignored events.
- Integrated Whop webhook events with Billing Core subscription activation, cancellation, invoice recording, entitlement sync, and idempotent delivery storage.
- Added tests covering manifest validity, invalid signature rejection, stale timestamp rejection, verified payment activation, entitlement sync, duplicate delivery idempotency, and disabled mapping denial.

### Phase 24

- Added first-party Paystack provider plugin with manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- Added Paystack transaction initialization payload preparation for active provider package mappings.
- Added verified Paystack webhook route using HMAC-SHA512 verification of `x-paystack-signature` against the raw request body and secret key.
- Added Paystack webhook handling for charge success, subscription status events, invoice events, and ignored events.
- Integrated Paystack webhook events with Billing Core subscription activation, cancellation, invoice recording, entitlement sync, and idempotent event storage.
- Added tests covering manifest validity, invalid signature rejection, verified charge activation, entitlement sync, duplicate webhook idempotency, and disabled mapping denial.

### Phase 23

- Added first-party PayPal provider plugin with manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- Added PayPal checkout order payload preparation for active provider package mappings.
- Added verified PayPal webhook route using raw-body CRC32, configured webhook ID, PayPal transmission headers, certificate URL, and RSA signature verification.
- Added PayPal webhook handling for order approval, payment capture completion, subscription activation/update/cancellation/expiry/suspension, and ignored events.
- Integrated PayPal webhook events with Billing Core subscription activation, cancellation, entitlement sync, and idempotent subscription event storage.
- Added tests covering manifest validity, invalid signature rejection, verified payment activation, entitlement sync, duplicate webhook idempotency, and disabled mapping denial.

### Phase 22

- Added first-party Stripe provider plugin with manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- Added checkout payload preparation for active Stripe package mappings without granting browser-side entitlements.
- Added verified Stripe webhook route using raw body HMAC verification against the `Stripe-Signature` header.
- Added Stripe webhook handling for checkout completion, subscription updates/deletion, invoice paid, and invoice payment failure.
- Integrated Stripe webhook events with Billing Core subscription activation, cancellation, invoice recording, and idempotent subscription event storage.
- Added tests covering manifest validity, invalid signature rejection, verified checkout activation, entitlement sync, duplicate webhook idempotency, and disabled mapping denial.

### Phase 21

- Added Core billing tables for payment providers, package-provider mappings, normalized subscriptions, subscription events, and billing invoices.
- Added billing models and provider-neutral `BillingManager` service for activation, entitlement sync, cancellation, invoice recording, and idempotent event recording.
- Added Super Admin billing console for providers, package mappings, manual subscription activation, and cancellation.
- Added member billing history page scoped to the authenticated user.
- Added `admin.billing.manage` permission and default provider seed records for manual, Stripe, PayPal, Paystack, and Whop.
- Added tests covering admin authorization, manual activation, entitlement sync, immediate cancellation, provider mappings, and member billing privacy.

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
