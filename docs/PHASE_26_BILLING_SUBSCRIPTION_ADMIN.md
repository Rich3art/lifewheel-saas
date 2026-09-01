# Phase 26 — Billing / Subscription Admin

Phase 26 builds on the provider-neutral Billing Core and payment provider plugins by adding the administration and member-facing selection layer.

## Implemented Scope

- Super Admin billing metrics:
  - total subscriptions
  - active/trialing subscriptions
  - past-due subscriptions
  - estimated monthly recurring revenue
  - estimated annual run rate
- Editable package-provider mappings:
  - external product ID
  - external price ID
  - amount
  - currency
  - active/inactive state
- Member billing area:
  - authenticated user subscription history
  - authenticated user invoice history
  - public active package list
  - checkout provider buttons for enabled active mappings
- Checkout routing:
  - Core validates mapping, package, and provider state.
  - Core hands off to the enabled provider plugin route.
  - Provider plugins still validate direct URL access.
- Admin visibility:
  - recent subscriptions
  - recent invoices
  - recent billing events

## Architecture Decisions

- Billing administration remains Core because packages, subscriptions, invoices, entitlements, and commercial metrics are platform infrastructure.
- Provider-specific checkout construction remains in payment plugins.
- Core uses `App\Services\Billing\CheckoutRouter` as the narrow handoff layer from package/provider selection to provider plugin routes.
- Member package availability is derived from active public packages and active mappings on enabled providers; browser-submitted mapping IDs are revalidated server-side.
- Metrics are calculated from stored normalized subscription records only. No fake dashboard values are rendered.

## Security Notes

- Super Admin billing routes continue to require `admin.billing.manage`.
- Member billing routes require authenticated, verified, 2FA-satisfied users.
- Member subscription and invoice queries are scoped by `user_id`.
- Package checkout denies inactive mappings, disabled providers, inactive packages, and private packages.
- Payment plugin checkout services also deny inactive/private packages to protect direct plugin URLs.
- Browser redirects or provider return URLs do not grant subscription access; subscription state is still updated through manual admin activation or verified webhooks.

## Known Limitations

- Provider plugins currently prepare checkout payloads/configuration but do not call live hosted checkout APIs with real credentials.
- MRR and ARR are estimated from active/trialing normalized subscriptions and do not yet account for refunds, discounts, taxes, churn cohorts, or currency conversion.
- Member billing history is read-only; upgrade/downgrade/cancel self-service workflows remain deferred.
- Billing history does not yet include downloadable invoice documents.

## Validation Target

Relevant validation for this phase:

- PHP syntax checks across Core, tests, and plugins.
- Frontend asset build.
- Git whitespace check.
- Laravel billing feature tests where Composer dependencies are installed.
