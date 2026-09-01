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

require_once __DIR__.'/../../../plugins/Whop/src/WhopCheckoutService.php';
require_once __DIR__.'/../../../plugins/Whop/src/WhopSignatureVerifier.php';
require_once __DIR__.'/../../../plugins/Whop/src/WhopWebhookHandler.php';

final class WhopPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_whop_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Whop/plugin.json')), true));

        $this->assertSame('whop', $manifest->id);
        $this->assertContains('routes/web.php', $manifest->routes);
        $this->assertSame([], $manifest->migrations);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->loadWhopPluginForTest('ws_test_secret');
        $payload = $this->payload(['id' => 'msg_invalid', 'type' => 'payment.succeeded', 'data' => []]);

        $this->post('/webhooks/whop', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_ID' => 'msg_invalid',
            'HTTP_WEBHOOK_TIMESTAMP' => (string) time(),
            'HTTP_WEBHOOK_SIGNATURE' => 'v1,bad',
        ], [], [], $payload)->assertStatus(400);
    }

    public function test_webhook_rejects_stale_timestamp(): void
    {
        $this->loadWhopPluginForTest('ws_test_secret');
        $payload = $this->payload(['id' => 'msg_stale', 'type' => 'payment.succeeded', 'data' => []]);
        $timestamp = (string) (time() - 600);

        $this->post('/webhooks/whop', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_ID' => 'msg_stale',
            'HTTP_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_WEBHOOK_SIGNATURE' => $this->signature('msg_stale', $timestamp, $payload, 'ws_test_secret'),
        ], [], [], $payload)->assertStatus(400);
    }

    public function test_verified_payment_webhook_activates_subscription_and_entitlement(): void
    {
        $this->loadWhopPluginForTest('ws_test_secret');
        $user = User::factory()->create();
        $package = Package::factory()->create(['billing_interval' => 'monthly']);
        $feature = Feature::factory()->create(['slug' => 'lifewheel.use']);
        $package->features()->attach($feature->id, ['enabled' => true]);
        $this->whopMapping($package);
        $payload = $this->payload([
            'id' => 'msg_payment_1',
            'type' => 'payment.succeeded',
            'api_version' => 'v1',
            'data' => [
                'id' => 'pay_123',
                'membership_id' => 'mem_123',
                'member_id' => 'user_whop_123',
                'amount' => 2900,
                'currency' => 'usd',
                'product_id' => 'prod_whop',
                'plan_id' => 'plan_whop',
                'metadata' => ['user_id' => (string) $user->id, 'package_id' => (string) $package->id],
            ],
        ]);
        $headers = $this->headers('msg_payment_1', $payload, 'ws_test_secret');

        $this->post('/webhooks/whop', [], $headers, [], [], $payload)->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'provider_key' => 'whop',
            'external_subscription_id' => 'mem_123',
            'status' => 'active',
        ]);
        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'lifewheel.use'));
    }

    public function test_duplicate_webhook_delivery_is_idempotent(): void
    {
        $this->loadWhopPluginForTest('ws_test_secret');
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $this->whopMapping($package);
        $payload = $this->payload([
            'id' => 'msg_duplicate',
            'type' => 'payment.succeeded',
            'data' => [
                'id' => 'pay_duplicate',
                'membership_id' => 'mem_duplicate',
                'amount' => 1000,
                'currency' => 'usd',
                'metadata' => ['user_id' => (string) $user->id, 'package_id' => (string) $package->id],
            ],
        ]);
        $headers = $this->headers('msg_duplicate', $payload, 'ws_test_secret');

        $this->post('/webhooks/whop', [], $headers, [], [], $payload)->assertOk();
        $this->post('/webhooks/whop', [], $headers, [], [], $payload)->assertOk();

        $this->assertSame(1, Subscription::query()->where('external_subscription_id', 'mem_duplicate')->count());
        $this->assertSame(1, SubscriptionEvent::query()->where('external_event_id', 'msg_duplicate')->count());
    }

    public function test_checkout_payload_requires_enabled_whop_mapping(): void
    {
        $this->loadWhopPluginForTest('ws_test_secret', false);
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $mapping = $this->whopMapping($package);

        $this->actingAs($user)
            ->post("/app/billing/whop/checkout/{$mapping->id}")
            ->assertForbidden();
    }

    private function loadWhopPluginForTest(string $webhookSecret, bool $enabled = true): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'whop',
            'name' => 'Whop',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Whop plugin.',
            'path' => base_path('plugins/Whop'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Whop/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        PaymentProvider::query()->updateOrCreate(
            ['key' => 'whop'],
            ['name' => 'Whop', 'enabled' => $enabled, 'sandbox' => true, 'settings' => ['webhook_secret' => $webhookSecret]],
        );

        Route::middleware('web')->group(base_path('plugins/Whop/routes/web.php'));
    }

    private function whopMapping(Package $package): PackageProviderMapping
    {
        $provider = PaymentProvider::query()->where('key', 'whop')->firstOrFail();

        return PackageProviderMapping::query()->create([
            'package_id' => $package->id,
            'payment_provider_id' => $provider->id,
            'external_product_id' => 'prod_whop',
            'external_price_id' => 'plan_whop',
            'amount_cents' => 2900,
            'currency' => 'USD',
            'active' => true,
        ]);
    }

    private function payload(array $event): string
    {
        return json_encode($event, JSON_THROW_ON_ERROR);
    }

    private function headers(string $webhookId, string $payload, string $secret): array
    {
        $timestamp = (string) time();

        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_ID' => $webhookId,
            'HTTP_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_WEBHOOK_SIGNATURE' => $this->signature($webhookId, $timestamp, $payload, $secret),
        ];
    }

    private function signature(string $webhookId, string $timestamp, string $payload, string $secret): string
    {
        $signature = base64_encode(hash_hmac('sha256', "{$webhookId}.{$timestamp}.{$payload}", $secret, true));

        return 'v1,'.$signature;
    }
}
