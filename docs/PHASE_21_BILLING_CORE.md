# Phase 21 — Billing Core

Phase 21 adds provider-neutral billing infrastructure. It does not implement Stripe, PayPal, Paystack, or Whop checkout/webhooks yet. Those remain separate provider plugin phases.

## Scope

- Core billing database tables and Eloquent models.
- Provider-neutral subscription service.
- Payment provider registry.
- Package-to-provider mapping records.
- Normalized subscriptions, subscription events, and billing invoices.
- Super Admin billing console.
- Member billing history page.
- Billing permission and default provider seed data.

## Architecture

Core owns normalized commercial state:

- `payment_providers`
- `package_provider_mappings`
- `subscriptions`
- `subscription_events`
- `billing_invoices`

Payment provider plugins should translate provider-specific payloads into calls to `BillingManager`. Provider plugins must not directly mutate package entitlements except through the Core billing service.

## Subscription Engine

`BillingManager` supports:

- activating/upserting a normalized subscription
- syncing the user's active package entitlement
- immediate or period-end cancellation
- invoice recording
- idempotent subscription event recording
- audit logging for admin actions

The existing `user_packages` table remains the entitlement bridge used by `EntitlementService`.

## Security Review

- Admin billing routes require `admin.billing.manage`.
- Member billing routes require authenticated, verified, 2FA-passed users.
- Member billing queries are scoped by authenticated `user_id`.
- Provider settings are cast as encrypted arrays.
- Browser redirects are not trusted for subscription activation.
- Provider secrets and webhook verification are intentionally deferred to provider plugins.

## Limitations

- No checkout provider is implemented in this phase.
- No webhook endpoints are implemented in this phase.
- The billing console supports normalized settings and manual activation only.
- Refunds, proration, customer portals, and payment retry logic are deferred.
