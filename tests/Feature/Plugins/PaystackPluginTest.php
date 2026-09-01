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
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/Paystack/src/PaystackCheckoutService.php';
require_once __DIR__.'/../../../plugins/Paystack/src/PaystackSignatureVerifier.php';
require_once __DIR__.'/../../../plugins/Paystack/src/PaystackWebhookHandler.php';

final class PaystackPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_paystack_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Paystack/plugin.json')), true));

        $this->assertSame('paystack', $manifest->id);
        $this->assertContains('routes/web.php', $manifest->routes);
        $this->assertSame([], $manifest->migrations);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->loadPaystackPluginForTest('sk_test_secret');
        $payload = $this->payload(['event' => 'charge.success', 'data' => ['id' => 1]]);

        $this->post('/webhooks/paystack', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => 'bad',
        ], [], [], $payload)->assertStatus(400);
    }

    public function test_verified_charge_webhook_activates_subscription_and_entitlement(): void
    {
        $this->loadPaystackPluginForTest('sk_test_secret');
        $user = User::factory()->create();
        $package = Package::factory()->create(['billing_interval' => 'monthly']);
        $feature = Feature::factory()->create(['slug' => 'lifewheel.use']);
        $package->features()->attach($feature->id, ['enabled' => true]);
        $this->paystackMapping($package);
        $payload = $this->payload([
            'event' => 'charge.success',
            'data' => [
                'id' => 1001,
                'reference' => 'ref_1001',
                'amount' => 2900,
                'currency' => 'NGN',
                'customer' => ['customer_code' => 'CUS_123'],
                'subscription' => ['subscription_code' => 'SUB_123'],
                'metadata' => ['user_id' => (string) $user->id, 'package_id' => (string) $package->id],
            ],
        ]);

        $this->post('/webhooks/paystack', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $this->signature($payload, 'sk_test_secret'),
        ], [], [], $payload)->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'provider_key' => 'paystack',
            'external_subscription_id' => 'SUB_123',
            'status' => 'active',
        ]);
        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'lifewheel.use'));
    }

    public function test_duplicate_paystack_webhook_event_is_idempotent(): void
    {
        $this->loadPaystackPluginForTest('sk_test_secret');
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $this->paystackMapping($package);
        $payload = $this->payload([
            'event' => 'charge.success',
            'data' => [
                'id' => 2002,
                'reference' => 'ref_2002',
                'amount' => 1000,
                'currency' => 'NGN',
                'subscription' => ['subscription_code' => 'SUB_DUP'],
                'metadata' => ['user_id' => (string) $user->id, 'package_id' => (string) $package->id],
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $this->signature($payload, 'sk_test_secret'),
        ];

        $this->post('/webhooks/paystack', [], $headers, [], [], $payload)->assertOk();
        $this->post('/webhooks/paystack', [], $headers, [], [], $payload)->assertOk();

        $this->assertSame(1, Subscription::query()->where('external_subscription_id', 'SUB_DUP')->count());
        $this->assertSame(1, SubscriptionEvent::query()->where('external_event_id', '2002')->count());
    }

    public function test_checkout_payload_requires_enabled_paystack_mapping(): void
    {
        $this->loadPaystackPluginForTest('sk_test_secret', false);
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $mapping = $this->paystackMapping($package);

        $this->actingAs($user)
            ->post("/app/billing/paystack/checkout/{$mapping->id}")
            ->assertForbidden();
    }

    private function loadPaystackPluginForTest(string $secretKey, bool $enabled = true): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'paystack',
            'name' => 'Paystack',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Paystack plugin.',
            'path' => base_path('plugins/Paystack'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Paystack/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        PaymentProvider::query()->updateOrCreate(
            ['key' => 'paystack'],
            ['name' => 'Paystack', 'enabled' => $enabled, 'sandbox' => true, 'settings' => ['secret_key' => $secretKey]],
        );

        Route::middleware('web')->group(base_path('plugins/Paystack/routes/web.php'));
    }

    private function paystackMapping(Package $package): PackageProviderMapping
    {
        $provider = PaymentProvider::query()->where('key', 'paystack')->firstOrFail();

        return PackageProviderMapping::query()->create([
            'package_id' => $package->id,
            'payment_provider_id' => $provider->id,
            'external_product_id' => 'paystack-plan',
            'external_price_id' => 'paystack-plan-code',
            'amount_cents' => 2900,
            'currency' => 'NGN',
            'active' => true,
        ]);
    }

    private function payload(array $event): string
    {
        return json_encode($event, JSON_THROW_ON_ERROR);
    }

    private function signature(string $payload, string $secret): string
    {
        return hash_hmac('sha512', $payload, $secret);
    }
}
