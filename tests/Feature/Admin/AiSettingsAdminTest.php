<?php

namespace Tests\Feature\Admin;

use App\Models\AiModelRoute;
use App\Models\AiProvider;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_access_ai_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/ai')
            ->assertForbidden();
    }

    public function test_admin_can_update_provider_without_exposing_secret(): void
    {
        $admin = User::factory()->create();
        foreach (['admin.dashboard.view', 'admin.ai.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }
        $provider = AiProvider::query()->create(['key' => 'openai', 'name' => 'OpenAI']);

        $this->actingAs($admin)->put("/admin/ai/providers/{$provider->id}", [
            'name' => 'OpenAI',
            'enabled' => 1,
            'mock_mode' => 0,
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'secret-test-key',
        ])->assertRedirect();

        $provider->refresh();

        $this->assertTrue($provider->enabled);
        $this->assertSame('secret-test-key', $provider->encrypted_api_key);
        $this->assertArrayNotHasKey('encrypted_api_key', $provider->toArray());
    }

    public function test_admin_can_update_model_route(): void
    {
        $admin = User::factory()->create();
        foreach (['admin.dashboard.view', 'admin.ai.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }
        $provider = AiProvider::query()->create(['key' => 'mock', 'name' => 'Mock', 'enabled' => true, 'mock_mode' => true]);
        $route = AiModelRoute::query()->create(['feature_slug' => 'ai.coach', 'ai_provider_id' => $provider->id, 'model' => 'mock-coach-v1']);

        $this->actingAs($admin)->put("/admin/ai/routes/{$route->id}", [
            'ai_provider_id' => $provider->id,
            'model' => 'mock-coach-v2',
            'enabled' => 1,
            'monthly_limit' => 5,
            'sort_order' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('ai_model_routes', [
            'id' => $route->id,
            'model' => 'mock-coach-v2',
            'monthly_limit' => 5,
            'sort_order' => 20,
        ]);
    }
}
