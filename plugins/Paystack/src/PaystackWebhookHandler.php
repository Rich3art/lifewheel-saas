<?php

namespace LifeWheel\Plugins\Paystack;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingManager;
use App\Services\Billing\BillingSubscriptionData;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class PaystackWebhookHandler
{
    public function __construct(private BillingManager $billing)
    {
    }

    public function handle(array $event): void
    {
        $eventType = (string) ($event['event'] ?? '');
        $data = $event['data'] ?? [];
        $eventId = $this->eventId($eventType, $data);

        if ($eventType === '' || $eventId === '') {
            throw new RuntimeException('Paystack webhook event is missing event or id.');
        }

        if (DB::table('subscription_events')->where('provider_key', 'paystack')->where('external_event_id', $eventId)->exists()) {
            return;
        }

        match ($eventType) {
            'charge.success' => $this->chargeSuccess($event, $eventId),
            'subscription.create', 'subscription.enable', 'subscription.not_renew', 'subscription.disable' => $this->subscriptionEvent($event, $eventId),
            'invoice.create', 'invoice.payment_failed', 'invoice.update' => $this->invoiceEvent($event, $eventId),
            default => $this->recordIgnoredEvent($eventId, $eventType),
        };
    }

    private function chargeSuccess(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        $metadata = $data['metadata'] ?? [];
        $userId = (int) ($metadata['user_id'] ?? 0);
        $packageId = (int) ($metadata['package_id'] ?? 0);

        if ($userId <= 0 || $packageId <= 0) {
            $this->recordIgnoredEvent($eventId, 'charge.success');

            return;
        }

        $user = User::query()->findOrFail($userId);
        $provider = PaymentProvider::query()->where('key', 'paystack')->first();
        $mapping = $this->mappingFor($packageId, $data['currency'] ?? null, $data['amount'] ?? null);
        $subscriptionCode = (string) ($data['subscription']['subscription_code'] ?? $data['subscription_code'] ?? $data['reference'] ?? $eventId);

        $subscription = $this->billing->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $packageId,
            providerKey: 'paystack',
            paymentProviderId: $provider?->id,
            externalCustomerId: (string) ($data['customer']['customer_code'] ?? ''),
            externalSubscriptionId: $subscriptionCode,
            status: 'active',
            amountCents: (int) ($data['amount'] ?? $mapping?->amount_cents ?? 0),
            currency: strtoupper((string) ($data['currency'] ?? $mapping?->currency ?? 'USD')),
            billingInterval: $mapping?->package?->billing_interval ?? 'monthly',
            currentPeriodStartsAt: now(),
            metadata: ['paystack_reference' => $data['reference'] ?? null],
        ));

        $this->billing->recordInvoice(
            $subscription,
            'paid',
            (int) ($data['amount'] ?? 0),
            (string) ($data['currency'] ?? $subscription->currency),
            isset($data['id']) ? 'paystack-charge-'.$data['id'] : (string) ($data['reference'] ?? ''),
            $this->summary($event),
        );
        $this->billing->recordEvent($subscription, 'paystack.charge.success', $eventId, $this->summary($event));
    }

    private function subscriptionEvent(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        $subscription = Subscription::query()
            ->where('provider_key', 'paystack')
            ->where('external_subscription_id', (string) ($data['subscription_code'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, (string) ($event['event'] ?? 'subscription'));

            return;
        }

        if (in_array($event['event'], ['subscription.not_renew', 'subscription.disable'], true)) {
            $this->billing->cancelSubscription($subscription, null, $event['event'] === 'subscription.disable');
            $this->billing->recordEvent($subscription, 'paystack.'.$event['event'], $eventId, $this->summary($event));

            return;
        }

        $subscription->forceFill(['status' => 'active', 'metadata' => ['paystack_status' => $event['event']]])->save();
        $this->billing->recordEvent($subscription, 'paystack.'.$event['event'], $eventId, $this->summary($event));
    }

    private function invoiceEvent(array $event, string $eventId): void
    {
        $data = $event['data'] ?? [];
        $subscription = Subscription::query()
            ->where('provider_key', 'paystack')
            ->where('external_subscription_id', (string) ($data['subscription']['subscription_code'] ?? $data['subscription_code'] ?? ''))
            ->first();

        if (! $subscription) {
            $this->recordIgnoredEvent($eventId, (string) ($event['event'] ?? 'invoice'));

            return;
        }

        $status = ($event['event'] ?? '') === 'invoice.payment_failed' ? 'failed' : strtolower((string) ($data['status'] ?? 'pending'));
        $this->billing->recordInvoice(
            $subscription,
            $status,
            (int) ($data['amount'] ?? $data['amount_due'] ?? 0),
            (string) ($data['currency'] ?? $subscription->currency),
            isset($data['id']) ? 'paystack-invoice-'.$data['id'] : null,
            $this->summary($event),
        );
        $this->billing->recordEvent($subscription, 'paystack.'.(string) ($event['event'] ?? 'invoice'), $eventId, $this->summary($event));
    }

    private function mappingFor(int $packageId, mixed $currency, mixed $amount): ?PackageProviderMapping
    {
        return PackageProviderMapping::query()
            ->with('package')
            ->where('package_id', $packageId)
            ->whereHas('provider', fn ($query) => $query->where('key', 'paystack'))
            ->where('active', true)
            ->when($currency, fn ($query) => $query->where(function ($query) use ($currency): void {
                $query->whereNull('currency')->orWhere('currency', strtoupper((string) $currency));
            }))
            ->when($amount !== null, fn ($query) => $query->where(function ($query) use ($amount): void {
                $query->whereNull('amount_cents')->orWhere('amount_cents', (int) $amount);
            }))
            ->first();
    }

    private function eventId(string $eventType, array $data): string
    {
        return (string) ($data['id'] ?? $data['reference'] ?? $data['subscription_code'] ?? hash('sha256', $eventType.'|'.json_encode($data)));
    }

    private function recordIgnoredEvent(string $eventId, string $eventType): void
    {
        DB::table('subscription_events')->insert([
            'provider_key' => 'paystack',
            'event_type' => 'paystack.ignored.'.$eventType,
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
            'event' => $event['event'] ?? null,
            'data_id' => $event['data']['id'] ?? null,
            'reference' => $event['data']['reference'] ?? null,
        ];
    }
}
