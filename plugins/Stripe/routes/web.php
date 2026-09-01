<?php

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\Stripe\StripeCheckoutService;
use LifeWheel\Plugins\Stripe\StripeSignatureVerifier;
use LifeWheel\Plugins\Stripe\StripeWebhookHandler;

require_once dirname(__DIR__).'/src/StripeCheckoutService.php';
require_once dirname(__DIR__).'/src/StripeSignatureVerifier.php';
require_once dirname(__DIR__).'/src/StripeWebhookHandler.php';

Route::middleware(['auth', 'verified', 'twofactor'])
    ->prefix('app/billing/stripe')
    ->name('plugins.stripe.')
    ->group(function (): void {
        Route::post('/checkout/{mapping}', function (Request $request, PackageProviderMapping $mapping, StripeCheckoutService $checkout) {
            try {
                $payload = $checkout->checkoutPayload($request->user(), $mapping);
            } catch (\RuntimeException) {
                abort(403);
            }

            return back()->with('stripe_checkout_payload', $payload);
        })->name('checkout');
    });

Route::post('/webhooks/stripe', function (Request $request, StripeSignatureVerifier $verifier, StripeWebhookHandler $handler) {
    $provider = PaymentProvider::query()->where('key', 'stripe')->first();
    $endpointSecret = (string) data_get($provider?->settings, 'webhook_secret', env('STRIPE_WEBHOOK_SECRET', ''));

    abort_unless($provider?->enabled, 404);
    abort_unless($verifier->verify($request, $endpointSecret), 400);

    $event = json_decode($request->getContent(), true);
    abort_unless(is_array($event), 400);

    $handler->handle($event);

    return response()->json(['received' => true]);
})->name('plugins.stripe.webhook');
