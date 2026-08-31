# Phase 20 — Forum / Social / Messaging Plugin

Phase 20 adds the first-party community plugin. It keeps community content isolated from private LifeWheel, journal, goal, habit, project, lesson, and AI data.

## Scope

- `forum` plugin manifest, lifecycle entrypoint, routes, migration, views, and access helper.
- Forum categories, topics, replies, and report submission.
- Social profiles, following, blocking, and privacy flags.
- Direct messaging using ordinary HTTP requests and page refreshes.
- Admin moderation report queue protected by plugin-owned permission.

## Architecture

The plugin owns:

- `forum_profiles`
- `forum_categories`
- `forum_topics`
- `forum_replies`
- `social_follows`
- `social_blocks`
- `social_conversations`
- `social_conversation_participants`
- `social_messages`
- `social_reports`

Core remains responsible for authentication, 2FA, RBAC, feature entitlements, plugin registration, audit infrastructure, and the app shell.

## Entitlements And Permissions

Member feature entitlements:

- `forum.use`
- `forum.create_topic`
- `forum.reply`
- `forum.message`
- `forum.follow`

Admin permission:

- `forum.moderate`

This intentionally separates package-based community access from moderation powers.

## Security Review

- Forum member routes require auth, verified email, 2FA, and `forum.use`.
- Topic creation, replies, messages, and follows each require their own feature entitlement.
- Conversation reads require participant membership.
- Message sending checks participant membership and block relationships.
- Reports for private messages require the reporter to be a conversation participant.
- Admin moderation routes require server-side `forum.moderate` permission.
- Views use Blade escaping and do not render arbitrary executable HTML.

## Limitations

- No WebSocket server by design; cPanel-compatible HTTP refresh is used.
- Topic editing, rich media uploads, reactions, public profile browsing, and advanced moderation actions are deferred.
- Default categories are not seeded yet; they can be added through a later admin/settings pass.
