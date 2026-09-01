<?php

namespace App\Services\Billing;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final readonly class BillingManager
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function activateSubscription(BillingSubscriptionData $data, ?User $actor = null): Subscription
    {
        return DB::transaction(function () use ($data, $actor): Subscription {
            $values = [
                'user_id' => $data->userId,
                'package_id' => $data->packageId,
                'payment_provider_id' => $data->paymentProviderId,
                'external_customer_id' => $data->externalCustomerId,
                'status' => $data->status,
                'amount_cents' => $data->amountCents,
                'currency' => strtoupper($data->currency),
                'billing_interval' => $data->billingInterval,
                'trial' => $data->trial,
                'current_period_starts_at' => $data->currentPeriodStartsAt,
                'current_period_ends_at' => $data->currentPeriodEndsAt,
                'cancels_at' => $data->cancelsAt,
                'cancelled_at' => null,
                'metadata' => $data->metadata,
            ];

            $subscription = $data->externalSubscriptionId
                ? Subscription::query()->updateOrCreate(
                    ['provider_key' => $data->providerKey, 'external_subscription_id' => $data->externalSubscriptionId],
                    $values,
                )
                : Subscription::query()->create([
                    ...$values,
                    'provider_key' => $data->providerKey,
                    'external_subscription_id' => null,
                ]);

            $this->syncUserPackage($subscription, $actor);
            $this->recordEvent($subscription, 'subscription.activated', null, ['status' => $data->status]);
            $this->audit->log('billing.subscription_activated', $actor, $subscription);

            return $subscription;
        });
    }

    public function cancelSubscription(Subscription $subscription, ?User $actor = null, bool $immediate = false): Subscription
    {
        return DB::transaction(function () use ($subscription, $actor, $immediate): Subscription {
            $subscription->forceFill([
                'status' => $immediate ? 'cancelled' : 'cancelling',
                'cancels_at' => $immediate ? now() : ($subscription->current_period_ends_at ?? now()),
                'cancelled_at' => $immediate ? now() : null,
            ])->save();

            if ($immediate) {
                DB::table('user_packages')
                    ->where('user_id', $subscription->user_id)
                    ->where('package_id', $subscription->package_id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled', 'ends_at' => now(), 'updated_at' => now()]);
            }

            $this->recordEvent($subscription, 'subscription.cancelled', null, ['immediate' => $immediate]);
            $this->audit->log('billing.subscription_cancelled', $actor, $subscription, ['immediate' => $immediate]);

            return $subscription->fresh();
        });
    }

    public function recordInvoice(Subscription $subscription, string $status, int $amountCents, string $currency, ?string $externalInvoiceId = null, ?array $metadata = null): void
    {
        $values = [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'provider_key' => $subscription->provider_key,
            'external_invoice_id' => $externalInvoiceId,
            'status' => $status,
            'amount_cents' => $amountCents,
            'currency' => strtoupper($currency),
            'paid_at' => $status === 'paid' ? now() : null,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($externalInvoiceId) {
            DB::table('billing_invoices')->updateOrInsert(
                ['provider_key' => $subscription->provider_key, 'external_invoice_id' => $externalInvoiceId],
                $values,
            );

            return;
        }

        DB::table('billing_invoices')->insert($values);
    }

    public function recordEvent(Subscription $subscription, string $eventType, ?string $externalEventId = null, ?array $payloadSummary = null): void
    {
        $values = [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'provider_key' => $subscription->provider_key,
            'event_type' => $eventType,
            'external_event_id' => $externalEventId,
            'payload_summary' => $payloadSummary ? json_encode($payloadSummary) : null,
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($externalEventId) {
            DB::table('subscription_events')->updateOrInsert(
                ['provider_key' => $subscription->provider_key, 'external_event_id' => $externalEventId],
                $values,
            );

            return;
        }

        DB::table('subscription_events')->insert($values);
    }

    private function syncUserPackage(Subscription $subscription, ?User $actor): void
    {
        Package::query()->findOrFail($subscription->package_id);

        DB::table('user_packages')->updateOrInsert(
            [
                'user_id' => $subscription->user_id,
                'package_id' => $subscription->package_id,
                'status' => 'active',
            ],
            [
                'starts_at' => $subscription->current_period_starts_at ?? now(),
                'ends_at' => $subscription->current_period_ends_at,
                'assigned_by' => $actor?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
