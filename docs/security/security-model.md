# Security Model

## Priority

Security is a core platform requirement, not a plugin feature.

## Baseline Controls

- Laravel authentication with secure password hashing.
- Email verification and password reset.
- TOTP 2FA with recovery codes.
- CSRF protection for browser mutations.
- Server-side authorization policies.
- RBAC for administrative permissions.
- Feature entitlement checks for package access.
- Rate limiting for login, password reset, 2FA, messaging, AI, and sensitive forms.
- Escaped output by default.
- Sanitized rich text for CMS/blog/forum content.
- Signed and idempotent payment webhooks.
- Secure session/cookie configuration.
- Production error sanitization.
- Audit logging for sensitive actions.

## IDOR / BOLA Rule

Never load private records by ID alone.

Controllers/services must query through ownership or policy-verifiable relationships. Example:

```php
$journal = $request->user()->journals()->findOrFail($id);
```

Avoid:

```php
$journal = Journal::findOrFail($id);
```

## Plugin Upload Risks

Only Super Admin can upload plugins.

Plugin ZIP validation must include:

- CSRF
- authorization
- file size limit
- MIME and extension checks
- zip-slip prevention
- manifest schema validation
- compatibility checks
- dependency checks
- extraction to approved directories only
- audit logging

Third-party PHP plugins execute server-side code. The platform cannot fully sandbox arbitrary PHP plugins on shared hosting.

## AI Privacy

- Provider keys remain server-side.
- Prompts receive the minimum relevant user data.
- User ownership checks happen before retrieval.
- AI logs must not store provider secrets or raw tokens.
- Cross-user data must never be included in AI context.

## Payment Security

- Never trust browser payment success redirects.
- Verify all payment outcomes through provider webhooks/API.
- Webhook handlers must verify signatures, enforce idempotency, and map provider events to configured packages/features server-side.
- Do not allow client-submitted package IDs to grant access.
