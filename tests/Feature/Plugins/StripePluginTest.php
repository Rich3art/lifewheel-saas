<?php

namespace Tests\Feature\Plugins;

use App\Models\Feature;
use App\Models\InstalledPlugin;
use App\Models\Package;
use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Plugins\PluginManifest;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\Stripe\StripeSignatureVerifier;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/Stripe/src/StripeCheckoutService.php';
require_once __DIR__.'/../../../plugins/Stripe/src/StripeSignatureVerifier.php';
require_once __DIR__.'/../../../plugins/Stripe/src/StripeWebhookHandler.php';

final class StripePluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Stripe/plugin.json')), true));

        $this->assertSame('stripe', $manifest->id);
        $this->assertContains('routes/web.php', $manifest->routes);
        $this->assertSame([], $manifest->migrations);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->loadStripePluginForTest('whsec_test');
        $payload = json_encode(['id' => 'evt_invalid', 'type' => 'checkout.session.completed']);

        $this->post('/webhooks/stripe', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=bad',
        ], [], [], $payload)
            ->assertStatus(400);
    }

    public function test_verified_checkout_webhook_activates_subscription_and_entitlement(): void
    {
        $this->loadStripePluginForTest('whsec_test');
        $user = User::factory()->create();
        $package = Package::factory()->create(['billing_interval' => 'monthly']);
        $feature = Feature::factory()->create(['slug' => 'lifewheel.use']);
        $package->features()->attach($feature->id, ['enabled' => true]);
        $this->stripeMapping($package);
        $payload = $this->payload([
            'id' => 'evt_checkout_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'customer' => 'cus_123',
                    'subscription' => 'sub_123',
                    'client_reference_id' => (string) $user->id,
                    'amount_total' => 2900,
                    'currency' => 'usd',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'package_id' => (string) $package->id,
                    ],
                ],
            ],
        ]);

        $this->post('/webhooks/stripe', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $this->signature($payload, 'whsec_test'),
        ], [], [], $payload)
            ->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'provider_key' => 'stripe',
            'external_subscription_id' => 'sub_123',
            'status' => 'active',
        ]);
        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'lifewheel.use'));
    }

    public function test_duplicate_webhook_event_is_idempotent(): void
    {
        $this->loadStripePluginForTest('whsec_test');
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $this->stripeMapping($package);
        $payload = $this->payload([
            'id' => 'evt_duplicate',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_duplicate',
                    'customer' => 'cus_dup',
                    'subscription' => 'sub_duplicate',
                    'client_reference_id' => (string) $user->id,
                    'amount_total' => 1000,
                    'currency' => 'usd',
                    'metadata' => ['user_id' => (string) $user->id, 'package_id' => (string) $package->id],
                ],
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $this->signature($payload, 'whsec_test'),
        ];

        $this->post('/webhooks/stripe', [], $headers, [], [], $payload)->assertOk();
        $this->post('/webhooks/stripe', [], $headers, [], [], $payload)->assertOk();

        $this->assertSame(1, \App\Models\Subscription::query()->where('external_subscription_id', 'sub_duplicate')->count());
        $this->assertSame(1, \App\Models\SubscriptionEvent::query()->where('external_event_id', 'evt_duplicate')->count());
    }

    public function test_checkout_payload_requires_enabled_stripe_mapping(): void
    {
        $this->loadStripePluginForTest('whsec_test', false);
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $mapping = $this->stripeMapping($package);

        $this->actingAs($user)
            ->post("/app/billing/stripe/checkout/{$mapping->id}")
            ->assertForbidden();
    }

    private function loadStripePluginForTest(string $webhookSecret, bool $enabled = true): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'stripe',
            'name' => 'Stripe',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Stripe plugin.',
            'path' => base_path('plugins/Stripe'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Stripe/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        PaymentProvider::query()->updateOrCreate(
            ['key' => 'stripe'],
            ['name' => 'Stripe', 'enabled' => $enabled, 'sandbox' => true, 'settings' => ['webhook_secret' => $webhookSecret]],
        );

        Route::middleware('web')->group(base_path('plugins/Stripe/routes/web.php'));
    }

    private function stripeMapping(Package $package): PackageProviderMapping
    {
        $provider = PaymentProvider::query()->where('key', 'stripe')->firstOrFail();

        return PackageProviderMapping::query()->create([
            'package_id' => $package->id,
            'payment_provider_id' => $provider->id,
            'external_product_id' => 'prod_123',
            'external_price_id' => 'price_123',
            'amount_cents' => 2900,
            'currency' => 'USD',
            'active' => true,
        ]);
    }

    private function payload(array $event): string
    {
        return json_encode($event, JSON_THROW_ON_ERROR);
    }

    private function signature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
