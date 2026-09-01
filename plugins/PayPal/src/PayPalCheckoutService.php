<?php

namespace LifeWheel\Plugins\PayPal;

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\User;
use RuntimeException;

final class PayPalCheckoutService
{
    public function orderPayload(User $user, PackageProviderMapping $mapping): array
    {
        $mapping->loadMissing('package', 'provider');
        $provider = $mapping->provider;

        if (! $provider instanceof PaymentProvider || $provider->key !== 'paypal' || ! $provider->enabled || ! $mapping->active) {
            throw new RuntimeException('PayPal checkout is not enabled for this package.');
        }

        if (! $mapping->package->active || ! $mapping->package->public) {
            throw new RuntimeException('PayPal checkout is not available for this package.');
        }

        $amount = number_format(($mapping->amount_cents ?? $mapping->package->price_cents) / 100, 2, '.', '');
        $currency = strtoupper((string) ($mapping->currency ?? $mapping->package->currency));

        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'package-'.$mapping->package_id,
                'custom_id' => 'user:'.$user->id.'|package:'.$mapping->package_id,
                'description' => $mapping->package->name,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
            ]],
            'application_context' => [
                'return_url' => route('member.billing.index').'?checkout=paypal_success',
                'cancel_url' => route('member.billing.index').'?checkout=paypal_cancelled',
                'user_action' => 'PAY_NOW',
            ],
        ];
    }
}
