<?php

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\Paystack\PaystackCheckoutService;
use LifeWheel\Plugins\Paystack\PaystackSignatureVerifier;
use LifeWheel\Plugins\Paystack\PaystackWebhookHandler;

require_once dirname(__DIR__).'/src/PaystackCheckoutService.php';
require_once dirname(__DIR__).'/src/PaystackSignatureVerifier.php';
require_once dirname(__DIR__).'/src/PaystackWebhookHandler.php';

Route::middleware(['auth', 'verified', 'twofactor'])
    ->prefix('app/billing/paystack')
    ->name('plugins.paystack.')
    ->group(function (): void {
        Route::post('/checkout/{mapping}', function (Request $request, PackageProviderMapping $mapping, PaystackCheckoutService $checkout) {
            try {
                $payload = $checkout->transactionPayload($request->user(), $mapping);
            } catch (\RuntimeException) {
                abort(403);
            }

            return back()->with('paystack_checkout_payload', $payload);
        })->name('checkout');
    });

Route::post('/webhooks/paystack', function (Request $request, PaystackSignatureVerifier $verifier, PaystackWebhookHandler $handler) {
    $provider = PaymentProvider::query()->where('key', 'paystack')->first();
    $secretKey = (string) data_get($provider?->settings, 'secret_key', env('PAYSTACK_SECRET_KEY', ''));

    abort_unless($provider?->enabled, 404);
    abort_unless($verifier->verify($request, $secretKey), 400);

    $event = json_decode($request->getContent(), true);
    abort_unless(is_array($event), 400);

    $handler->handle($event);

    return response()->json(['received' => true]);
})->name('plugins.paystack.webhook');
