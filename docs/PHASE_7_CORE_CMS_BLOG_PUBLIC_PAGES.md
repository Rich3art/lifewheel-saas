# Phase 7 Core CMS Blog And Public Pages

Phase 7 adds the Core CMS and blog foundation. This is platform infrastructure and remains separate from product plugins such as LifeWheel, AI Coach, Forum, and Gamification.

## Implemented Scope

- Public pages served from database content.
- Admin page list, create, edit, publish, schedule, unpublish, and draft workflow.
- Page SEO fields: SEO title, meta description, canonical URL, and Open Graph JSON storage.
- Page version snapshots on every save.
- Legal page flag for Privacy Policy, Terms, Cookie Policy, and Privacy Rights pages.
- Seeded default pages: About, Privacy Policy, Terms, Cookie Policy, Privacy Rights, Contact.
- Public blog index and post detail pages.
- Admin blog list, create, edit, publish, schedule, unpublish, and draft workflow.
- Blog post revisions on every save.
- Blog categories and tags.
- Featured image path placeholder.
- Admin dashboard entries for CMS and Blog.

## Authorization Review

- Page admin routes require `admin.pages.manage`.
- Blog admin routes require `admin.blog.manage`.
- Normal members cannot access admin authoring routes by direct URL.
- Public routes show only content with published status and a publish date that is not in the future.
- Public rendering escapes content with `e()` and formats line breaks with `nl2br`.
- Admin forms use CSRF-protected POST/PUT requests.

## Legal Versioning

Pages marked as legal retain immutable `page_versions` snapshots. Current public content is tied to `current_version_id`, so future policy acceptance workflows can refer to exact published versions instead of mutable page rows.

## Known Limitations

- Rich-text editing is plain textarea in this phase; a richer editor can be added later without changing the data model.
- Media upload management is not fully implemented yet; featured image path is a placeholder.
- Package landing page blocks are deferred, though packages already have landing-page slugs from Phase 6.
- Full PHPUnit execution is still blocked locally because Composer dependencies are unavailable and PHP OpenSSL is missing.

## Tests Added

- Members cannot access pages admin.
- Members cannot access blog admin.
- Admins with page/blog permissions can access the correct areas.
- Saving a page creates a version.
- Draft pages are hidden publicly.
- Published pages are visible publicly.
- Publishing a blog post creates a revision.
- Draft blog posts are hidden publicly.
