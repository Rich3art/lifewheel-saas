# Billing Architecture

## Design

Use provider-neutral billing core with payment providers implemented as plugins.

Target providers:

- Stripe
- PayPal
- Paystack
- Whop

## Core Billing

Core owns:

- normalized subscriptions
- package mappings
- billing status
- package entitlements
- payment event logs
- provider settings registry
- webhook dispatch

Core does not hard-code provider-specific logic beyond the provider interface.

## Provider Interface

Payment plugins should implement:

- `createCheckout()`
- `createSubscription()`
- `cancelSubscription()`
- `changeSubscription()`
- `getSubscription()`
- `verifyWebhook()`
- `handleWebhook()`
- `refund()` where supported
- `customerPortal()` where supported

## Webhook Processing

Webhook processing must be:

- signature verified
- idempotent
- replay-safe
- logged
- mapped to configured package/provider settings
- independent from browser redirects

Do not allow request manipulation to upgrade entitlements.

## Package Landing Pages

Package pages should be editable CMS pages tied to package data.

Feature comparison should derive from actual package features where practical.
