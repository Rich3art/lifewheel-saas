<?php

namespace App\Services\Billing;

use App\Models\PackageProviderMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use RuntimeException;

final class CheckoutRouter
{
    /**
     * @var array<string, string>
     */
    private array $routeNames = [
        'stripe' => 'plugins.stripe.checkout',
        'paypal' => 'plugins.paypal.checkout',
        'paystack' => 'plugins.paystack.checkout',
        'whop' => 'plugins.whop.checkout',
    ];

    public function redirectToProvider(PackageProviderMapping $mapping): RedirectResponse
    {
        $mapping->loadMissing('provider', 'package');
        $routeName = $this->routeNames[$mapping->provider->key] ?? null;

        if ($routeName === null || ! Route::has($routeName)) {
            throw new RuntimeException('Checkout provider is not currently available.');
        }

        return redirect()->route($routeName, $mapping, 307);
    }
}
