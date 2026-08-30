<?php

namespace Tests\Feature\Admin;

use App\Models\Feature;
use App\Models\Package;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SaasPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_package_with_features_and_limits(): void
    {
        $admin = $this->admin();
        $feature = Feature::factory()->create();

        $this->actingAs($admin)->post('/admin/packages', [
            'name' => 'Premium',
            'slug' => 'premium',
            'price_cents' => 4900,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'trial_days' => 0,
            'sort_order' => 10,
            'features' => [$feature->id],
            'limits' => "ai.messages=100\nreviews=10",
            'active' => 1,
            'public' => 1,
        ])->assertRedirect();

        $package = Package::query()->where('slug', 'premium')->firstOrFail();
        $this->assertTrue($package->features()->whereKey($feature->id)->exists());
        $this->assertDatabaseHas('package_limits', ['package_id' => $package->id, 'key' => 'ai.messages', 'value' => '100']);
    }

    public function test_admin_can_assign_package_to_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();
        $package = Package::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$target->id}/packages", [
            'package_id' => $package->id,
        ])->assertRedirect();

        $this->assertTrue($target->fresh()->packages()->whereKey($package->id)->exists());
    }

    private function admin(): User
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', 'admin.users.manage', 'admin.saas.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
