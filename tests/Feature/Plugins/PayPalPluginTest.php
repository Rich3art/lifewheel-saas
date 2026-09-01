<?php

namespace Tests\Feature\Plugins;

use App\Models\Feature;
use App\Models\InstalledPlugin;
use App\Models\Package;
use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Plugins\PluginManifest;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/PayPal/src/PayPalCheckoutService.php';
require_once __DIR__.'/../../../plugins/PayPal/src/PayPalWebhookHandler.php';
require_once __DIR__.'/../../../plugins/PayPal/src/PayPalWebhookVerifier.php';

final class PayPalPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_paypal_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/PayPal/plugin.json')), true));

        $this->assertSame('paypal', $manifest->id);
        $this->assertContains('routes/web.php', $manifest->routes);
        $this->assertSame([], $manifest->migrations);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->loadPayPalPluginForTest('WEBHOOK_ID');
        $payload = $this->payload(['id' => 'WH-invalid', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED']);

        $this->post('/webhooks/paypal', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYPAL_TRANSMISSION_ID' => 'transmission-1',
            'HTTP_PAYPAL_TRANSMISSION_TIME' => now()->toIso8601String(),
            'HTTP_PAYPAL_TRANSMISSION_SIG' => 'bad',
            'HTTP_PAYPAL_CERT_URL' => 'https://api-m.sandbox.paypal.com/certs/test.pem',
            'HTTP_PAYPAL_AUTH_ALGO' => 'SHA256withRSA',
        ], [], [], $payload)
            ->assertStatus(400);
    }

    public function test_verified_payment_webhook_activates_subscription_and_entitlement(): void
    {
        [$privateKey, $certificate] = $this->keyPair();
        $this->loadPayPalPluginForTest('WEBHOOK_ID', $certificate);
        $user = User::factory()->create();
        $package = Package::factory()->create(['billing_interval' => 'monthly']);
        $feature = Feature::factory()->create(['slug' => 'lifewheel.use']);
        $package->features()->attach($feature->id, ['enabled' => true]);
        $this->paypalMapping($package);
        $payload = $this->payload([
            'id' => 'WH-payment-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture-1',
                'custom_id' => 'user:'.$user->id.'|package:'.$package->id,
                'amount' => ['value' => '29.00', 'currency_code' => 'USD'],
            ],
        ]);

        $this->post('/webhooks/paypal', [], $this->headers($payload, 'WEBHOOK_ID', $privateKey), [], [], $payload)
            ->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'provider_key' => 'paypal',
            'external_subscription_id' => 'capture-1',
            'status' => 'active',
        ]);
        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'lifewheel.use'));
    }

    public function test_duplicate_paypal_webhook_event_is_idempotent(): void
    {
        [$privateKey, $certificate] = $this->keyPair();
        $this->loadPayPalPluginForTest('WEBHOOK_ID', $certificate);
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $this->paypalMapping($package);
        $payload = $this->payload([
            'id' => 'WH-duplicate',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture-duplicate',
                'custom_id' => 'user:'.$user->id.'|package:'.$package->id,
                'amount' => ['value' => '10.00', 'currency_code' => 'USD'],
            ],
        ]);
        $headers = $this->headers($payload, 'WEBHOOK_ID', $privateKey);

        $this->post('/webhooks/paypal', [], $headers, [], [], $payload)->assertOk();
        $this->post('/webhooks/paypal', [], $headers, [], [], $payload)->assertOk();

        $this->assertSame(1, Subscription::query()->where('external_subscription_id', 'capture-duplicate')->count());
        $this->assertSame(1, SubscriptionEvent::query()->where('external_event_id', 'WH-duplicate')->count());
    }

    public function test_checkout_payload_requires_enabled_paypal_mapping(): void
    {
        $this->loadPayPalPluginForTest('WEBHOOK_ID', enabled: false);
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $mapping = $this->paypalMapping($package);

        $this->actingAs($user)
            ->post("/app/billing/paypal/checkout/{$mapping->id}")
            ->assertForbidden();
    }

    private function loadPayPalPluginForTest(string $webhookId, ?string $certificate = null, bool $enabled = true): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'paypal',
            'name' => 'PayPal',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test PayPal plugin.',
            'path' => base_path('plugins/PayPal'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/PayPal/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        PaymentProvider::query()->updateOrCreate(
            ['key' => 'paypal'],
            ['name' => 'PayPal', 'enabled' => $enabled, 'sandbox' => true, 'settings' => ['webhook_id' => $webhookId]],
        );

        Http::fake([
            'https://api-m.sandbox.paypal.com/certs/test.pem' => Http::response($certificate ?? 'not a certificate', 200),
        ]);
        Route::middleware('web')->group(base_path('plugins/PayPal/routes/web.php'));
    }

    private function paypalMapping(Package $package): PackageProviderMapping
    {
        $provider = PaymentProvider::query()->where('key', 'paypal')->firstOrFail();

        return PackageProviderMapping::query()->create([
            'package_id' => $package->id,
            'payment_provider_id' => $provider->id,
            'external_product_id' => 'paypal-product',
            'external_price_id' => 'paypal-plan',
            'amount_cents' => 2900,
            'currency' => 'USD',
            'active' => true,
        ]);
    }

    private function payload(array $event): string
    {
        return json_encode($event, JSON_THROW_ON_ERROR);
    }

    private function headers(string $payload, string $webhookId, string $privateKey): array
    {
        $transmissionId = 'transmission-'.sha1($payload);
        $transmissionTime = now()->toIso8601String();
        $crc = sprintf('%u', crc32($payload));
        $message = "{$transmissionId}|{$transmissionTime}|{$webhookId}|{$crc}";
        openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYPAL_TRANSMISSION_ID' => $transmissionId,
            'HTTP_PAYPAL_TRANSMISSION_TIME' => $transmissionTime,
            'HTTP_PAYPAL_TRANSMISSION_SIG' => base64_encode($signature),
            'HTTP_PAYPAL_CERT_URL' => 'https://api-m.sandbox.paypal.com/certs/test.pem',
            'HTTP_PAYPAL_AUTH_ALGO' => 'SHA256withRSA',
        ];
    }

    private function keyPair(): array
    {
        $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($private, $privateKey);
        $csr = openssl_csr_new(['commonName' => 'api-m.sandbox.paypal.com'], $private);
        $cert = openssl_csr_sign($csr, null, $private, 1);
        openssl_x509_export($cert, $certificate);

        return [$privateKey, $certificate];
    }
}
