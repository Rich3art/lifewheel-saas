# Phase 22 — Stripe Plugin

Phase 22 adds the first payment-provider plugin. Stripe remains isolated from Core; Core owns normalized billing state and the Stripe plugin translates Stripe checkout/webhook events into Billing Core operations.

## Scope

- First-party `stripe` plugin manifest, lifecycle entrypoint, routes, services, and tests.
- Stripe checkout payload preparation for mapped packages.
- Verified Stripe webhook route.
- Manual HMAC verification of the `Stripe-Signature` header against the raw request body.
- Handling for checkout completion, subscription created/updated/deleted, invoice paid, and invoice payment failed events.
- Idempotent webhook processing through Billing Core subscription events.

## Architecture

The plugin does not add its own tables. It uses Billing Core tables:

- `payment_providers`
- `package_provider_mappings`
- `subscriptions`
- `subscription_events`
- `billing_invoices`
- `user_packages`

The plugin calls `BillingManager` to activate/cancel subscriptions, record invoices, and record webhook events.

## Webhook Security

Stripe signs webhook events using the `Stripe-Signature` header. The plugin verifies:

- endpoint secret exists
- signature header exists
- timestamp is within tolerance
- HMAC SHA-256 signature matches the raw request body

This follows Stripe's documented requirement that webhook verification use the raw body, signature header, and endpoint secret.

Source: https://docs.stripe.com/webhooks/signature

## Security Review

- Webhook route requires the Stripe provider to be enabled.
- Invalid signatures are rejected before payload processing.
- Duplicate external event IDs are ignored.
- Checkout preparation requires an enabled Stripe provider, active mapping, and configured Stripe price ID.
- Provider secrets are read server-side only from encrypted provider settings or environment variables.
- Browser success redirects are not trusted to grant entitlements.

## Limitations

- This phase prepares checkout payloads but does not call Stripe's API to create hosted checkout sessions. That can be added after deciding whether to use the official Stripe PHP SDK or a small cURL client for cPanel packaging.
- Subscription update events need LifeWheel metadata on the Stripe subscription to map directly back to package/user.
- Full live Stripe sandbox testing is deferred until provider credentials are supplied.
