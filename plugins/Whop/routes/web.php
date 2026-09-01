<?php

use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\Whop\WhopCheckoutService;
use LifeWheel\Plugins\Whop\WhopSignatureVerifier;
use LifeWheel\Plugins\Whop\WhopWebhookHandler;

require_once dirname(__DIR__).'/src/WhopCheckoutService.php';
require_once dirname(__DIR__).'/src/WhopSignatureVerifier.php';
require_once dirname(__DIR__).'/src/WhopWebhookHandler.php';

Route::middleware(['auth', 'verified', 'twofactor'])
    ->prefix('app/billing/whop')
    ->name('plugins.whop.')
    ->group(function (): void {
        Route::post('/checkout/{mapping}', function (Request $request, PackageProviderMapping $mapping, WhopCheckoutService $checkout) {
            try {
                $payload = $checkout->checkoutConfigurationPayload($request->user(), $mapping);
            } catch (\RuntimeException) {
                abort(403);
            }

            return back()->with('whop_checkout_payload', $payload);
        })->name('checkout');
    });

Route::post('/webhooks/whop', function (Request $request, WhopSignatureVerifier $verifier, WhopWebhookHandler $handler) {
    $provider = PaymentProvider::query()->where('key', 'whop')->first();
    $webhookSecret = (string) data_get($provider?->settings, 'webhook_secret', env('WHOP_WEBHOOK_SECRET', ''));

    abort_unless($provider?->enabled, 404);
    abort_unless($verifier->verify($request, $webhookSecret), 400);

    $event = json_decode($request->getContent(), true);
    abort_unless(is_array($event), 400);

    $handler->handle($event, (string) $request->header('webhook-id', ''));

    return response()->json(['received' => true]);
})->name('plugins.whop.webhook');
