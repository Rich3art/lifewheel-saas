# Database Architecture

## Database

Use MySQL or MariaDB for cPanel compatibility.

## Core Tables

Core owns platform infrastructure tables:

- users
- roles
- permissions
- role_user
- permission_role
- features
- packages
- package_features
- package_limits
- subscriptions
- feature_overrides
- usage_counters
- plugins
- settings
- user_setting_visibility
- pages
- page_versions
- blog_posts
- blog_categories
- blog_tags
- media
- privacy_requests
- policy_versions
- policy_acceptances
- audit_logs
- ai_providers
- ai_model_routes
- ai_usage_events
- payment_providers
- payment_events

## Plugin Table Ownership

Plugins own feature-specific tables.

Examples:

- LifeWheel: `lifewheel_assessments`, `lifewheel_scores`, `lifewheel_reflections`.
- Goals: `goals`, `goal_milestones`, `goal_records`, relationship tables.
- Habits: `habits`, `habit_logs`, `habit_values`, relationship tables.
- Lessons: `lessons`, `lesson_links`.
- Gamification: `xp_events`, `gamification_rules`.
- Forum/Social: `forum_categories`, `forum_topics`, `forum_replies`, `social_follows`, `social_blocks`, `social_conversations`, `social_messages`, `social_reports`.

## Authorization Design

Every private plugin table must include:

- direct `user_id`, or
- a policy-verifiable ownership chain.

Queries must enforce ownership or privileged policies. Do not rely on hidden navigation.

## Historical Data

Historical user data should be append-only where product meaning depends on history:

- LifeWheel assessments
- habit logs
- goal records
- review outputs
- lessons
- XP events
- audit logs
- policy versions

Avoid overwriting historical records.
