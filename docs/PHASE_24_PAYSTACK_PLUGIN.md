# Phase 24 — Paystack Plugin

Phase 24 adds the Paystack payment-provider plugin. Paystack remains isolated from Core and translates verified Paystack transaction/subscription events into Billing Core records.

## Scope

- First-party `paystack` plugin manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- Paystack transaction initialization payload preparation for active package mappings.
- Verified Paystack webhook endpoint.
- HMAC-SHA512 verification of `x-paystack-signature` against the raw request body and Paystack secret key.
- Handling for charge success, subscription status events, and invoice events.
- Billing Core integration for subscriptions, invoices, entitlement sync, and idempotent events.

## Architecture

The plugin owns no database tables. It uses Billing Core:

- `payment_providers`
- `package_provider_mappings`
- `subscriptions`
- `subscription_events`
- `billing_invoices`
- `user_packages`

Provider-specific payloads are translated into `BillingManager` calls.

## Webhook Security

Paystack signs webhook requests using the `x-paystack-signature` header. The signature is HMAC-SHA512 over the raw request body using the Paystack secret key.

The plugin rejects webhook requests when:

- Paystack provider is disabled
- secret key is missing
- signature header is missing
- signature does not match the raw request body

Source: https://paystack.com/docs/payments/webhooks/

## Security Review

- Webhook route requires the Paystack provider to be enabled.
- Invalid signatures are rejected before event parsing.
- Duplicate external event IDs are idempotent.
- Checkout payload route denies disabled or inactive mappings.
- Provider secrets remain server-side in encrypted provider settings or environment variables.
- Browser callback URLs do not activate subscriptions.

## Limitations

- Checkout route prepares the transaction payload but does not call Paystack's transaction initialization API yet.
- Live Paystack testing requires real sandbox/live credentials.
- Activation requires `user_id` and `package_id` metadata in Paystack events.
