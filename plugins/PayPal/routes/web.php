<?php

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\PayPal\PayPalCheckoutService;
use LifeWheel\Plugins\PayPal\PayPalWebhookHandler;
use LifeWheel\Plugins\PayPal\PayPalWebhookVerifier;

require_once dirname(__DIR__).'/src/PayPalCheckoutService.php';
require_once dirname(__DIR__).'/src/PayPalWebhookHandler.php';
require_once dirname(__DIR__).'/src/PayPalWebhookVerifier.php';

Route::middleware(['auth', 'verified', 'twofactor'])
    ->prefix('app/billing/paypal')
    ->name('plugins.paypal.')
    ->group(function (): void {
        Route::post('/checkout/{mapping}', function (Request $request, PackageProviderMapping $mapping, PayPalCheckoutService $checkout) {
            try {
                $payload = $checkout->orderPayload($request->user(), $mapping);
            } catch (\RuntimeException) {
                abort(403);
            }

            return back()->with('paypal_checkout_payload', $payload);
        })->name('checkout');
    });

Route::post('/webhooks/paypal', function (Request $request, PayPalWebhookVerifier $verifier, PayPalWebhookHandler $handler) {
    $provider = PaymentProvider::query()->where('key', 'paypal')->first();
    $webhookId = (string) data_get($provider?->settings, 'webhook_id', env('PAYPAL_WEBHOOK_ID', ''));

    abort_unless($provider?->enabled, 404);
    abort_unless($verifier->verify($request, $webhookId), 400);

    $event = json_decode($request->getContent(), true);
    abort_unless(is_array($event), 400);

    $handler->handle($event);

    return response()->json(['received' => true]);
})->name('plugins.paypal.webhook');
