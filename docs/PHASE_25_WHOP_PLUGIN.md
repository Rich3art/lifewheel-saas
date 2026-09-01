# Phase 25 — Whop Plugin

Phase 25 adds the Whop payment-provider plugin. Whop remains isolated from Core and translates verified Standard Webhooks payment, membership, and invoice events into Billing Core records.

## Scope

- First-party `whop` plugin manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- Whop checkout configuration payload preparation for active package mappings.
- Verified Whop webhook endpoint.
- Standard Webhooks HMAC-SHA256 verification using `webhook-id`, `webhook-timestamp`, `webhook-signature`, webhook secret, and raw request body.
- Handling for payment success, membership activation/deactivation, membership cancel-at-period-end changes, invoice events, and ignored events.
- Billing Core integration for subscriptions, invoices, entitlement sync, and idempotent events.

## Architecture

The plugin owns no database tables. It uses Billing Core:

- `payment_providers`
- `package_provider_mappings`
- `subscriptions`
- `subscription_events`
- `billing_invoices`
- `user_packages`

Provider-specific Whop payloads are translated into `BillingManager` calls. Browser redirects never grant entitlement.

## Webhook Security

Whop uses Standard Webhooks. The plugin verifies:

- `webhook-id` exists
- `webhook-timestamp` exists and is within five minutes
- `webhook-signature` exists and contains a `v1` signature
- HMAC-SHA256 over `{webhook-id}.{webhook-timestamp}.{raw body}` matches the base64 signature

Sources:

- https://docs.whop.com/developer/guides/webhooks
- https://docs.whop.com/api-reference/payments/payment-succeeded
- https://docs.whop.com/api-reference/memberships/membership-activated

## Security Review

- Webhook route requires the Whop provider to be enabled.
- Invalid or stale signatures are rejected before event parsing.
- Duplicate webhook delivery IDs are idempotent.
- Checkout payload route denies disabled or inactive mappings.
- Provider secrets remain server-side in encrypted provider settings or environment variables.
- Activation requires LifeWheel metadata mapping Whop events to internal user/package records.

## Limitations

- Checkout route prepares a Whop checkout configuration payload but does not call Whop APIs yet.
- Live Whop testing requires real credentials and webhook secret.
- Current fulfillment requires `user_id` and `package_id` metadata on Whop payment or membership events.
