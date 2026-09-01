<?php

namespace LifeWheel\Plugins\Paystack;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\User;
use RuntimeException;

final class PaystackCheckoutService
{
    public function transactionPayload(User $user, PackageProviderMapping $mapping): array
    {
        $mapping->loadMissing('package', 'provider');
        $provider = $mapping->provider;

        if (! $provider instanceof PaymentProvider || $provider->key !== 'paystack' || ! $provider->enabled || ! $mapping->active) {
            throw new RuntimeException('Paystack checkout is not enabled for this package.');
        }

        $amount = $mapping->amount_cents ?? $mapping->package->price_cents;
        $currency = strtoupper((string) ($mapping->currency ?? $mapping->package->currency));

        return [
            'email' => $user->email,
            'amount' => $amount,
            'currency' => $currency,
            'callback_url' => route('member.billing.index').'?checkout=paystack_return',
            'metadata' => [
                'user_id' => (string) $user->id,
                'package_id' => (string) $mapping->package_id,
                'provider_mapping_id' => (string) $mapping->id,
            ],
        ];
    }
}
