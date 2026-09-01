<?php

namespace LifeWheel\Plugins\Stripe;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\User;
use RuntimeException;

final class StripeCheckoutService
{
    public function checkoutPayload(User $user, PackageProviderMapping $mapping): array
    {
        $mapping->loadMissing('package', 'provider');
        $provider = $mapping->provider;

        if (! $provider instanceof PaymentProvider || $provider->key !== 'stripe' || ! $provider->enabled || ! $mapping->active) {
            throw new RuntimeException('Stripe checkout is not enabled for this package.');
        }

        if (! $mapping->external_price_id) {
            throw new RuntimeException('Stripe price mapping is missing.');
        }

        return [
            'mode' => $mapping->package->billing_interval === 'lifetime' ? 'payment' : 'subscription',
            'line_items' => [
                ['price' => $mapping->external_price_id, 'quantity' => 1],
            ],
            'customer_email' => $user->email,
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
                'package_id' => (string) $mapping->package_id,
            ],
            'success_url' => route('member.billing.index').'?checkout=stripe_success',
            'cancel_url' => route('member.billing.index').'?checkout=stripe_cancelled',
        ];
    }
}
