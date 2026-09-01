# Phase 23 — PayPal Plugin

Phase 23 adds the PayPal payment-provider plugin. PayPal remains isolated from Core and translates PayPal order/subscription/payment events into Billing Core records.

## Scope

- First-party `paypal` plugin manifest, lifecycle entrypoint, routes, services, documentation, and tests.
- PayPal checkout order payload preparation for active provider package mappings.
- Verified PayPal webhook endpoint.
- Local cryptographic webhook verification using raw payload CRC32, webhook ID, PayPal transmission headers, certificate URL, and RSA signature.
- Handling for payment capture/order approval, billing subscription activation/update/end events, and ignored event logging.

## Architecture

The plugin does not own database tables. It uses Billing Core:

- `payment_providers`
- `package_provider_mappings`
- `subscriptions`
- `subscription_events`
- `billing_invoices`
- `user_packages`

The plugin calls `BillingManager`; it does not directly grant feature access.

## Webhook Security

PayPal webhook verification uses:

- `PAYPAL-TRANSMISSION-ID`
- `PAYPAL-TRANSMISSION-TIME`
- `PAYPAL-TRANSMISSION-SIG`
- `PAYPAL-CERT-URL`
- `PAYPAL-AUTH-ALGO`
- configured webhook ID
- original raw request body

The plugin restricts certificate URLs to HTTPS PayPal hosts and verifies RSA signatures locally. This avoids adding a Composer SDK dependency during the cPanel-focused phase.

Sources:

- https://developer.paypal.com/api/rest/webhooks/rest
- https://developer.paypal.com/api/webhooks/v1/verify-webhook-signature-post

## Security Review

- Webhook route requires the PayPal provider to be enabled.
- Invalid signatures are rejected before payload processing.
- Duplicate event IDs are idempotent.
- Checkout payload preparation requires an enabled PayPal provider and active mapping.
- Provider secrets remain server-side in encrypted provider settings or environment variables.
- Browser return URLs do not activate subscriptions.

## Limitations

- Checkout route prepares the order payload but does not call PayPal APIs yet.
- Live sandbox testing requires PayPal credentials and configured webhook ID.
- Subscription webhooks require metadata/custom IDs that map PayPal resources back to LifeWheel user/package records.
