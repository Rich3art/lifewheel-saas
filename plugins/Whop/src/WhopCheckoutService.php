<?php

namespace LifeWheel\Plugins\Whop;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\User;
use RuntimeException;

final class WhopCheckoutService
{
    public function checkoutConfigurationPayload(User $user, PackageProviderMapping $mapping): array
    {
        $mapping->loadMissing('package', 'provider');
        $provider = $mapping->provider;

        if (! $provider instanceof PaymentProvider || $provider->key !== 'whop' || ! $provider->enabled || ! $mapping->active) {
            throw new RuntimeException('Whop checkout is not enabled for this package.');
        }

        if (! $mapping->package->active || ! $mapping->package->public) {
            throw new RuntimeException('Whop checkout is not available for this package.');
        }

        if (! $mapping->external_price_id && ! $mapping->external_product_id) {
            throw new RuntimeException('Whop plan or product mapping is missing.');
        }

        return [
            'plan_id' => $mapping->external_price_id,
            'product_id' => $mapping->external_product_id,
            'metadata' => [
                'user_id' => (string) $user->id,
                'package_id' => (string) $mapping->package_id,
                'provider_mapping_id' => (string) $mapping->id,
            ],
            'redirect_url' => route('member.billing.index').'?checkout=whop_return',
        ];
    }
}
