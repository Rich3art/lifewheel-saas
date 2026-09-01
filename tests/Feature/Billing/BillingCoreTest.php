<?php

namespace Tests\Feature\Billing;

use App\Models\Feature;
use App\Models\Package;
use App\Models\PaymentProvider;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingManager;
use App\Services\Billing\BillingSubscriptionData;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BillingCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_billing_requires_billing_permission(): void
    {
        $admin = User::factory()->create();
        $admin->directPermissions()->attach(Permission::factory()->create(['slug' => 'admin.dashboard.view']));

        $this->actingAs($admin)
            ->get('/admin/billing')
            ->assertForbidden();
    }

    public function test_manual_subscription_activation_creates_subscription_and_entitlement(): void
    {
        $admin = $this->billingAdmin();
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $feature = Feature::factory()->create(['slug' => 'lifewheel.use']);
        $package->features()->attach($feature->id, ['enabled' => true]);

        $this->actingAs($admin)
            ->post('/admin/billing/subscriptions/manual', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'billing_interval' => 'monthly',
                'amount_cents' => 1900,
                'currency' => 'usd',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'provider_key' => 'manual',
            'status' => 'active',
        ]);
        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'lifewheel.use'));
    }

    public function test_immediate_cancellation_revokes_synced_user_package(): void
    {
        $admin = $this->billingAdmin();
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $subscription = app(BillingManager::class)->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $package->id,
            providerKey: 'manual',
            externalSubscriptionId: 'manual-test',
            currentPeriodStartsAt: now(),
            currentPeriodEndsAt: now()->addMonth(),
        ), $admin);

        $this->actingAs($admin)
            ->put("/admin/billing/subscriptions/{$subscription->id}/cancel", ['immediate' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('user_packages', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_create_provider_mapping(): void
    {
        $admin = $this->billingAdmin();
        $package = Package::factory()->create();
        $provider = PaymentProvider::query()->create(['key' => 'stripe', 'name' => 'Stripe']);

        $this->actingAs($admin)
            ->post('/admin/billing/mappings', [
                'package_id' => $package->id,
                'payment_provider_id' => $provider->id,
                'external_product_id' => 'prod_123',
                'external_price_id' => 'price_123',
                'amount_cents' => 2900,
                'currency' => 'usd',
                'active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('package_provider_mappings', [
            'package_id' => $package->id,
            'payment_provider_id' => $provider->id,
            'external_product_id' => 'prod_123',
            'currency' => 'USD',
            'active' => true,
        ]);
    }

    public function test_subscription_events_are_idempotent_only_when_external_event_id_exists(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $subscription = app(BillingManager::class)->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $package->id,
            providerKey: 'manual',
            externalSubscriptionId: 'event-test',
        ));

        $billing = app(BillingManager::class);
        $billing->recordEvent($subscription, 'subscription.updated');
        $billing->recordEvent($subscription, 'subscription.updated');
        $billing->recordEvent($subscription, 'provider.webhook', 'evt_123', ['status' => 'active']);
        $billing->recordEvent($subscription, 'provider.webhook', 'evt_123', ['status' => 'active']);

        $this->assertSame(4, DB::table('subscription_events')->where('subscription_id', $subscription->id)->count());
        $this->assertSame(1, DB::table('subscription_events')->where('external_event_id', 'evt_123')->count());
    }

    public function test_member_billing_page_only_lists_own_subscriptions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownPackage = Package::factory()->create(['name' => 'Own Package']);
        $otherPackage = Package::factory()->create(['name' => 'Other Package']);
        app(BillingManager::class)->activateSubscription(new BillingSubscriptionData(
            userId: $user->id,
            packageId: $ownPackage->id,
            providerKey: 'manual',
            externalSubscriptionId: 'own-sub',
        ));
        app(BillingManager::class)->activateSubscription(new BillingSubscriptionData(
            userId: $other->id,
            packageId: $otherPackage->id,
            providerKey: 'manual',
            externalSubscriptionId: 'other-sub',
        ));

        $this->actingAs($user)
            ->get('/app/billing')
            ->assertOk()
            ->assertSee('Own Package')
            ->assertDontSee('Other Package');
    }

    private function billingAdmin(): User
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', 'admin.billing.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
