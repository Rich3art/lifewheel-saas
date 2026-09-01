<?php

namespace LifeWheel\Plugins\PayPal;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingManager;
use App\Services\Billing\BillingSubscriptionData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class PayPalWebhookHandler
{
    public function __construct(private BillingManager $billing)
    {
    }

    public function handle(array $event): void
    {
        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['event_type'] ?? '');

        if ($eventId === '' || $eventType === '') {
            throw new RuntimeException('PayPal webhook event is missing id or event_type.');
        }

        if (DB::table('subscription_events')->where('provider_key', 'paypal')->where('external_event_id', $eventId)->exists()) {
            return;
        }

        match ($eventType) {
            'CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED' => $this->paymentCompleted($event, $eventId, $eventType),
            'BILLING.SUBSCRIPTION.ACTIVATED', 'BILLING.SUBSCRIPTION.UPDATED' => $this->subscriptionUpdated($event, $eventId, $eventType),
            'BILLING.SUBSCRIPTION.CANCELLED', 'BILLING.SUBSCRIPTION.EXPIRED', 'BILLING.SUBSCRIPTION.SUSPENDED' => $this->subscriptionEnded($event, $eventId, $eventType),
            default => $this->recordIgnoredEvent($eventId, $eventType),
        };
    }

    private function paymentCompleted(array $event, string $eventId, string $eventType): void
    {
        $resource = $event['resource'] ?? [];
        [$userId, $packageId] = $this->idsFromResource($resource);

        if ($userId <= 0 || $packageId <= 0) {
            $this->recordIgnoredEvent($eventId, $eventType);

            return;
        }

        $user = User::query()->findOrFail($userId);
        $provider = PaymentProvider::query()->where('key', 'paypal')->first();
        $mapping = $this->mappingFor($packageId);
        $amount = (int) round(((float) ($resource['amount']['value'] ?? $resource['purchase_units'][0]['amount']['value'] ?? 0)) * 100);
        $currency = strtoupper((string) ($resource['amount']['currency_code'] ?? $resource['purchase_units'][0]['amount']['currency_code'] ?? $mapping?->currency ?? 'USD'));

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $packageId,
            providerKey: 'paypal',
            paymentProviderId: $provider?->id,
            externalCustomerId: data_get($resource, 'payer.payer_id'),
            externalSubscriptionId: (string) ($resource['billing_agreement_id'] ?? $resource['supplementary_data']['related_ids']['order_id'] ?? $resource['id'] ?? 'paypal-'.$eventId),
            status: 'active',
            amountCents: $amount ?: (int) ($mapping?->amount_cents ?? 0),
            currency: $currency,
            billingInterval: $mapping?->package?->billing_interval ?? 'monthly',
            currentPeriodStartsAt: now(),
            metadata: ['paypal_resource_id' => $resource['id'] ?? null],
        ));

        $this->billing->recordEvent($subscription, 'paypal.'.$eventType, $eventId, $this->summary($event));
    }

    private function subscriptionUpdated(array $event, string $eventId, string $eventType): void
    {
        $resource = $event['resource'] ?? [];
        [$userId, $packageId] = $this->idsFromResource($resource);

        if ($userId <= 0 || $packageId <= 0) {
            $this->recordIgnoredEvent($eventId, $eventType);

            return;
        }

        $provider = PaymentProvider::query()->where('key', 'paypal')->first();
        $mapping = $this->mappingFor($packageId);

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $userId,
            packageId: $packageId,
            providerKey: 'paypal',
            paymentProviderId: $provider?->id,
            externalCustomerId: data_get($resource, 'subscriber.payer_id'),
            externalSubscriptionId: (string) ($resource['id'] ?? ''),
            status: $this->normalizeStatus((string) ($resource['status'] ?? 'ACTIVE')),
            amountCents: (int) ($mapping?->amount_cents ?? 0),
            currency: strtoupper((string) ($mapping?->currency ?? 'USD')),
            billingInterval: $mapping?->package?->billing_interval ?? 'monthly',
            currentPeriodStartsAt: $this->time($resource['start_time'] ?? null),
            currentPeriodEndsAt: $this->time($resource['billing_info']['next_billing_time'] ?? null),
            metadata: ['paypal_status' => $resource['status'] ?? null],
        ));

        $this->billing->recordEvent($subscription, 'paypal.'.$eventType, $eventId, $this->summary($event));
    }

    private function subscriptionEnded(array $event, string $eventId, string $eventType): void
    {
        $resource = $event['resource'] ?? [];
        $subscription = Subscription::query()
            ->where('provider_key', 'paypal')
            ->where('external_subscription_id', (string) ($resource['id'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, $eventType);

            return;
        }

        $this->billing->cancelSubscription($subscription, null, true);
        $this->billing->recordEvent($subscription, 'paypal.'.$eventType, $eventId, $this->summary($event));
    }

    private function idsFromResource(array $resource): array
    {
        $customId = (string) ($resource['custom_id'] ?? data_get($resource, 'purchase_units.0.custom_id', ''));
        preg_match('/user:(\d+)\|package:(\d+)/', $customId, $matches);

        return [(int) ($matches[1] ?? 0), (int) ($matches[2] ?? 0)];
    }

    private function mappingFor(int $packageId): ?PackageProviderMapping
    {
        return PackageProviderMapping::query()
            ->with('package')
            ->where('package_id', $packageId)
            ->whereHas('provider', fn ($query) => $query->where('key', 'paypal'))
            ->where('active', true)
            ->first();
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ACTIVE', 'APPROVAL_PENDING', 'APPROVED' => 'active',
            'SUSPENDED' => 'past_due',
            'CANCELLED', 'EXPIRED' => 'cancelled',
            default => strtolower($status),
        };
    }

    private function time(mixed $value): ?Carbon
    {
        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }

    private function recordIgnoredEvent(string $eventId, string $eventType): void
    {
        DB::table('subscription_events')->insert([
            'provider_key' => 'paypal',
            'event_type' => 'paypal.ignored.'.$eventType,
            'external_event_id' => $eventId,
            'payload_summary' => json_encode(['ignored' => true]),
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function summary(array $event): array
    {
        return [
            'id' => $event['id'] ?? null,
            'event_type' => $event['event_type'] ?? null,
            'create_time' => $event['create_time'] ?? null,
        ];
    }
}
