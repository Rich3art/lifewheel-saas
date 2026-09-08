# Clean Install And Upgrade QA

This checklist is the Phase 32 gate before production deployment. It must be performed against release artifacts, not a working tree.

## Required Environment

- PHP 8.2+
- MySQL or MariaDB
- Apache with `.htaccess`
- PDO MySQL
- cURL
- JSON
- OpenSSL
- ZIP
- writable `storage/`
- writable `bootstrap/cache/`
- standard SMTP credentials for email verification/password reset testing

## Artifact Preparation

1. Run the GitHub Actions `Release Packages` workflow.
2. Download the generated artifact bundle.
3. Confirm it contains:
   - `lifewheel-core-{version}.zip`
   - one ZIP per first-party plugin
   - `release-manifest.json`
4. Confirm the Core ZIP does not contain:
   - `.env`
   - `.git`
   - `node_modules`
   - `tests`
   - local logs/cache/sessions
   - `storage/app/installed.lock`

## Clean Install Smoke Test

1. Extract the Core ZIP into a fresh cPanel-like web root.
2. Point the domain or local Apache vhost at `public/`.
3. Create a fresh MySQL/MariaDB database and user.
4. Visit `/install`.
5. Verify requirements pass or show actionable failures.
6. Test the database connection.
7. Complete installation with a strong Super Admin password.
8. Confirm `/install` becomes unavailable after install.
9. Confirm `storage/app/installed.lock` exists.
10. Sign in as Super Admin.

## Core Authorization QA

Verify:

- guest users are redirected away from `/admin/dashboard`
- normal members cannot open Super Admin routes
- Super Admin can access admin dashboard
- suspended users cannot use authenticated areas
- role and permission assignment requires admin permission
- package/feature access is enforced server-side
- member settings visibility is data-driven

## Plugin Lifecycle QA

For each first-party plugin ZIP:

1. Upload through the Plugin Manager.
2. Install.
3. Enable.
4. Confirm routes/menus appear only for entitled users.
5. Disable.
6. Confirm direct URLs stop working.
7. Re-enable.
8. Confirm data is still present.
9. Update with the same or newer ZIP where applicable.
10. Uninstall while keeping data.
11. Confirm plugin files can be deleted without deleting user data.
12. Perform destructive data removal only in a disposable QA database.

## Feature QA

Verify at least one happy path and one denial path for:

- LifeWheel assessment/history
- Journal entries
- Goals
- Habits
- Projects
- Gamification XP ledger
- Lessons
- AI Life Analysis
- AI Coach
- AI Reviews
- Forum topics/replies
- Following/blocking
- Direct messages
- Stripe webhook denial and verified test event
- PayPal webhook denial and verified test event
- Paystack webhook denial and verified test event
- Whop webhook denial and verified test event
- Privacy export
- Privacy erasure request
- Blog publishing
- CMS legal page versioning
- PWA manifest/service worker/offline fallback

## IDOR/BOLA Checks

For every private object type, create two users and verify user B cannot access user A data by changing IDs in direct URLs or form submissions.

Include:

- LifeWheel assessments
- journals
- goals
- habits
- projects
- lessons
- AI conversations/reviews/analyses
- privacy exports
- forum private messages
- billing history

## Upgrade QA

1. Install a previous Core release artifact.
2. Install and enable all first-party plugin artifacts from that release.
3. Create representative test data.
4. Apply the new Core artifact.
5. Run migrations.
6. Upload/update each new plugin artifact.
7. Confirm data remains intact.
8. Confirm plugin versions update correctly.
9. Confirm disabled plugins remain disabled.
10. Confirm installer remains locked.

## Production Stop Rule

Do not deploy to `lifewheel.ranksmedia.com` until Phase 33 is explicitly approved and cPanel access is provided in that phase.
