<?php

namespace Tests\Feature\Plugins;

use App\Models\Feature;
use App\Models\InstalledPlugin;
use App\Models\Package;
use App\Models\User;
use App\Plugins\PluginManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class LifeWheelPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifewheel_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/LifeWheel/plugin.json')), true));

        $this->assertSame('lifewheel', $manifest->id);
        $this->assertContains('routes/web.php', $manifest->routes);
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_lifewheel_requires_feature_entitlement(): void
    {
        $this->loadLifeWheelPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/lifewheel')
            ->assertForbidden();
    }

    public function test_user_can_create_append_only_assessments_when_entitled(): void
    {
        $this->loadLifeWheelPluginForTest();
        $user = $this->entitledUser();

        $payload = [
            'reflection' => 'Quarterly baseline.',
            'scores' => [
                'body' => 5,
                'mind' => 6,
                'soul' => 7,
                'romance' => 4,
                'family' => 8,
                'friends' => 6,
                'mission' => 7,
                'money' => 5,
                'growth' => 9,
            ],
        ];

        $this->actingAs($user)->post('/app/lifewheel/assessments', $payload)->assertRedirect();
        $this->actingAs($user)->post('/app/lifewheel/assessments', $payload)->assertRedirect();

        $this->assertDatabaseCount('lifewheel_assessments', 2);
        $this->assertDatabaseCount('lifewheel_scores', 18);
    }

    public function test_history_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadLifeWheelPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();

        $assessmentId = DB::table('lifewheel_assessments')->insertGetId([
            'user_id' => $other->id,
            'overall_score' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/lifewheel/history/{$assessmentId}")
            ->assertNotFound();
    }

    private function loadLifeWheelPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'lifewheel',
            'name' => 'LifeWheel',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test LifeWheel plugin.',
            'path' => base_path('plugins/LifeWheel'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/LifeWheel/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/LifeWheel/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/LifeWheel/routes/web.php'));
    }

    private function entitledUser(): User
    {
        $user = User::factory()->create();
        $feature = Feature::query()->firstOrCreate(['slug' => 'lifewheel.use'], ['name' => 'LifeWheel']);
        $history = Feature::query()->firstOrCreate(['slug' => 'lifewheel.history'], ['name' => 'LifeWheel History']);
        $package = Package::factory()->create();
        $package->features()->attach([$feature->id => ['enabled' => true], $history->id => ['enabled' => true]]);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
