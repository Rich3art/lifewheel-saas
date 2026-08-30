<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\Package;
use App\Models\User;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_feature_grants_entitlement(): void
    {
        $user = User::factory()->create();
        $feature = Feature::factory()->create(['slug' => 'ai.coach']);
        $package = Package::factory()->create();
        $package->features()->attach($feature, ['enabled' => true]);
        $user->packages()->attach($package, ['status' => 'active', 'starts_at' => now()]);

        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'ai.coach'));
    }

    public function test_user_override_can_deny_package_feature(): void
    {
        $user = User::factory()->create();
        $feature = Feature::factory()->create(['slug' => 'ai.coach']);
        $package = Package::factory()->create();
        $package->features()->attach($feature, ['enabled' => true]);
        $user->packages()->attach($package, ['status' => 'active', 'starts_at' => now()]);
        $user->featureOverrides()->create(['feature_id' => $feature->id, 'enabled' => false]);

        $this->assertFalse(app(EntitlementService::class)->userHasFeature($user, 'ai.coach'));
    }

    public function test_user_override_can_grant_feature_without_package(): void
    {
        $user = User::factory()->create();
        $feature = Feature::factory()->create(['slug' => 'lessons.use']);
        $user->featureOverrides()->create(['feature_id' => $feature->id, 'enabled' => true]);

        $this->assertTrue(app(EntitlementService::class)->userHasFeature($user, 'lessons.use'));
    }
}
