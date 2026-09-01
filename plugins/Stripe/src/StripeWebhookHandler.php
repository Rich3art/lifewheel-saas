<?php

namespace LifeWheel\Plugins\Stripe;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Services\Billing\BillingManager;
use App\Services\Billing\BillingSubscriptionData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class StripeWebhookHandler
{
    public function __construct(private BillingManager $billing)
    {
    }

    public function handle(array $event): void
    {
        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');

        if ($eventId === '' || $eventType === '') {
            throw new RuntimeException('Stripe webhook event is missing id or type.');
        }

        if (DB::table('subscription_events')->where('provider_key', 'stripe')->where('external_event_id', $eventId)->exists()) {
            return;
        }

        match ($eventType) {
            'checkout.session.completed' => $this->checkoutCompleted($event, $eventId),
            'customer.subscription.updated', 'customer.subscription.created' => $this->subscriptionUpdated($event, $eventId),
            'customer.subscription.deleted' => $this->subscriptionDeleted($event, $eventId),
            'invoice.paid', 'invoice.payment_failed' => $this->invoiceEvent($event, $eventId),
            default => $this->recordIgnoredEvent($eventId, $eventType),
        };
    }

    private function checkoutCompleted(array $event, string $eventId): void
    {
        $object = $event['data']['object'] ?? [];
        $metadata = $object['metadata'] ?? [];
        $userId = (int) ($metadata['user_id'] ?? $object['client_reference_id'] ?? 0);
        $packageId = (int) ($metadata['package_id'] ?? 0);
        $subscriptionId = isset($object['subscription']) ? (string) $object['subscription'] : null;

        if ($userId <= 0 || $packageId <= 0) {
            throw new RuntimeException('Stripe checkout session is missing LifeWheel metadata.');
        }

        $user = User::query()->findOrFail($userId);
        $provider = PaymentProvider::query()->where('key', 'stripe')->first();
        $mapping = $this->mappingFor($packageId, $object['currency'] ?? null, $object['amount_total'] ?? null);

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $packageId,
            providerKey: 'stripe',
            paymentProviderId: $provider?->id,
            externalCustomerId: isset($object['customer']) ? (string) $object['customer'] : null,
            externalSubscriptionId: $subscriptionId ?: 'stripe-session-'.$object['id'],
            status: 'active',
            amountCents: (int) ($object['amount_total'] ?? $mapping?->amount_cents ?? 0),
            currency: strtoupper((string) ($object['currency'] ?? $mapping?->currency ?? 'USD')),
            billingInterval: $mapping?->package?->billing_interval ?? 'monthly',
            currentPeriodStartsAt: now(),
            currentPeriodEndsAt: null,
            metadata: ['stripe_session_id' => $object['id'] ?? null],
        ));

        $this->billing->recordEvent($subscription, 'stripe.checkout.session.completed', $eventId, $this->summary($event));
    }

    private function subscriptionUpdated(array $event, string $eventId): void
    {
        $object = $event['data']['object'] ?? [];
        $metadata = $object['metadata'] ?? [];
        $userId = (int) ($metadata['user_id'] ?? 0);
        $packageId = (int) ($metadata['package_id'] ?? 0);
        $provider = PaymentProvider::query()->where('key', 'stripe')->first();

        if ($userId <= 0 || $packageId <= 0) {
            $this->recordIgnoredEvent($eventId, (string) ($event['type'] ?? 'stripe.subscription.updated'));

            return;
        }

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $userId,
            packageId: $packageId,
            providerKey: 'stripe',
            paymentProviderId: $provider?->id,
            externalCustomerId: isset($object['customer']) ? (string) $object['customer'] : null,
            externalSubscriptionId: (string) ($object['id'] ?? ''),
            status: $this->normalizeStatus((string) ($object['status'] ?? 'active')),
            amountCents: (int) ($object['items']['data'][0]['price']['unit_amount'] ?? 0),
            currency: strtoupper((string) ($object['currency'] ?? 'USD')),
            billingInterval: $this->intervalFromSubscription($object),
            currentPeriodStartsAt: $this->timestamp($object['current_period_start'] ?? null),
            currentPeriodEndsAt: $this->timestamp($object['current_period_end'] ?? null),
            cancelsAt: $this->timestamp($object['cancel_at'] ?? null),
            metadata: ['stripe_status' => $object['status'] ?? null],
        ));

        $this->billing->recordEvent($subscription, 'stripe.subscription.updated', $eventId, $this->summary($event));
    }

    private function subscriptionDeleted(array $event, string $eventId): void
    {
        $object = $event['data']['object'] ?? [];
        $subscription = \App\Models\Subscription::query()
            ->where('provider_key', 'stripe')
            ->where('external_subscription_id', (string) ($object['id'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, 'stripe.subscription.deleted');

            return;
        }

        $this->billing->cancelSubscription($subscription, null, true);
        $this->billing->recordEvent($subscription, 'stripe.subscription.deleted', $eventId, $this->summary($event));
    }

    private function invoiceEvent(array $event, string $eventId): void
    {
        $object = $event['data']['object'] ?? [];
        $subscription = \App\Models\Subscription::query()
            ->where('provider_key', 'stripe')
            ->where('external_subscription_id', (string) ($object['subscription'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, (string) ($event['type'] ?? 'stripe.invoice'));

            return;
        }

        $status = ($event['type'] ?? '') === 'invoice.paid' ? 'paid' : 'failed';
        $this->billing->recordInvoice(
            $subscription,
            $status,
            (int) ($object['amount_paid'] ?? $object['amount_due'] ?? 0),
            (string) ($object['currency'] ?? $subscription->currency),
            isset($object['id']) ? (string) $object['id'] : null,
            $this->summary($event),
        );
        $this->billing->recordEvent($subscription, 'stripe.'.(string) ($event['type'] ?? 'invoice'), $eventId, $this->summary($event));
    }

    private function recordIgnoredEvent(string $eventId, string $eventType): void
    {
        DB::table('subscription_events')->insert([
            'provider_key' => 'stripe',
            'event_type' => 'stripe.ignored.'.$eventType,
            'external_event_id' => $eventId,
            'payload_summary' => json_encode(['ignored' => true]),
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mappingFor(int $packageId, mixed $currency, mixed $amount): ?PackageProviderMapping
    {
        return PackageProviderMapping::query()
            ->with('package')
            ->where('package_id', $packageId)
            ->whereHas('provider', fn ($query) => $query->where('key', 'stripe'))
            ->where('active', true)
            ->when($currency, fn ($query) => $query->where(function ($query) use ($currency): void {
                $query->whereNull('currency')->orWhere('currency', strtoupper((string) $currency));
            }))
            ->when($amount !== null, fn ($query) => $query->where(function ($query) use ($amount): void {
                $query->whereNull('amount_cents')->orWhere('amount_cents', (int) $amount);
            }))
            ->first();
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'active', 'trialing' => 'active',
            'past_due', 'unpaid' => 'past_due',
            'canceled' => 'cancelled',
            default => $status,
        };
    }

    private function intervalFromSubscription(array $object): string
    {
        $interval = (string) ($object['items']['data'][0]['price']['recurring']['interval'] ?? 'month');

        return match ($interval) {
            'year' => 'yearly',
            'week', 'month' => 'monthly',
            default => 'monthly',
        };
    }

    private function timestamp(mixed $value): ?Carbon
    {
        return is_numeric($value) ? Carbon::createFromTimestamp((int) $value) : null;
    }

    private function summary(array $event): array
    {
        return [
            'id' => $event['id'] ?? null,
            'type' => $event['type'] ?? null,
            'livemode' => $event['livemode'] ?? null,
        ];
    }
}
