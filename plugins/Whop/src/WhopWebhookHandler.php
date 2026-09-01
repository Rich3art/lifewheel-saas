<?php

namespace LifeWheel\Plugins\Whop;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingManager;
use App\Services\Billing\BillingSubscriptionData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class WhopWebhookHandler
{
    public function __construct(private BillingManager $billing)
    {
    }

    public function handle(array $event, string $deliveryId): void
    {
        $eventType = (string) ($event['type'] ?? '');
        $eventId = $deliveryId ?: (string) ($event['id'] ?? '');

        if ($eventId === '' || $eventType === '') {
            throw new RuntimeException('Whop webhook event is missing id or type.');
        }

        if (DB::table('subscription_events')->where('provider_key', 'whop')->where('external_event_id', $eventId)->exists()) {
            return;
        }

        match ($eventType) {
            'payment.succeeded' => $this->paymentSucceeded($event, $eventId),
            'membership.activated', 'membership.cancel_at_period_end_changed' => $this->membershipActivated($event, $eventId),
            'membership.deactivated' => $this->membershipDeactivated($event, $eventId),
            'invoice.paid', 'invoice.past_due', 'invoice.voided', 'invoice.marked_uncollectible' => $this->invoiceEvent($event, $eventId),
            default => $this->recordIgnoredEvent($eventId, $eventType),
        };
    }

    private function paymentSucceeded(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        [$userId, $packageId] = $this->idsFromData($data);

        if ($userId <= 0 || $packageId <= 0) {
            $this->recordIgnoredEvent($eventId, 'payment.succeeded');

            return;
        }

        $user = User::query()->findOrFail($userId);
        $provider = PaymentProvider::query()->where('key', 'whop')->first();
        $mapping = $this->mappingFor($packageId, $data);
        $subscriptionId = (string) ($data['membership_id'] ?? $data['subscription_id'] ?? $data['id'] ?? $eventId);
        $amount = (int) ($data['amount'] ?? $data['amount_cents'] ?? $mapping?->amount_cents ?? 0);
        $currency = strtoupper((string) ($data['currency'] ?? $mapping?->currency ?? 'USD'));

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $packageId,
            providerKey: 'whop',
            paymentProviderId: $provider?->id,
            externalCustomerId: (string) ($data['member_id'] ?? $data['user_id'] ?? ''),
            externalSubscriptionId: $subscriptionId,
            status: 'active',
            amountCents: $amount,
            currency: $currency,
            billingInterval: $mapping?->package?->billing_interval ?? 'monthly',
            currentPeriodStartsAt: now(),
            metadata: ['whop_payment_id' => $data['id'] ?? null],
        ));

        $this->billing->recordInvoice(
            $subscription,
            'paid',
            $amount,
            $currency,
            isset($data['invoice_id']) ? (string) $data['invoice_id'] : (string) ($data['id'] ?? ''),
            $this->summary($event),
        );
        $this->billing->recordEvent($subscription, 'whop.payment.succeeded', $eventId, $this->summary($event));
    }

    private function membershipActivated(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        [$userId, $packageId] = $this->idsFromData($data);

        if ($userId <= 0 || $packageId <= 0) {
            $this->recordIgnoredEvent($eventId, (string) ($event['type'] ?? 'membership'));

            return;
        }

        $provider = PaymentProvider::query()->where('key', 'whop')->first();
        $mapping = $this->mappingFor($packageId, $data);
        $membershipId = (string) ($data['id'] ?? $data['membership_id'] ?? $eventId);

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $userId,
            packageId: $packageId,
            providerKey: 'whop',
            paymentProviderId: $provider?->id,
            externalCustomerId: (string) ($data['member_id'] ?? $data['user_id'] ?? ''),
            externalSubscriptionId: $membershipId,
            status: $this->normalizeStatus((string) ($data['status'] ?? 'active')),
            amountCents: (int) ($data['amount'] ?? $data['amount_cents'] ?? $mapping?->amount_cents ?? 0),
            currency: strtoupper((string) ($data['currency'] ?? $mapping?->currency ?? 'USD')),
            billingInterval: $mapping?->package?->billing_interval ?? 'monthly',
            currentPeriodStartsAt: $this->time($data['created_at'] ?? $data['valid_from'] ?? null) ?? now(),
            currentPeriodEndsAt: $this->time($data['valid_until'] ?? $data['expires_at'] ?? $data['renewal_period_end'] ?? null),
            cancelsAt: $this->time($data['cancel_at'] ?? null),
            metadata: ['whop_status' => $data['status'] ?? null],
        ));

        $this->billing->recordEvent($subscription, 'whop.'.(string) $event['type'], $eventId, $this->summary($event));
    }

    private function membershipDeactivated(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        $subscription = Subscription::query()
            ->where('provider_key', 'whop')
            ->where('external_subscription_id', (string) ($data['id'] ?? $data['membership_id'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, 'membership.deactivated');

            return;
        }

        $this->billing->cancelSubscription($subscription, null, true);
        $this->billing->recordEvent($subscription, 'whop.membership.deactivated', $eventId, $this->summary($event));
    }

    private function invoiceEvent(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        $subscription = Subscription::query()
            ->where('provider_key', 'whop')
            ->where('external_subscription_id', (string) ($data['membership_id'] ?? $data['subscription_id'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, (string) ($event['type'] ?? 'invoice'));

            return;
        }

        $status = match ($event['type'] ?? '') {
            'invoice.paid' => 'paid',
            'invoice.past_due' => 'past_due',
            'invoice.voided' => 'voided',
            'invoice.marked_uncollectible' => 'uncollectible',
            default => 'pending',
        };

        $this->billing->recordInvoice(
            $subscription,
            $status,
            (int) ($data['amount_due'] ?? $data['amount'] ?? $data['amount_cents'] ?? 0),
            (string) ($data['currency'] ?? $subscription->currency),
            isset($data['id']) ? (string) $data['id'] : null,
            $this->summary($event),
        );
        $this->billing->recordEvent($subscription, 'whop.'.(string) ($event['type'] ?? 'invoice'), $eventId, $this->summary($event));
    }

    private function idsFromData(array $data): array
    {
        $metadata = $data['metadata'] ?? [];
        $userId = (int) ($metadata['user_id'] ?? $data['metadata_user_id'] ?? 0);
        $packageId = (int) ($metadata['package_id'] ?? $data['metadata_package_id'] ?? 0);

        return [$userId, $packageId];
    }

    private function mappingFor(int $packageId, array $data): ?PackageProviderMapping
    {
        return PackageProviderMapping::query()
            ->with('package')
            ->where('package_id', $packageId)
            ->whereHas('provider', fn ($query) => $query->where('key', 'whop'))
            ->where('active', true)
            ->when(isset($data['plan_id']), fn ($query) => $query->where(function ($query) use ($data): void {
                $query->whereNull('external_price_id')->orWhere('external_price_id', (string) $data['plan_id']);
            }))
            ->when(isset($data['product_id']), fn ($query) => $query->where(function ($query) use ($data): void {
                $query->whereNull('external_product_id')->orWhere('external_product_id', (string) $data['product_id']);
            }))
            ->first();
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'active', 'valid', 'trialing' => 'active',
            'past_due', 'unpaid' => 'past_due',
            'cancelled', 'canceled', 'inactive', 'invalid', 'expired' => 'cancelled',
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
            'provider_key' => 'whop',
            'event_type' => 'whop.ignored.'.$eventType,
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
            'type' => $event['type'] ?? null,
            'api_version_date' => $event['api_version_date'] ?? null,
        ];
    }
}
